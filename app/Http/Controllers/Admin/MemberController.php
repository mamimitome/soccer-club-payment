<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * 管理者向け会員管理コントローラー
 *
 * 管理者（admin）が会員を一覧表示・追加・編集・削除するための
 * CRUD（作成・読取・更新・削除）機能を提供します。
 *
 * CRUD とは？
 * Create（作成）・Read（読取）・Update（更新）・Delete（削除）の頭文字で、
 * データ管理に必要な基本4操作のことです。
 *
 * アクセス制限：
 * routes/web.php で middleware(['auth', 'role:admin']) を設定しているため、
 * 管理者（admin）のみがこのコントローラーにアクセスできます。
 */
class MemberController extends Controller
{
    /**
     * 会員一覧を表示する
     *
     * 検索キーワード（名前・メール）で絞り込みができます。
     * admin（管理者自身）は一覧に表示しません。
     *
     * @param  \Illuminate\Http\Request  $request  HTTPリクエスト（検索パラメーターを含む）
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // 検索キーワードをリクエストから取得する
        // ?search=xxx のようにURLパラメーターで渡される
        $search = $request->input('search', '');

        // ユーザー一覧を取得するクエリを組み立てる
        // admin（管理者）は管理対象外のため除外する
        $query = User::where('role', '!=', 'admin')
            ->withCount([
                // 今月の決済成功件数を一緒に取得（支払い状況の判定に使う）
                // withCount : リレーション先のレコード数を xxx_count というカラムで取得できる
                'payments as succeeded_payments_count' => function ($q) {
                    $q->where('status', 'succeeded')
                      ->whereMonth('paid_at', now()->month)
                      ->whereYear('paid_at', now()->year);
                }
            ]);

        // 検索キーワードがある場合は名前またはメールで絞り込む
        if ($search !== '') {
            // LIKE検索 : %で囲むと「含む」検索になる
            // orWhere : OR条件（名前に含む OR メールに含む）
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', '%' . $search . '%')
                  ->orWhere('email', 'LIKE', '%' . $search . '%');
            });
        }

        // 新しい順に並べて15件ずつページネーション（ページ分割）する
        // paginate() : 大量データを複数ページに分割して表示するLaravelの機能
        $members = $query->latest()->paginate(15);

        // ビュー（resources/views/admin/members/index.blade.php）にデータを渡す
        return view('admin.members.index', compact('members', 'search'));
    }

    /**
     * 会員追加フォームを表示する
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        // 追加フォームのビューを表示する（データは不要）
        return view('admin.members.create');
    }

    /**
     * 新しい会員をデータベースに保存する
     *
     * フォームから送信されたデータをバリデーション（入力検証）してから保存します。
     *
     * @param  \Illuminate\Http\Request  $request  フォームから送信されたデータ
     * @return \Illuminate\Http\RedirectResponse  保存後に一覧画面へリダイレクト
     */
    public function store(Request $request)
    {
        // バリデーション（入力値の検証）
        // validate() : ルールに違反があれば自動的にフォームに戻り、エラーを表示する
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            // email: 必須・メール形式・usersテーブル内で重複不可
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            // パスワード: 必須・8文字以上・確認欄と一致（confirmed: password_confirmationフィールドと比較）
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            // role: member（正会員）またはvisitor（ビジター）のみ許可
            'role'     => ['required', Rule::in(['member', 'visitor'])],
            // phone: 任意・最大20文字
            'phone'    => ['nullable', 'string', 'max:20'],
        ], [
            // バリデーションエラーメッセージの日本語化
            'name.required'      => '名前は必須です',
            'name.max'           => '名前は255文字以内で入力してください',
            'email.required'     => 'メールアドレスは必須です',
            'email.email'        => '正しいメールアドレス形式で入力してください',
            'email.unique'       => 'このメールアドレスは既に使用されています',
            'password.required'  => 'パスワードは必須です',
            'password.min'       => 'パスワードは8文字以上で入力してください',
            'password.confirmed' => 'パスワードと確認用パスワードが一致しません',
            'role.required'      => '役割は必須です',
            'role.in'            => '役割は「正会員」または「ビジター」を選択してください',
            'phone.max'          => '電話番号は20文字以内で入力してください',
        ]);

        // バリデーション通過後、ユーザーをデータベースに保存する
        // Hash::make() : パスワードを安全なハッシュ値に変換して保存する
        // （ハッシュ化とは：元に戻せない一方向の変換で、パスワードを安全に保存する方法）
        User::create([
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role'     => $validated['role'],
            'phone'    => $validated['phone'] ?? null,
        ]);

        // 保存後は一覧画面にリダイレクトして成功メッセージを表示する
        // session()->flash() : 次のリクエストだけ表示される一時的なメッセージ（フラッシュメッセージ）
        return redirect()
            ->route('admin.members.index')
            ->with('message', '会員を追加しました');
    }

    /**
     * 会員編集フォームを表示する
     *
     * @param  \App\Models\User  $member  編集対象のユーザー（ルートモデルバインディングで自動取得）
     * @return \Illuminate\View\View
     */
    public function edit(User $member)
    {
        // 管理者アカウントは編集不可にする（セキュリティのため）
        if ($member->isAdmin()) {
            return redirect()
                ->route('admin.members.index')
                ->with('error', '管理者アカウントは編集できません');
        }

        // 編集フォームのビューに対象ユーザーのデータを渡す
        return view('admin.members.edit', compact('member'));
    }

    /**
     * 会員情報を更新する
     *
     * パスワード欄が空の場合は、パスワードを変更しません。
     *
     * @param  \Illuminate\Http\Request  $request  フォームから送信されたデータ
     * @param  \App\Models\User  $member  更新対象のユーザー
     * @return \Illuminate\Http\RedirectResponse  更新後に一覧画面へリダイレクト
     */
    public function update(Request $request, User $member)
    {
        // 管理者アカウントは変更不可にする
        if ($member->isAdmin()) {
            return redirect()
                ->route('admin.members.index')
                ->with('error', '管理者アカウントは変更できません');
        }

        // バリデーションルールを組み立てる
        // メールアドレスの重複チェックで「このユーザー自身は除外」する必要がある
        // Rule::unique()->ignore($member->id) : 自分のIDは重複チェックから除外
        $validated = $request->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($member->id)
            ],
            // パスワード: 任意（空欄ならスキップ）・入力する場合は8文字以上
            // nullable : 空欄を許可する
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', Rule::in(['member', 'visitor'])],
            'phone'    => ['nullable', 'string', 'max:20'],
        ], [
            'name.required'      => '名前は必須です',
            'email.required'     => 'メールアドレスは必須です',
            'email.email'        => '正しいメールアドレス形式で入力してください',
            'email.unique'       => 'このメールアドレスは既に使用されています',
            'password.min'       => 'パスワードは8文字以上で入力してください',
            'password.confirmed' => 'パスワードと確認用パスワードが一致しません',
            'role.required'      => '役割は必須です',
            'role.in'            => '役割は「正会員」または「ビジター」を選択してください',
        ]);

        // 更新データを組み立てる
        $updateData = [
            'name'  => $validated['name'],
            'email' => $validated['email'],
            'role'  => $validated['role'],
            'phone' => $validated['phone'] ?? null,
        ];

        // パスワードが入力されている場合のみ更新する
        // 空欄のパスワードを保存すると既存のパスワードが消えてしまうため
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        // データベースを更新する
        $member->update($updateData);

        // 更新後は一覧画面にリダイレクトして成功メッセージを表示する
        return redirect()
            ->route('admin.members.index')
            ->with('message', '会員情報を更新しました');
    }

    /**
     * 会員を削除する
     *
     * 削除前の確認ダイアログはフロントエンド（JavaScript）で表示します。
     * このメソッドでは実際の削除処理を行います。
     *
     * @param  \App\Models\User  $member  削除対象のユーザー
     * @return \Illuminate\Http\RedirectResponse  削除後に一覧画面へリダイレクト
     */
    public function destroy(User $member)
    {
        // 管理者アカウントは削除不可にする（自分自身を削除するリスクを防ぐ）
        if ($member->isAdmin()) {
            return redirect()
                ->route('admin.members.index')
                ->with('error', '管理者アカウントは削除できません');
        }

        // 会員名を削除前に記憶しておく（成功メッセージで使うため）
        $name = $member->name;

        // データベースから削除する
        // 関連する決済履歴は外部キー制約（CASCADE）で自動削除される
        $member->delete();

        // 削除後は一覧画面にリダイレクトして成功メッセージを表示する
        return redirect()
            ->route('admin.members.index')
            ->with('message', "{$name} さんを削除しました");
    }
}
