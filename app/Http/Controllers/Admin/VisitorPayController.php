<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;

/**
 * 管理者によるビジター代行決済コントローラー
 *
 * このコントローラーの目的：
 * 管理者（スタッフ）がクラブの窓口（受付）でビジターの代わりに
 * クレジットカード決済を処理できる機能を提供します。
 *
 * 使用場面の例：
 * - ビジターがスマートフォンを持っていない
 * - ビジターが決済操作に不慣れ
 * - 管理者が代理で決済処理をしたい
 *
 * セキュリティ：
 * - routes/web.php で middleware(['auth', 'role:admin']) を設定しているため
 *   管理者（admin）のみがこのコントローラーにアクセスできます。
 * - 決済はビジターのユーザーIDに紐付けて記録されます。
 *
 * 担当する画面・エンドポイント：
 * - GET  /admin/visitor/pay              : 代行決済フォームの表示（index）
 * - POST /admin/visitor/payment/intent   : PaymentIntentの作成（createPaymentIntent）
 * - POST /admin/visitor/payment/complete : 決済完了の記録（complete）
 */
class VisitorPayController extends Controller
{
    /**
     * コンストラクタ：Stripeのシークレットキーを設定する
     */
    public function __construct()
    {
        Stripe::setApiKey(config('stripe.secret'));
    }

    /**
     * 管理者向けビジター代行決済フォームを表示する
     *
     * GET /admin/visitor/pay
     *
     * ビジター一覧をドロップダウンで表示し、
     * 対象のビジターを選んで決済を代行できます。
     *
     * @param  \Illuminate\Http\Request  $request  クエリパラメーター（visitor_id）を含む
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // ビジター一覧を取得（ドロップダウン用）
        // 名前順で並べて選びやすくする
        $visitors = User::where('role', 'visitor')
            ->orderBy('name')
            ->get();

        // URLパラメーターで選択中のビジターIDを受け取る
        // 例: /admin/visitor/pay?visitor_id=5
        $selectedVisitorId = $request->input('visitor_id');

        // 選択されたビジターを取得（ない場合は null）
        $selectedVisitor = null;
        if ($selectedVisitorId) {
            // findOrFail() : 存在しないIDの場合は404エラーを返す
            $selectedVisitor = User::where('role', 'visitor')
                ->where('id', $selectedVisitorId)
                ->first();
        }

        // 選択されたビジターの最近の決済履歴を取得（ある場合のみ）
        $recentPayments = collect(); // 空のコレクション（デフォルト）
        if ($selectedVisitor) {
            $recentPayments = $selectedVisitor->payments()
                ->where('payment_type', 'visitor_fee')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get();
        }

        // ビジター料金（2,500円）
        $amount = config('stripe.prices.visitor_fee');

        return view('admin.visitor.pay', compact(
            'visitors',
            'selectedVisitor',
            'selectedVisitorId',
            'recentPayments',
            'amount',
            'stripeKey',
        ) + ['stripeKey' => config('stripe.key')]);
    }

    /**
     * PaymentIntentを作成してclient_secretを返す（管理者代行）
     *
     * POST /admin/visitor/payment/intent
     *
     * 管理者が選択したビジターのためにPaymentIntentを作成します。
     * 決済はビジターのユーザーIDに紐付けて記録されます。
     *
     * @param  \Illuminate\Http\Request  $request  visitor_id を含む
     * @return \Illuminate\Http\JsonResponse
     */
    public function createPaymentIntent(Request $request)
    {
        // バリデーション：visitor_id が必須
        $request->validate([
            'visitor_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        // 対象ビジターを取得
        $visitor = User::where('role', 'visitor')
            ->where('id', $request->input('visitor_id'))
            ->firstOrFail();

        try {
            // StripeカスタマーIDの取得または作成
            $customerId = $this->getOrCreateStripeCustomer($visitor);

            // PaymentIntentの作成
            $paymentIntent = PaymentIntent::create([
                'amount'      => config('stripe.prices.visitor_fee'), // 2500
                'currency'    => config('stripe.currency'), // 'jpy'
                'customer'    => $customerId,
                'description' => 'ビジター参加費（管理者代行）',

                // メタデータに「管理者代行」であることを記録
                'metadata' => [
                    'user_id'        => $visitor->id,
                    'user_name'      => $visitor->name,
                    'user_email'     => $visitor->email,
                    'payment_type'   => 'visitor_fee',
                    'processed_by'   => 'admin', // 管理者代行であることを示す
                ],

                'automatic_payment_methods' => [
                    'enabled' => true,
                ],
            ]);

            // DB に「pending（処理中）」レコードを作成
            // user_id はビジター（代行者の管理者ではない）のIDを使う
            Payment::create([
                'user_id'                  => $visitor->id,
                'payment_type'             => 'visitor_fee',
                'amount'                   => config('stripe.prices.visitor_fee'),
                'status'                   => 'pending',
                'stripe_payment_intent_id' => $paymentIntent->id,
                'notes'                    => '管理者代行決済',
            ]);

            Log::info('管理者代行PaymentIntent作成', [
                'visitor_id'        => $visitor->id,
                'payment_intent_id' => $paymentIntent->id,
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
            ]);

        } catch (ApiErrorException $e) {
            Log::error('管理者代行PaymentIntent作成エラー', [
                'visitor_id' => $visitor->id,
                'message'    => $e->getMessage(),
            ]);

            return response()->json([
                'error' => '決済の準備中にエラーが発生しました。'
            ], 500);
        }
    }

    /**
     * 代行決済完了後の処理
     *
     * POST /admin/visitor/payment/complete
     *
     * PaymentIntentIDを使ってStripeに問い合わせ、
     * 決済成功を確認してからDBを更新します。
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function complete(Request $request)
    {
        $request->validate([
            'payment_intent_id' => ['required', 'string'],
            'visitor_id'        => ['required', 'integer', 'exists:users,id'],
        ]);

        $paymentIntentId = $request->input('payment_intent_id');
        $visitorId       = $request->input('visitor_id');

        try {
            // StripeのAPIで PaymentIntent の状態を確認
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            // DBからこのPaymentIntentのレコードを取得
            // visitor_id も照合してなりすまし防止
            $payment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
                ->where('user_id', $visitorId)
                ->firstOrFail();

            if ($paymentIntent->status === 'succeeded') {
                // 決済成功：DBレコードを更新
                $payment->update([
                    'status'           => 'succeeded',
                    'stripe_charge_id' => $paymentIntent->latest_charge,
                    'paid_at'          => now(),
                ]);

                Log::info('管理者代行決済成功', [
                    'visitor_id'        => $visitorId,
                    'payment_intent_id' => $paymentIntentId,
                    'amount'            => $payment->amount,
                ]);

                // ビジター名を取得してメッセージに含める
                $visitor = User::find($visitorId);

                return response()->json([
                    'success'  => true,
                    'message'  => ($visitor?->name ?? 'ビジター') . ' さんの参加費を受け付けました。',
                    'redirect' => route('admin.visitor.pay') . '?visitor_id=' . $visitorId,
                ]);

            } else {
                $payment->update(['status' => 'failed']);

                return response()->json([
                    'success' => false,
                    'error'   => '決済が完了していません。ステータス: ' . $paymentIntent->status,
                ], 400);
            }

        } catch (ApiErrorException $e) {
            Log::error('管理者代行決済完了確認エラー', [
                'visitor_id'        => $visitorId,
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
    // プライベートメソッド
    // =============================================

    /**
     * StripeカスタマーIDを取得または新規作成する
     *
     * @param  \App\Models\User  $user  ビジターのユーザーモデル
     * @return string  StripeカスタマーID（例: cus_abc123）
     */
    private function getOrCreateStripeCustomer($user): string
    {
        if ($user->stripe_customer_id) {
            return $user->stripe_customer_id;
        }

        $customer = Customer::create([
            'name'  => $user->name,
            'email' => $user->email,
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        $user->update(['stripe_customer_id' => $customer->id]);

        return $customer->id;
    }
}
