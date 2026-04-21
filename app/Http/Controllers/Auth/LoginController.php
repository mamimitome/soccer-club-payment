<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * ログイン・ログアウトを管理するコントローラー
 *
 * コントローラーとは？
 * ユーザーのリクエスト（ブラウザからのアクセス）を受け取り、
 * 必要な処理をして画面（View）を返す「橋渡し役」です。
 *
 * このコントローラーが担当する処理：
 * 1. ログイン画面の表示（show メソッド）
 * 2. ログイン処理（login メソッド）
 * 3. ログアウト処理（logout メソッド）
 */
class LoginController extends Controller
{
    /**
     * ログイン画面を表示する
     *
     * GETリクエスト: ブラウザで /login にアクセスしたとき
     *
     * すでにログイン済みの場合は、ロールに応じた画面にリダイレクトします。
     * （ログイン済みのユーザーがログイン画面を開いた場合の処理）
     *
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function show()
    {
        // Auth::check() : ユーザーがログイン済みかどうかを確認する
        // ログイン済みなら true、未ログインなら false を返す
        if (Auth::check()) {
            // すでにログインしているので、ロールに応じた画面にリダイレクト
            return redirect($this->getRedirectUrl(Auth::user()));
        }

        // 未ログインなのでログイン画面を表示する
        // 'auth.login' は resources/views/auth/login.blade.php に対応
        return view('auth.login');
    }

    /**
     * ログイン処理を実行する
     *
     * POSTリクエスト: ログインフォームの「ログイン」ボタンを押したとき
     *
     * 処理の流れ：
     * 1. 入力値のバリデーション（検証）
     * 2. メールアドレスとパスワードで認証を試みる
     * 3. 成功 → ロールに応じた画面へリダイレクト
     * 4. 失敗 → エラーメッセージと共にログイン画面に戻す
     *
     * @param  Request  $request  フォームから送信されたデータ
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        // =============================================
        // デバッグ用：リクエスト情報をログに記録
        // =============================================
        // Railway のログ（Deployments → Logs）で確認できます。
        // 本番環境で500エラーが発生した場合、どのステップで失敗したかを特定するために使います。
        Log::info('[ログイン開始]', [
            // リクエストの送信元IPアドレス
            // TrustProxies 設定後は X-Forwarded-For ヘッダーから実際のIPが取れるはず
            'ip'         => $request->ip(),

            // https/http どちらで来ているかを確認（セッションCookieのSecureフラグに関係）
            'is_secure'  => $request->secure(),

            // セッションドライバーの種類（database / file など）
            // SESSION_DRIVER=database の場合、sessions テーブルが必要
            'session_driver' => config('session.driver'),

            // メールアドレス（デバッグ後は削除すること）
            'email'      => $request->input('email'),
        ]);

        try {
            // =============================================
            // バリデーション（入力値の検証）
            // =============================================
            // ユーザーが入力した値が正しい形式かチェックします。
            // バリデーションに失敗すると、自動的にエラーメッセージが表示されます。
            $request->validate([
                // email: 必須 / 文字列 / メールアドレス形式
                'email'    => ['required', 'string', 'email'],
                // password: 必須 / 文字列
                'password' => ['required', 'string'],
            ], [
                // エラーメッセージを日本語にカスタマイズ
                'email.required'    => 'メールアドレスを入力してください',
                'email.email'       => 'メールアドレスの形式で入力してください',
                'password.required' => 'パスワードを入力してください',
            ]);

            Log::info('[ログイン] バリデーション通過');

            // =============================================
            // 認証処理
            // =============================================
            // Auth::attempt() は以下の処理を一括で行います：
            // 1. メールアドレスでユーザーを検索
            // 2. 入力されたパスワードと保存済みのハッシュを比較
            // 3. 一致すればセッションにログイン情報を保存
            //
            // 'remember' => true の場合、「ログインを保持」（ブラウザを閉じても維持）
            $credentials = $request->only('email', 'password');
            $remember    = $request->boolean('remember'); // チェックボックスの値

            Log::info('[ログイン] Auth::attempt 実行前');

            $result = Auth::attempt($credentials, $remember);

            Log::info('[ログイン] Auth::attempt 実行後', ['result' => $result]);

            if ($result) {
                // =============================================
                // ログイン成功
                // =============================================

                Log::info('[ログイン] セッション再生成前');

                // セッション固定攻撃（Session Fixation Attack）を防ぐため
                // ログイン後はセッションIDを再生成します。
                // これはセキュリティの基本的な対策です。
                $request->session()->regenerate();

                Log::info('[ログイン] セッション再生成完了');

                // ログインしたユーザーを取得
                $user = Auth::user();

                Log::info('[ログイン] 認証成功 → リダイレクト', [
                    'user_id'  => $user->id,
                    'role'     => $user->role,
                    'redirect' => $this->getRedirectUrl($user),
                ]);

                // ロールに応じた画面にリダイレクト
                // 例: admin → /admin/dashboard
                return redirect($this->getRedirectUrl($user));
            }

            Log::info('[ログイン] 認証失敗（メール or パスワードが違う）');

            // =============================================
            // ログイン失敗
            // =============================================
            // withErrors() : エラーメッセージを次の画面に渡す
            // onlyInput()  : 入力値を保持（パスワードは除く）
            // ※セキュリティのため「どちらが間違っているか」は教えない
            return back()
                ->withErrors([
                    'email' => 'メールアドレスまたはパスワードが正しくありません',
                ])
                ->onlyInput('email'); // メールアドレスだけ再入力状態にする（パスワードはクリア）

        } catch (\Throwable $e) {
            // =============================================
            // 予期しない例外のキャッチ
            // =============================================
            // Auth::attempt() や session()->regenerate() など、
            // 各ステップで例外が発生した場合にここで捕捉します。
            //
            // よくある原因：
            // - sessions テーブルが存在しない（SESSION_DRIVER=database の場合）
            // - DB 接続が確立できない
            // - APP_KEY が設定されていない（セッション暗号化に必要）
            Log::error('[ログイン] 例外発生', [
                'class'   => get_class($e),      // 例外クラス名（例: PDOException）
                'message' => $e->getMessage(),    // エラーメッセージ
                'file'    => $e->getFile(),       // 発生ファイル
                'line'    => $e->getLine(),       // 発生行番号
                'trace'   => $e->getTraceAsString(), // スタックトレース
            ]);

            // 500エラーとして再スローする
            // （bootstrap/app.php の withExceptions でも記録されます）
            throw $e;
        }
    }

    /**
     * ログアウト処理を実行する
     *
     * POSTリクエスト: ログアウトボタンを押したとき
     *
     * 処理の流れ：
     * 1. セッションからログイン情報を削除
     * 2. セッションIDを再生成（セキュリティ対策）
     * 3. ログイン画面にリダイレクト
     *
     * @param  Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function logout(Request $request)
    {
        // セッションからログイン情報を削除する
        Auth::logout();

        // セッションを無効化して、セッションIDを新しく生成する
        // これにより、ログアウト後に古いセッションIDが悪用されるのを防ぐ
        $request->session()->invalidate();
        $request->session()->regenerateToken(); // CSRFトークンも再生成

        // ログイン画面にリダイレクト
        return redirect()->route('login')
            ->with('message', 'ログアウトしました');
    }

    // =============================================
    // プライベートメソッド（内部処理用）
    // =============================================

    /**
     * ロールに応じたリダイレクト先URLを返す（内部ヘルパー）
     *
     * User モデルの getRedirectUrlByRole() を呼び出しているだけですが、
     * コントローラー内でロジックを確認しやすいようにラップしています。
     *
     * @param  \App\Models\User  $user
     * @return string
     */
    private function getRedirectUrl($user): string
    {
        return $user->getRedirectUrlByRole();
    }
}
