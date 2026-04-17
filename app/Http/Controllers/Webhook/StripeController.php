<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

/**
 * Stripe Webhookコントローラー
 *
 * Webhookとは？
 * Stripeが「決済が完了した」「失敗した」などのイベントを
 * 自動的にこのサーバーに通知してくる仕組みです。
 *
 * なぜWebhookが必要なの？
 * フロントエンドからの通知だけでは不確実な場合があります。
 * 例えば：
 * - ユーザーが決済後にブラウザを閉じた
 * - ネットワークエラーで「完了しました」の通知がサーバーに届かなかった
 * - 月謝の自動引き落とし（ユーザーの操作なし）
 *
 * Webhookを使うことで、StripeからサーバーへHTTPリクエストが送られるため
 * ユーザーの行動に関係なく確実に処理できます。
 *
 * セキュリティ：署名の検証
 * Stripeから来たWebhookかどうかを確認するため、
 * Webhook Secretを使って署名を検証します。
 * 検証に失敗した場合は不正なリクエストとして拒否します。
 */
class StripeController extends Controller
{
    /**
     * Stripeからのイベント通知を受け取る
     *
     * POST /webhook/stripe
     *
     * 注意：このエンドポイントはCSRF検証を除外する必要があります。
     * （Stripeはブラウザではなくサーバーからリクエストを送るため、
     *   CSRFトークンを持っていません）
     * → routes/web.php で withoutMiddleware(\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class)
     *   を設定するか、bootstrap/app.php で除外します。
     *
     * @param  Request  $request  Stripeからのリクエスト
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        // Stripeのシークレットキーをセット
        Stripe::setApiKey(config('stripe.secret'));

        // =============================================
        // 署名の検証（セキュリティの要）
        // =============================================
        // Stripeは各リクエストに「Stripe-Signature」ヘッダーを付けて送ります。
        // Webhook Secretを使ってこの署名を検証することで、
        // 「本当にStripeからのリクエストか」を確認できます。

        // リクエストの生のボディを取得（署名検証には生のボディが必要）
        $payload   = $request->getContent();
        // Stripe-Signature ヘッダーを取得
        $sigHeader = $request->header('Stripe-Signature');
        // Webhook Secret（.envから取得）
        $webhookSecret = config('stripe.webhook_secret');

        try {
            // Stripe SDKで署名を検証する
            // 検証に失敗すると SignatureVerificationException が発生する
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);

        } catch (\UnexpectedValueException $e) {
            // ペイロード（リクエスト本文）が不正
            Log::warning('Webhook: 不正なペイロード', ['message' => $e->getMessage()]);
            return response()->json(['error' => '不正なリクエストです'], 400);

        } catch (SignatureVerificationException $e) {
            // 署名の検証失敗 → 偽のWebhookの可能性がある
            Log::warning('Webhook: 署名の検証失敗', ['message' => $e->getMessage()]);
            return response()->json(['error' => '署名の検証に失敗しました'], 400);
        }

        // =============================================
        // イベントの種類に応じた処理
        // =============================================
        // Stripeには様々なイベントがありますが、
        // このシステムでは決済関連のイベントだけを処理します。

        // $event->type : イベントの種類を表す文字列
        // 例: 'payment_intent.succeeded', 'payment_intent.payment_failed'
        switch ($event->type) {

            case 'payment_intent.succeeded':
                // =============================================
                // 決済成功イベント
                // =============================================
                // 決済が成功したときにStripeから送られてくる
                $this->handlePaymentIntentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                // =============================================
                // 決済失敗イベント
                // =============================================
                // カードの残高不足、期限切れなどで失敗したとき
                $this->handlePaymentIntentFailed($event->data->object);
                break;

            default:
                // 上記以外のイベントは無視する（ログだけ残す）
                Log::info('Webhook: 未処理のイベント', ['type' => $event->type]);
                break;
        }

        // Stripeに「正常に受け取った」と応答する（200を返さないとStripeが再送してくる）
        return response()->json(['received' => true]);
    }

    // =============================================
    // プライベートメソッド（各イベントの処理）
    // =============================================

    /**
     * payment_intent.succeeded イベントの処理
     *
     * 決済が成功したときの処理：
     * - paymentsテーブルのstatusを 'succeeded' に更新
     * - paid_at（決済完了日時）を記録
     *
     * @param  \Stripe\PaymentIntent  $paymentIntent  Stripeの PaymentIntent オブジェクト
     */
    private function handlePaymentIntentSucceeded($paymentIntent): void
    {
        // PaymentIntentIDでDBのレコードを検索
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (!$payment) {
            // DBにレコードがない場合（通常はないが念のため）
            Log::warning('Webhook: 対応するPaymentレコードが見つかりません', [
                'payment_intent_id' => $paymentIntent->id,
            ]);
            return;
        }

        // すでに成功済みの場合は重複処理しない（Webhookの再送対策）
        if ($payment->status === 'succeeded') {
            Log::info('Webhook: すでに処理済みのPaymentIntent', [
                'payment_intent_id' => $paymentIntent->id,
            ]);
            return;
        }

        // DBを更新する
        $payment->update([
            'status'           => 'succeeded',
            'stripe_charge_id' => $paymentIntent->latest_charge, // ChargeID
            'paid_at'          => now(),
        ]);

        Log::info('Webhook: 決済成功を記録', [
            'payment_id'        => $payment->id,
            'user_id'           => $payment->user_id,
            'amount'            => $payment->amount,
            'payment_intent_id' => $paymentIntent->id,
        ]);
    }

    /**
     * payment_intent.payment_failed イベントの処理
     *
     * 決済が失敗したときの処理：
     * - paymentsテーブルのstatusを 'failed' に更新
     * - 失敗理由を記録
     *
     * @param  \Stripe\PaymentIntent  $paymentIntent  Stripeの PaymentIntent オブジェクト
     */
    private function handlePaymentIntentFailed($paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (!$payment) {
            Log::warning('Webhook: 対応するPaymentレコードが見つかりません（失敗イベント）', [
                'payment_intent_id' => $paymentIntent->id,
            ]);
            return;
        }

        // 失敗理由を取得（Stripeから返ってくるエラーメッセージ）
        // ?-> はオプショナルチェーン：null でもエラーにならない
        $failureReason = $paymentIntent->last_payment_error?->message
            ?? '不明なエラー';

        $payment->update([
            'status'         => 'failed',
            'failure_reason' => $failureReason,
        ]);

        Log::warning('Webhook: 決済失敗を記録', [
            'payment_id'        => $payment->id,
            'user_id'           => $payment->user_id,
            'failure_reason'    => $failureReason,
            'payment_intent_id' => $paymentIntent->id,
        ]);
    }
}
