<?php

namespace App\Http\Controllers\Member;

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
 * 正会員の月謝決済コントローラー
 *
 * このコントローラーが担当する処理：
 * 1. 決済フォームの表示（show メソッド）
 * 2. PaymentIntentの作成（createPaymentIntent メソッド）
 *    → フロントエンドのStripe.jsが決済処理を行うために必要な「鍵」を返す
 * 3. 決済完了後の記録（complete メソッド）
 *    → 決済成功を確認してpaymentsテーブルに保存する
 *
 * Stripeの決済フローについて（Payment Intentとは？）
 * ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
 * 1. サーバー（このコントローラー）が PaymentIntent を作成する
 *    → Stripeに「9,000円の決済を準備してください」と依頼
 *    → Stripeから「client_secret（クライアントシークレット）」が返ってくる
 *
 * 2. フロントエンド（Stripe.js）が client_secret を使ってカード情報を送信する
 *    → カード情報はブラウザからStripeに直接送られる（サーバーを経由しない）
 *    → これによりカード情報がサーバーに保存されるリスクがゼロになる（PCI DSS準拠）
 *
 * 3. 決済成功後、フロントエンドがサーバーに通知する
 *    → サーバーがStripeに問い合わせて決済を確認
 *    → paymentsテーブルに記録する
 *
 * 4. Stripeがバックグラウンドでも Webhook を送ってくる
 *    → WebhookController で処理する（より確実な方法）
 */
class PaymentController extends Controller
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
        // Stripeのシークレットキーをセット（config/stripe.phpから読み込む）
        // これをしないとStripeのAPIが「認証エラー」になる
        Stripe::setApiKey(config('stripe.secret'));
    }

    /**
     * 月謝決済フォームを表示する
     *
     * GET /member/pay
     *
     * 今月分がすでに支払い済みかどうかをチェックして、
     * 未払いの場合のみ決済フォームを表示します。
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show()
    {
        $user = Auth::user();

        // 今月分の月謝が支払い済みかチェック
        $alreadyPaid = Payment::where('user_id', $user->id)
            ->where('payment_type', 'monthly_fee')
            ->where('status', 'succeeded')
            ->whereMonth('billing_month', now()->month)
            ->whereYear('billing_month', now()->year)
            ->exists(); // 存在するかどうか（true/false）を返す

        // すでに支払い済みならダッシュボードに戻す
        if ($alreadyPaid) {
            return redirect()->route('member.dashboard')
                ->with('message', '今月の月謝はすでにお支払いいただいています。');
        }

        // 月謝金額を config/stripe.php から取得
        $amount = config('stripe.prices.monthly_fee'); // 9000

        return view('member.pay', [
            'user'          => $user,
            'amount'        => $amount,
            'billingMonth'  => now()->format('Y年m月'),    // 表示用（例: 2026年4月）
            'stripeKey'     => config('stripe.key'),       // Stripe.jsに渡す公開可能キー
        ]);
    }

    /**
     * PaymentIntentを作成してclient_secretを返す
     *
     * POST /member/payment/intent
     *
     * このメソッドはAJAXリクエスト（JavaScriptからの非同期通信）で呼ばれます。
     * フロントエンドのStripe.jsがカード情報を処理するために必要な
     * client_secret を JSON形式で返します。
     *
     * PaymentIntentとは？
     * Stripeでの決済を表すオブジェクトです。
     * 「これから9,000円の決済を行う予定です」という宣言のようなものです。
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPaymentIntent(Request $request)
    {
        $user = Auth::user();

        // 二重決済チェック：今月分がすでに支払い済みでないか確認
        $alreadyPaid = Payment::where('user_id', $user->id)
            ->where('payment_type', 'monthly_fee')
            ->where('status', 'succeeded')
            ->whereMonth('billing_month', now()->month)
            ->whereYear('billing_month', now()->year)
            ->exists();

        if ($alreadyPaid) {
            // HTTPステータス 409 Conflict = すでに処理済み
            return response()->json([
                'error' => '今月の月謝はすでにお支払いいただいています。'
            ], 409);
        }

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
                'amount'   => config('stripe.prices.monthly_fee'), // 9000

                // 通貨（jpy = 日本円）
                'currency' => config('stripe.currency'), // 'jpy'

                // 誰の決済か（StripeのカスタマーID）
                'customer' => $customerId,

                // 決済の説明（Stripeダッシュボードで確認できる）
                'description' => now()->format('Y年m月') . '分 月謝',

                // メタデータ（Stripeダッシュボードやログで確認用）
                // 任意のキーバリューを保存できる
                'metadata' => [
                    'user_id'       => $user->id,
                    'user_name'     => $user->name,
                    'user_email'    => $user->email,
                    'payment_type'  => 'monthly_fee',
                    'billing_month' => now()->format('Y-m'),
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
                'payment_type'             => 'monthly_fee',
                'amount'                   => config('stripe.prices.monthly_fee'),
                'status'                   => 'pending', // 処理中
                'stripe_payment_intent_id' => $paymentIntent->id,
                'billing_month'            => now()->startOfMonth(), // 今月1日
            ]);

            // フロントエンドに client_secret を返す
            // client_secret は Stripe.js がカード情報を送るために使う「鍵」
            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
            ]);

        } catch (ApiErrorException $e) {
            // Stripe APIのエラー（カード会社との通信エラーなど）
            Log::error('PaymentIntent作成エラー', [
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
     * POST /member/payment/complete
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

                Log::info('月謝決済成功', [
                    'user_id'            => $user->id,
                    'payment_intent_id'  => $paymentIntentId,
                    'amount'             => $payment->amount,
                ]);

                return response()->json([
                    'success'  => true,
                    'message'  => '月謝のお支払いが完了しました。',
                    'redirect' => route('member.dashboard'),
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
            Log::error('決済完了確認エラー', [
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
