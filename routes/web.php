<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Member\DashboardController as MemberDashboardController;
use App\Http\Controllers\Member\PaymentController as MemberPaymentController;
use App\Http\Controllers\Visitor\PayController;
use App\Http\Controllers\Webhook\StripeController as StripeWebhookController;
use Illuminate\Support\Facades\Route;

/**
 * ルーティング設定（routes/web.php）
 *
 * ルーティングとは？
 * 「どのURL（パス）にアクセスしたら、どのコントローラーのどのメソッドを呼ぶか」
 * を定義するファイルです。
 *
 * 例:
 * Route::get('/login', ...) → ブラウザで /login を開いたとき
 * Route::post('/login', ...) → /login にフォームを送信したとき
 *
 * ミドルウェアについて:
 * middleware('auth')        → ログインしていないとアクセスできない
 * middleware('role:admin')  → admin ロールでないとアクセスできない
 */

// =============================================
// トップページ → ログイン画面にリダイレクト
// =============================================
Route::get('/', function () {
    // サイトのトップ（/）にアクセスしたらログイン画面へ
    return redirect()->route('login');
});

// =============================================
// 認証関連ルート（ログイン・ログアウト）
// =============================================
// middleware('guest') : ログイン済みのユーザーはアクセス不可
// ログイン済みの人がログイン画面に来たら、ダッシュボードにリダイレクトされる
Route::middleware('guest')->group(function () {

    /**
     * ログイン画面の表示
     * GET /login → LoginController@show
     * route名: 'login' → route('login') で URL を生成できる
     */
    Route::get('/login', [LoginController::class, 'show'])
        ->name('login');

    /**
     * ログイン処理
     * POST /login → LoginController@login
     * フォームから「ログイン」ボタンを押したときに呼ばれる
     */
    Route::post('/login', [LoginController::class, 'login']);
});

/**
 * ログアウト処理
 * POST /logout → LoginController@logout
 * route名: 'logout'
 *
 * なぜ POST？
 * GET でログアウトできると、悪意のあるサイトからリンクを踏ませるだけで
 * ログアウトさせられてしまうため、POST を使います（CSRF対策と組み合わせ）
 */
Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// =============================================
// 管理者専用ルート（admin のみアクセス可）
// =============================================
// middleware(['auth', 'role:admin'])
// → 「ログイン済み かつ admin ロール」の場合のみアクセス許可
Route::prefix('admin')                          // URL を /admin/... にする
    ->middleware(['auth', 'role:admin'])         // 認証 + admin ロールチェック
    ->name('admin.')                            // ルート名を admin.xxx にする
    ->group(function () {

        /**
         * 管理者ダッシュボード
         * GET /admin/dashboard → AdminDashboardController@index
         * route名: 'admin.dashboard'
         */
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])
            ->name('dashboard');
    });

// =============================================
// 正会員専用ルート（member のみアクセス可）
// =============================================
Route::prefix('member')
    ->middleware(['auth', 'role:member'])
    ->name('member.')
    ->group(function () {

        /**
         * 正会員ダッシュボード
         * GET /member/dashboard → MemberDashboardController@index
         * route名: 'member.dashboard'
         */
        Route::get('/dashboard', [MemberDashboardController::class, 'index'])
            ->name('dashboard');

        /**
         * 月謝決済フォームの表示
         * GET /member/pay → MemberPaymentController@show
         * route名: 'member.pay'
         */
        Route::get('/pay', [MemberPaymentController::class, 'show'])
            ->name('pay');

        /**
         * PaymentIntentの作成（AJAXエンドポイント）
         * POST /member/payment/intent → MemberPaymentController@createPaymentIntent
         * route名: 'member.payment.intent'
         *
         * フロントエンド（Stripe.js）が決済を開始するために呼ぶエンドポイント。
         * JSONでclient_secretを返す。
         */
        Route::post('/payment/intent', [MemberPaymentController::class, 'createPaymentIntent'])
            ->name('payment.intent');

        /**
         * 決済完了の記録（AJAXエンドポイント）
         * POST /member/payment/complete → MemberPaymentController@complete
         * route名: 'member.payment.complete'
         *
         * Stripe.jsが決済を完了させた後に呼ぶエンドポイント。
         * DBに成功を記録してリダイレクト先URLを返す。
         */
        Route::post('/payment/complete', [MemberPaymentController::class, 'complete'])
            ->name('payment.complete');
    });

// =============================================
// ビジター専用ルート（visitor のみアクセス可）
// =============================================
Route::prefix('visitor')
    ->middleware(['auth', 'role:visitor'])
    ->name('visitor.')
    ->group(function () {

        /**
         * ビジター決済画面
         * GET /visitor/pay → PayController@index
         * route名: 'visitor.pay'
         */
        Route::get('/pay', [PayController::class, 'index'])
            ->name('pay');
    });

// =============================================
// Webhook ルート（認証不要・CSRF除外）
// =============================================
/**
 * Stripe Webhook の受信エンドポイント
 * POST /webhook/stripe → StripeWebhookController@handle
 * route名: 'webhook.stripe'
 *
 * 重要：このルートには2つの特別な設定が必要です。
 *
 * 1. 認証なし（middleware('auth') を付けない）
 *    → StripeはユーザーではなくStripeのサーバーから送ってくるため、
 *      ログイン状態を持っていません。
 *
 * 2. CSRF検証を除外（withoutMiddleware）
 *    → StripeはCSRFトークンを送らないため、CSRF検証が通りません。
 *      代わりに Webhook Secret による署名検証でセキュリティを担保します。
 *
 * セキュリティ：
 * CSRF検証がない代わりに、StripeWebhookController内で
 * Stripe署名（Stripe-Signatureヘッダー）を検証します。
 * これにより偽のWebhookを受け付けないようにしています。
 */
Route::post('/webhook/stripe', [StripeWebhookController::class, 'handle'])
    ->name('webhook.stripe')
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
