<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        if (Auth::attempt($credentials, $remember)) {
            // =============================================
            // ログイン成功
            // =============================================

            // セッション固定攻撃（Session Fixation Attack）を防ぐため
            // ログイン後はセッションIDを再生成します。
            // これはセキュリティの基本的な対策です。
            $request->session()->regenerate();

            // ログインしたユーザーを取得
            $user = Auth::user();

            // ロールに応じた画面にリダイレクト
            // 例: admin → /admin/dashboard
            return redirect($this->getRedirectUrl($user));
        }

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
