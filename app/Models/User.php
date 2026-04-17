<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

/**
 * ユーザーモデル
 *
 * モデルとは？
 * データベースのテーブル（usersテーブル）と対応するクラスです。
 * このクラスを使ってユーザーの情報を取得・保存・更新・削除できます。
 *
 * Authenticatable を継承することで、Laravelの認証機能が使えるようになります。
 * （ログイン・ログアウト・セッション管理など）
 */
class User extends Authenticatable
{
    /**
     * トレイト（Trait）の使用宣言
     *
     * トレイトとは？
     * 複数のクラスで共通して使いたい機能をまとめたものです。
     * useキーワードで取り込むだけで、その機能が使えるようになります。
     *
     * HasApiTokens  : Laravel SanctumのAPIトークン機能（ログイントークンの発行など）
     * HasFactory    : テスト用のダミーデータ生成機能
     * Notifiable    : メール通知など通知機能
     */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * 一括代入（マスアサインメント）を許可するカラム
     *
     * 一括代入とは？
     * User::create(['name' => '田中', 'email' => '...']) のように
     * 配列でまとめてデータを保存する方法です。
     *
     * fillable に書かれていないカラムは、一括代入では更新できません。
     * これはセキュリティのためで、意図しないカラムが書き換えられるのを防ぎます。
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',               // ユーザーの役割（admin/member/visitor）
        'phone',              // 電話番号
        'stripe_customer_id', // StripeのカスタマーID
    ];

    /**
     * JSONに変換するときに隠すカラム（APIレスポンスなどに含めない）
     *
     * なぜ隠すの？
     * パスワードのハッシュ値やトークンをフロントエンドに返してしまうと
     * セキュリティリスクになるため、自動的に除外します。
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * カラムの型変換（キャスト）設定
     *
     * キャストとは？
     * データベースから取り出した値を、自動的に別の型に変換する機能です。
     *
     * 例：
     * - email_verified_at は文字列で保存されていますが、
     *   取り出すと Carbon（日付操作クラス）のオブジェクトになります。
     * - password は自動的にbcryptでハッシュ化して保存されます。
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed', // 保存時に自動でハッシュ化
        ];
    }

    // =============================================
    // ロール（役割）判定メソッド
    // =============================================
    // 以下のメソッドを使うことで、コードが読みやすくなります。
    // 例: $user->isAdmin() → $user->role === 'admin' と同じ意味

    /**
     * 管理者かどうかを判定する
     *
     * @return bool true = 管理者, false = 管理者ではない
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * 正会員かどうかを判定する
     *
     * 正会員：月謝9,000円をクレジットカードで自動引き落とし
     *
     * @return bool true = 正会員, false = 正会員ではない
     */
    public function isMember(): bool
    {
        return $this->role === 'member';
    }

    /**
     * ビジターかどうかを判定する
     *
     * ビジター：都度払い2,500円
     *
     * @return bool true = ビジター, false = ビジターではない
     */
    public function isVisitor(): bool
    {
        return $this->role === 'visitor';
    }

    /**
     * ロールに応じたリダイレクト先URLを返す
     *
     * ログイン後にどの画面に遷移するかをここで定義します。
     * コントローラーでこのメソッドを呼ぶことで、
     * リダイレクト先の判定ロジックをモデルに集約できます。
     *
     * @return string リダイレクト先のURL
     */
    public function getRedirectUrlByRole(): string
    {
        return match ($this->role) {
            'admin'   => '/admin/dashboard',   // 管理者 → 管理ダッシュボード
            'member'  => '/member/dashboard',  // 正会員 → 会員ダッシュボード
            'visitor' => '/visitor/pay',        // ビジター → 決済画面
            default   => '/login',              // 不明なロール → ログイン画面に戻す
        };
    }

    /**
     * ロールの日本語表示名を返す
     *
     * 画面上でロールをわかりやすく表示するために使います。
     * 例: admin → "管理者"
     *
     * @return string ロールの日本語名
     */
    public function getRoleLabel(): string
    {
        return match ($this->role) {
            'admin'   => '管理者',
            'member'  => '正会員',
            'visitor' => 'ビジター',
            default   => '不明',
        };
    }

    // =============================================
    // リレーション（テーブル間の関連付け）
    // =============================================

    /**
     * このユーザーの決済履歴を取得する
     *
     * リレーションとは？
     * テーブル同士の関連を定義するものです。
     * 「1人のユーザーは複数の決済記録を持つ」という関係を表しています。
     * （1対多の関係 = hasMany）
     *
     * 使い方: $user->payments → そのユーザーの全決済記録を取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class);
    }
}
