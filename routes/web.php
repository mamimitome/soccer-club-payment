<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MemberController as AdminMemberController;
use App\Http\Controllers\Admin\VisitorPayController as AdminVisitorPayController;
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

        // =============================================
        // 会員管理 CRUD ルート
        // =============================================
        // Route::resource を使わずに個別定義する理由：
        // - ルートモデルバインディングのパラメーター名を {member} にしたいため
        // - 各ルートに日本語コメントを付けやすくするため

        /**
         * 会員一覧
         * GET /admin/members → AdminMemberController@index
         * route名: 'admin.members.index'
         */
        Route::get('/members', [AdminMemberController::class, 'index'])
            ->name('members.index');

        /**
         * 会員追加フォームの表示
         * GET /admin/members/create → AdminMemberController@create
         * route名: 'admin.members.create'
         *
         * ※ /members/{member} より先に定義しないと "create" が {member} にマッチしてしまう
         */
        Route::get('/members/create', [AdminMemberController::class, 'create'])
            ->name('members.create');

        /**
         * 会員追加の保存処理
         * POST /admin/members → AdminMemberController@store
         * route名: 'admin.members.store'
         */
        Route::post('/members', [AdminMemberController::class, 'store'])
            ->name('members.store');

        /**
         * 会員編集フォームの表示
         * GET /admin/members/{member}/edit → AdminMemberController@edit
         * route名: 'admin.members.edit'
         *
         * {member} : ルートモデルバインディング（URLのIDからUserモデルを自動取得）
         */
        Route::get('/members/{member}/edit', [AdminMemberController::class, 'edit'])
            ->name('members.edit');

        /**
         * 会員情報の更新処理
         * PUT /admin/members/{member} → AdminMemberController@update
         * route名: 'admin.members.update'
         */
        Route::put('/members/{member}', [AdminMemberController::class, 'update'])
            ->name('members.update');

        /**
         * 会員の削除処理
         * DELETE /admin/members/{member} → AdminMemberController@destroy
         * route名: 'admin.members.destroy'
         */
        Route::delete('/members/{member}', [AdminMemberController::class, 'destroy'])
            ->name('members.destroy');

        // =============================================
        // ビジター代行決済ルート（管理者専用）
        // =============================================

        /**
         * ビジター代行決済フォームの表示
         * GET /admin/visitor/pay → AdminVisitorPayController@index
         * route名: 'admin.visitor.pay'
         *
         * ?visitor_id=xxx で対象ビジターを指定できる
         */
        Route::get('/visitor/pay', [AdminVisitorPayController::class, 'index'])
            ->name('visitor.pay');

        /**
         * ビジター代行PaymentIntentの作成（AJAXエンドポイント）
         * POST /admin/visitor/payment/intent → AdminVisitorPayController@createPaymentIntent
         * route名: 'admin.visitor.payment.intent'
         */
        Route::post('/visitor/payment/intent', [AdminVisitorPayController::class, 'createPaymentIntent'])
            ->name('visitor.payment.intent');

        /**
         * ビジター代行決済完了の記録（AJAXエンドポイント）
         * POST /admin/visitor/payment/complete → AdminVisitorPayController@complete
         * route名: 'admin.visitor.payment.complete'
         */
        Route::post('/visitor/payment/complete', [AdminVisitorPayController::class, 'complete'])
            ->name('visitor.payment.complete');
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

        /**
         * ビジターPaymentIntentの作成（AJAXエンドポイント）
         * POST /visitor/payment/intent → PayController@createPaymentIntent
         * route名: 'visitor.payment.intent'
         *
         * フロントエンドのStripe.jsが決済を開始するために呼ぶエンドポイント。
         * JSONでclient_secretを返す。
         */
        Route::post('/payment/intent', [PayController::class, 'createPaymentIntent'])
            ->name('payment.intent');

        /**
         * ビジター決済完了の記録（AJAXエンドポイント）
         * POST /visitor/payment/complete → PayController@complete
         * route名: 'visitor.payment.complete'
         *
         * Stripe.jsが決済を完了させた後に呼ぶエンドポイント。
         * DBに成功を記録する。
         */
        Route::post('/payment/complete', [PayController::class, 'complete'])
            ->name('payment.complete');
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
