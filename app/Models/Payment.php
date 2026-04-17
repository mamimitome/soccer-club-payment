<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 決済履歴モデル
 *
 * paymentsテーブルと対応するモデルです。
 * 会員・ビジターの決済記録を管理します。
 *
 * 料金設定（CLAUDE.mdより）：
 * - 正会員  : 月謝 9,000円
 * - ビジター: 都度払い 2,500円
 */
class Payment extends Model
{
    /**
     * 一括代入を許可するカラム
     *
     * Payment::create([...]) で一度にデータを保存できるカラムの一覧です。
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',                   // 誰の決済か
        'payment_type',              // 種別（monthly_fee / visitor_fee / refund）
        'amount',                    // 金額（円）
        'status',                    // 状態（pending / succeeded / failed / refunded）
        'stripe_payment_intent_id',  // StripeのPaymentIntentID
        'stripe_charge_id',          // StripeのChargeID
        'billing_month',             // 請求対象月（正会員の月謝用）
        'paid_at',                   // 決済完了日時
        'failure_reason',            // 失敗理由
        'notes',                     // 管理者メモ
    ];

    /**
     * カラムの型変換（キャスト）設定
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // paid_at を Carbon オブジェクトに変換（日付操作が簡単になる）
            'paid_at'       => 'datetime',
            // billing_month を Carbon オブジェクトに変換
            'billing_month' => 'date',
            // amount を整数型に変換（小数点なし）
            'amount'        => 'integer',
        ];
    }

    // =============================================
    // リレーション
    // =============================================

    /**
     * この決済を行ったユーザーを取得する
     *
     * 1つの決済は1人のユーザーに紐付いている（多対1）
     * 使い方: $payment->user → そのユーザーを取得
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // =============================================
    // スコープ（よく使う絞り込み条件）
    // =============================================
    // スコープとは？
    // よく使うクエリ条件をメソッドとして定義しておく機能です。
    // Payment::succeeded()->get() のように呼び出せます。

    /**
     * 決済成功のレコードだけを取得するスコープ
     */
    public function scopeSucceeded($query)
    {
        return $query->where('status', 'succeeded');
    }

    /**
     * 決済失敗のレコードだけを取得するスコープ
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * 月謝のレコードだけを取得するスコープ
     */
    public function scopeMonthlyFee($query)
    {
        return $query->where('payment_type', 'monthly_fee');
    }

    /**
     * ビジター料金のレコードだけを取得するスコープ
     */
    public function scopeVisitorFee($query)
    {
        return $query->where('payment_type', 'visitor_fee');
    }
}
