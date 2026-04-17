<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * paymentsテーブル（決済履歴）を作成するマイグレーション
 *
 * このテーブルの役割：
 * 会員・ビジターが行ったすべての決済の記録を保存します。
 * 「いつ、誰が、いくら、どのような理由で支払ったか」を管理します。
 *
 * 料金設定：
 * - 正会員  : 月謝 9,000円（毎月自動引き落とし）
 * - ビジター: 都度払い 2,500円（参加のたびに決済）
 */
return new class extends Migration
{
    /**
     * マイグレーションを実行する（paymentsテーブルを作成する）
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            /**
             * id: 主キー（プライマリキー）
             *
             * 主キーとは？
             * テーブル内の各レコード（行）を一意に識別するためのカラムです。
             * id()メソッドは自動連番（1, 2, 3...）のunsignedBigIntegerを作成します。
             */
            $table->id();

            /**
             * user_id: どのユーザーの決済か
             *
             * foreignId()は他テーブルのidを参照する外部キーを作成します。
             * constrained()により、usersテーブルのidカラムと紐付けられます。
             * cascadeOnDelete()は、ユーザーが削除されたとき決済記録も削除します。
             *
             * 外部キーとは？
             * テーブル同士を関連付けるための仕組みです。
             * 「このuser_idはusersテーブルのidを参照している」と宣言することで、
             * 存在しないuser_idが入るのを防ぎます。
             */
            $table->foreignId('user_id')
                  ->constrained('users')
                  ->cascadeOnDelete()
                  ->comment('決済したユーザーのID（usersテーブルのidを参照）');

            /**
             * payment_type: 決済の種類
             *
             * monthly_fee  : 月謝（正会員の月額9,000円）
             * visitor_fee  : ビジター料金（都度払い2,500円）
             * refund       : 返金処理
             */
            $table->enum('payment_type', ['monthly_fee', 'visitor_fee', 'refund'])
                  ->comment('決済種別: monthly_fee=月謝, visitor_fee=ビジター料金, refund=返金');

            /**
             * amount: 決済金額（円）
             *
             * unsignedInteger()は0以上の整数型です。
             * 金額がマイナスになるのを防ぎます。
             * 返金の場合は status で管理します。
             *
             * なぜDecimal型（小数）を使わないの？
             * 日本円は小数点以下がないため、Integer型で十分です。
             * Stripeも日本円は最小通貨単位（円）で扱います。
             */
            $table->unsignedInteger('amount')
                  ->comment('決済金額（円）: 月謝=9000, ビジター=2500');

            /**
             * status: 決済のステータス（状態）
             *
             * pending   : 処理中（決済リクエストを送った直後）
             * succeeded : 決済成功
             * failed    : 決済失敗（カードエラーなど）
             * refunded  : 返金済み
             */
            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded'])
                  ->default('pending')
                  ->comment('決済状態: pending=処理中, succeeded=成功, failed=失敗, refunded=返金済み');

            /**
             * stripe_payment_intent_id: StripeのPaymentIntentID
             *
             * PaymentIntentとは？
             * Stripeで決済を行うたびに発行される一意のIDです。
             * 例: "pi_3OvxxxxxxxxxxxxxxxxxxxxO"
             *
             * このIDを保存しておくことで：
             * 1. 決済の詳細をStripeダッシュボードで確認できる
             * 2. 返金処理をStripe APIで実行できる
             * 3. 二重決済を防ぐ確認ができる
             *
             * nullable(): Stripe決済前はIDがないためNULLを許可
             */
            $table->string('stripe_payment_intent_id')
                  ->nullable()
                  ->unique()
                  ->comment('StripeのPaymentIntentID（決済ごとに発行される一意のID）');

            /**
             * stripe_charge_id: StripeのChargeID
             *
             * 決済が完了するとStripeから発行されるCharge IDです。
             * 例: "ch_3OvxxxxxxxxxxxxxxxxxxxxO"
             * 返金処理などに使用します。
             */
            $table->string('stripe_charge_id')
                  ->nullable()
                  ->comment('StripeのChargeID（決済完了後に発行）');

            /**
             * billing_month: 請求対象月（正会員の月謝用）
             *
             * date型はYYYY-MM-DD形式で保存します。
             * 月謝の場合、どの月の分の支払いかを記録します。
             * 例: 2026-04-01（4月分の月謝）
             *
             * nullable(): ビジター料金には請求月がないためNULLを許可
             */
            $table->date('billing_month')
                  ->nullable()
                  ->comment('請求対象月（月謝の場合: 例 2026-04-01 = 4月分）');

            /**
             * paid_at: 実際に決済が完了した日時
             *
             * timestamp型はYYYY-MM-DD HH:MM:SS形式で保存します。
             * Stripeから成功通知を受け取ったときにこの値を設定します。
             * nullable(): 決済完了前はNULL
             */
            $table->timestamp('paid_at')
                  ->nullable()
                  ->comment('決済完了日時（Stripeから成功通知を受けた日時）');

            /**
             * failure_reason: 決済失敗の理由
             *
             * 決済が失敗した場合、Stripeから返ってくるエラーメッセージを保存します。
             * 例: "カードの有効期限が切れています"
             * nullable(): 成功した場合はNULL
             */
            $table->string('failure_reason')
                  ->nullable()
                  ->comment('決済失敗の理由（例: カードの有効期限切れ）');

            /**
             * notes: メモ・備考
             *
             * 管理者が手動で記録を残したい場合に使用します。
             * text型は長いテキストを保存できます（string型より長い文字数対応）。
             */
            $table->text('notes')
                  ->nullable()
                  ->comment('管理者メモ・備考（任意）');

            /**
             * created_at: レコード作成日時
             * updated_at: レコード更新日時
             *
             * timestamps()は上記2カラムを自動で作成します。
             * Laravelが自動的に値を設定・更新してくれます。
             */
            $table->timestamps();

            // -----------------------------------------
            // インデックスの設定
            // -----------------------------------------
            // インデックスとは？
            // 本の「索引（インデックス）」と同じ概念です。
            // インデックスを付けると、大量のデータから素早く検索できます。
            // よく検索・絞り込みに使うカラムに設定します。

            // ユーザーIDで検索する場合のインデックス（例: 特定会員の支払い履歴一覧）
            $table->index('user_id');

            // 決済ステータスで検索する場合のインデックス（例: 失敗した決済一覧）
            $table->index('status');

            // 請求月で検索する場合のインデックス（例: 2026年4月の請求一覧）
            $table->index('billing_month');

            // 決済種別で検索する場合のインデックス
            $table->index('payment_type');
        });
    }

    /**
     * マイグレーションを元に戻す（paymentsテーブルを削除する）
     *
     * `php artisan migrate:rollback` で実行されます。
     * dropIfExists() はテーブルが存在する場合のみ削除します（エラー防止）。
     */
    public function down(): void
    {
        // paymentsテーブルを削除する
        // 注意: このテーブルを削除すると、すべての決済記録が失われます！
        Schema::dropIfExists('payments');
    }
};
