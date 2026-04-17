<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * ロール（役割）チェックミドルウェア
 *
 * ミドルウェアとは？
 * リクエスト（ブラウザからのアクセス）がコントローラーに届く前に
 * 実行される「門番」のような存在です。
 *
 * このミドルウェアの役割：
 * 各ページにアクセスできるロールを制限します。
 * 例えば /admin/dashboard は admin だけがアクセスでき、
 * member や visitor がアクセスしようとすると、自分の画面に戻されます。
 *
 * 使い方（routes/web.php で指定）：
 * Route::middleware(['auth', 'role:admin'])->group(...)
 * ↑ 「認証済みかつ admin ロール」の場合のみアクセス許可
 */
class RoleMiddleware
{
    /**
     * リクエストを処理する
     *
     * @param  Request  $request  ブラウザからのリクエスト情報
     * @param  Closure  $next     次のミドルウェアまたはコントローラーへ進む処理
     * @param  string   ...$roles 許可するロールの一覧（可変長引数）
     *                            例: 'admin' または 'admin', 'member' のように複数指定可
     * @return Response
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        // =============================================
        // ① ログイン確認
        // =============================================
        // ログインしていない場合は、ログイン画面にリダイレクト
        if (!Auth::check()) {
            return redirect()->route('login')
                ->with('error', 'ログインが必要です');
        }

        // =============================================
        // ② ロール確認
        // =============================================
        // ログインしているユーザーのロールを取得
        $user = Auth::user();

        // in_array() : 配列の中に特定の値が含まれているか確認する
        // $user->role が許可されたロール一覧 ($roles) に含まれているかチェック
        if (!in_array($user->role, $roles)) {
            // 許可されていないロールでアクセスしようとした場合

            // 不正アクセスの試みをログに記録（デバッグ・セキュリティ監視用）
            \Log::warning('不正なロールでのアクセス試行', [
                'user_id'        => $user->id,
                'user_role'      => $user->role,
                'required_roles' => $roles,
                'url'            => $request->url(),
            ]);

            // ロールに応じた正しい画面にリダイレクト
            // 「あなたはこのページを見る権限がありません」というメッセージと共に
            return redirect($user->getRedirectUrlByRole())
                ->with('error', 'このページへのアクセス権限がありません');
        }

        // =============================================
        // ③ 通過（アクセス許可）
        // =============================================
        // ロールが一致した場合は、リクエストをそのまま次の処理へ渡す
        return $next($request);
    }
}
