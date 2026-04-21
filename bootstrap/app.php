<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /**
         * リバースプロキシの信頼設定（Railway・本番環境用）
         *
         * 【問題の背景】
         * Railway はリバースプロキシ（ロードバランサー）を介してリクエストを転送します。
         * この場合、Laravel が受け取るリクエストの送信元 IP は「プロキシのIP」になるため、
         * セッションの固定（Session fixation）対策やCSRF検証が誤動作し、
         * POST /login が 500 エラーになることがあります。
         *
         * 【解決策】
         * trustProxies() でプロキシを信頼することで、
         * X-Forwarded-* ヘッダーに含まれる「本来のクライアント情報」を
         * Laravel が正しく読み取れるようになります。
         *
         * at: '*'  → すべてのプロキシを信頼する
         *           （Railway のプロキシIPは動的に変わるため '*' を使用）
         *
         * headers: 信頼するヘッダーをビット演算（|）で列挙する
         *   HEADER_X_FORWARDED_FOR   → クライアントの実際のIPアドレス
         *   HEADER_X_FORWARDED_PROTO → クライアントが使ったプロトコル（http/https）
         *   HEADER_X_FORWARDED_HOST  → クライアントが要求したホスト名
         */
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR |
                     Request::HEADER_X_FORWARDED_PROTO |
                     Request::HEADER_X_FORWARDED_HOST,
        );

        /**
         * カスタムミドルウェアの登録
         *
         * ミドルウェアエイリアスとは？
         * ミドルウェアのクラス名に短い名前（エイリアス）を付ける機能です。
         * routes/web.php で Route::middleware('role:admin') のように
         * 短い名前で指定できるようになります。
         *
         * alias() の引数:
         * キー   → routes/web.php で使う短い名前
         * 値     → 実際のミドルウェアクラス
         */
        $middleware->alias([
            // 'role' という名前で RoleMiddleware を使えるようにする
            // 使い方: middleware('role:admin') または middleware('role:member,visitor')
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        /**
         * ログイン済みユーザーがゲスト専用ページ（/login など）にアクセスした場合の
         * リダイレクト先を設定する。
         *
         * 【問題の背景】
         * Laravel 12 では RouteServiceProvider::HOME が存在しないため、
         * guest ミドルウェアはデフォルトで '/' にリダイレクトします。
         * しかし routes/web.php の '/' は route('login') に飛ばすため、
         * /login → / → /login → / ... という無限リダイレクトループが発生します。
         *
         * 【解決策】
         * redirectUsersTo() でロールに応じた正しい画面を返すことで、
         * /login にアクセスしてきたログイン済みユーザーを適切な画面に飛ばします。
         */
        $middleware->redirectUsersTo(function (Request $request) {
            $user = Auth::user();
            if ($user) {
                // User モデルの getRedirectUrlByRole() でロールに応じたURLを返す
                // admin → /admin/dashboard
                // member → /member/dashboard
                // visitor → /visitor/pay
                return $user->getRedirectUrlByRole();
            }
            return '/login';
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
