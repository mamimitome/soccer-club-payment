<?php

namespace App\Http\Controllers\Visitor;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;

/**
 * ビジター決済コントローラー
 *
 * ビジター（visitor）専用の決済画面と決済処理を管理します。
 * RoleMiddleware により、visitor ロール以外はアクセスできません。
 *
 * 担当する画面・エンドポイント：
 * - GET  /visitor/pay              : 決済フォームの表示（index）
 * - POST /visitor/payment/intent   : PaymentIntentの作成（createPaymentIntent）
 * - POST /visitor/payment/complete : 決済完了の記録（complete）
 *
 * 正会員の PaymentController（Member/PaymentController.php）と同じ仕組みですが、
 * 以下が異なります：
 * - 金額が 2,500円（visitor_fee）
 * - 今月支払い済みチェックをしない（都度払いのため何度でも支払い可能）
 * - payment_type が 'visitor_fee'
 * - billing_month を保存しない（都度払いのため月次管理不要）
 */
class PayController extends Controller
{
    /**
     * コンストラクタ：Stripeのシークレットキーを設定する
     *
     * コンストラクタとは？
     * クラスが使われるときに最初に実行されるメソッドです。
     * ここでStripeのAPIキーをセットすることで、
     * 各メソッド内でStripeのAPIが使えるようになります。
     */
    public function __construct()
    {
        // Stripeのシークレットキーをセット
        // これをしないとStripeのAPIが「認証エラー」になる
        Stripe::setApiKey(config('stripe.secret'));
    }

    /**
     * ビジター決済画面を表示する
     *
     * GET /visitor/pay
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 現在ログインしているビジターを取得
        $user = Auth::user();

        // このビジターの過去の決済履歴を取得（新しい順に5件）
        $recentPayments = $user->payments()
            ->where('payment_type', 'visitor_fee') // ビジター料金のみ
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // ビジター料金を config/stripe.php から取得（2,500円）
        $amount = config('stripe.prices.visitor_fee');

        $stripeKey = config('stripe.key');

        return view('visitor.pay', compact(
            'user',
            'recentPayments',
            'amount',
            'stripeKey',
        ));
    }

    /**
     * PaymentIntentを作成してclient_secretを返す
     *
     * POST /visitor/payment/intent
     *
     * このメソッドはAJAXリクエスト（JavaScriptからの非同期通信）で呼ばれます。
     * フロントエンドのStripe.jsがカード情報を処理するために必要な
     * client_secret を JSON形式で返します。
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPaymentIntent(Request $request)
    {
        $user = Auth::user();

        try {
            // =============================================
            // StripeカスタマーIDの取得または作成
            // =============================================
            // カスタマーIDとは？
            // Stripeがユーザーを識別するためのIDです（例: cus_abc123）。
            // 一度作成したカスタマーIDを保存しておくことで、
            // 次回以降の決済で自動的に紐付けられます。
            $customerId = $this->getOrCreateStripeCustomer($user);

            // =============================================
            // PaymentIntentの作成
            // =============================================
            $paymentIntent = PaymentIntent::create([
                // 金額（日本円は最小通貨単位が1円なのでそのまま渡す）
                'amount'   => config('stripe.prices.visitor_fee'), // 2500

                // 通貨（jpy = 日本円）
                'currency' => config('stripe.currency'), // 'jpy'

                // 誰の決済か（StripeのカスタマーID）
                'customer' => $customerId,

                // 決済の説明（Stripeダッシュボードで確認できる）
                'description' => 'ビジター参加費',

                // メタデータ（Stripeダッシュボードやログで確認用の補足情報）
                'metadata' => [
                    'user_id'      => $user->id,
                    'user_name'    => $user->name,
                    'user_email'   => $user->email,
                    'payment_type' => 'visitor_fee',
                ],

                // 自動的に決済方法を確認する（推奨設定）
                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            // =============================================
            // DB に「pending（処理中）」レコードを作成
            // =============================================
            // なぜこのタイミングでDBに保存するの？
            // ネットワークエラーなどで決済が中断した場合でも、
            // 「決済を試みた」という記録が残るようにするためです。
            // 最終的な成功・失敗はWebhookで更新します。
            Payment::create([
                'user_id'                  => $user->id,
                'payment_type'             => 'visitor_fee',
                'amount'                   => config('stripe.prices.visitor_fee'),
                'status'                   => 'pending', // 処理中
                'stripe_payment_intent_id' => $paymentIntent->id,
                // billing_month は都度払いのため null（月次管理不要）
            ]);

            // フロントエンドに client_secret を返す
            // client_secret は Stripe.js がカード情報を送るために使う「鍵」
            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
            ]);
        } catch (ApiErrorException $e) {
            // Stripe APIのエラー（ネットワークエラーなど）
            Log::error('ビジターPaymentIntent作成エラー', [
                'user_id' => $user->id,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => '決済の準備中にエラーが発生しました。しばらく後でお試しください。'
            ], 500);
        }
    }

    /**
     * 決済完了後の処理
     *
     * POST /visitor/payment/complete
     *
     * Stripe.js が決済を完了させた後、フロントエンドから呼ばれます。
     * PaymentIntentIDを使ってStripeに問い合わせ、
     * 本当に決済が成功しているかを確認してからDBを更新します。
     *
     * なぜフロントエンドの報告だけを信頼しないの？
     * フロントエンドは改ざんが可能なため、
     * 必ずサーバーからStripeに問い合わせて確認する必要があります。
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete(Request $request)
    {
        // バリデーション：payment_intent_id が必須
        $request->validate([
            'payment_intent_id' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $paymentIntentId = $request->input('payment_intent_id');

        try {
            // =============================================
            // StripeのAPIで PaymentIntent の状態を確認
            // =============================================
            // フロントエンドからの報告を信頼せず、
            // 必ずStripeサーバーに問い合わせて確認する（セキュリティのため）
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            // DBからこのPaymentIntentに対応するレコードを取得
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
                ->where('user_id', $user->id) // 本人の決済かを確認（なりすまし防止）
                ->firstOrFail();

            if ($paymentIntent->status === 'succeeded') {
                // =============================================
                // 決済成功：DBレコードを更新
                // =============================================
                $payment->update([
                    'status'           => 'succeeded',
                    'stripe_charge_id' => $paymentIntent->latest_charge, // ChargeID
                    'paid_at'          => now(),
                ]);

                Log::info('ビジター決済成功', [
                    'user_id'           => $user->id,
                    'payment_intent_id' => $paymentIntentId,
                    'amount'            => $payment->amount,
                ]);

                return response()->json([
                    'success'  => true,
                    'message'  => '参加費のお支払いが完了しました。',
                    'redirect' => route('visitor.pay'), // 支払い後も同じ画面に戻る
                ]);
            } else {
                // Stripeのステータスが 'succeeded' 以外の場合
                $payment->update(['status' => 'failed']);

                return response()->json([
                    'success' => false,
                    'error'   => '決済が完了していません。ステータス: ' . $paymentIntent->status,
                ], 400);
            }
        } catch (ApiErrorException $e) {
            Log::error('ビジター決済完了確認エラー', [
                'user_id'           => $user->id,
                'payment_intent_id' => $paymentIntentId,
                'message'           => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error'   => '決済の確認中にエラーが発生しました。',
            ], 500);
        }
    }

    // =============================================
    // プライベートメソッド（内部処理）
    // =============================================

    /**
     * StripeカスタマーIDを取得または新規作成する
     *
     * すでに stripe_customer_id が保存されている場合はそれを返し、
     * ない場合は新しくStripeカスタマーを作成して保存します。
     *
     * @param  \App\Models\User  $user
     * @return string  StripeカスタマーID（例: cus_abc123）
     */
    private function getOrCreateStripeCustomer($user): string
    {
        // すでにStripeカスタマーIDが保存されている場合はそれを返す
        if ($user->stripe_customer_id) {
            return $user->stripe_customer_id;
        }

        // Stripeに新しいカスタマーを作成する
        $customer = Customer::create([
            'name'  => $user->name,
            'email' => $user->email,
            'metadata' => [
                'user_id' => $user->id, // LaravelのユーザーIDをメモとして保存
            ],
        ]);

        // 作成されたカスタマーIDをDBに保存する（次回以降はこれを使う）
        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }
}
