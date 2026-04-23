<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * usersテーブルにrole（役割）カラムを追加するマイグレーション
 *
 * マイグレーションとは？
 * データベースのテーブル構造をバージョン管理するための仕組みです。
 * Gitでコードを管理するように、データベースの変更履歴を管理できます。
 * `php artisan migrate` コマンドで実行します。
 */
return new class extends Migration
{
    /**
     * マイグレーションを実行する（テーブルに変更を加える）
     *
     * up() メソッドは `php artisan migrate` を実行したときに動きます。
     * ここにテーブルへの追加・変更処理を書きます。
     */
    public function up(): void
    {

        //既存のテーブルに「列を追加」
        Schema::table('users', function (Blueprint $table) {
            /**
             * roleカラムの追加
             *
             * enum型とは？
             * あらかじめ決めた値しか入れられない型です。
             * 例えば 'admin', 'member', 'visitor' 以外の値は入れられません。
             * これにより、不正な値がデータベースに入るのを防げます。
             *
             * 各ロールの意味：
             * - admin   : 管理者（クラブスタッフ、すべての操作が可能）
             * - member  : 正会員（月謝9,000円、クレジットカード自動引き落とし）
             * - visitor : ビジター（都度払い2,500円、参加のたびに決済）
             *
             * default('member') : カラムのデフォルト値を 'member' に設定
             * after('password') : passwordカラムの直後に配置（見やすくするため）
             */
            $table->enum('role', ['admin', 'member', 'visitor'])
                ->default('member')
                ->after('password')
                ->comment('ユーザーの役割: admin=管理者, member=正会員(月謝9000円), visitor=ビジター(都度払い2500円)');

            /**
             * phone（電話番号）カラムの追加
             *
             * nullable() とは？
             * カラムにNULL（空の値）を許可するという意味です。
             * 電話番号は必須ではないため、登録しなくてもOKにしています。
             *
             * string型 : テキストを保存する型。電話番号は数字ですが、
             * ハイフン(-)が含まれるためstring型にしています。
             */
            $table->string('phone', 20)
                ->nullable()
                ->after('role')
                ->comment('電話番号（任意）');

            /**
             * stripe_customer_id（StripeのカスタマーID）カラムの追加
             *
             * Stripeで決済を行うと、顧客ごとに一意のIDが発行されます。
             * このIDを保存しておくことで、2回目以降の決済時に
             * カード情報を再入力せずに決済できるようになります。
             * 例: "cus_NffrFeUfNV2Hib"
             */
            $table->string('stripe_customer_id')
                ->nullable()
                ->after('phone')
                ->comment('StripeのカスタマーID（決済情報の紐付けに使用）');
        });
    }

    /**
     * マイグレーションを元に戻す（テーブルの変更を取り消す）
     *
     * down() メソッドは `php artisan migrate:rollback` を実行したときに動きます。
     * up() で行った変更を元に戻す処理を書きます。
     * up() と逆の操作を書くのがルールです。
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // up()で追加したカラムを削除する
            // dropColumn()は複数カラムをまとめて削除できます
            $table->dropColumn(['role', 'phone', 'stripe_customer_id']);
        });
    }
};
