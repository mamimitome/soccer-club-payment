<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void // ← php artisan migrate を実行したときに動く
    {
        //usersテーブル
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });


        // ① パスワードリセット用テーブル
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary(); // メールアドレスがキー
            $table->string('token'); // リセット用の一時トークン
            $table->timestamp('created_at')->nullable();
        });

        // ② ログインセッション管理テーブル
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary(); // セッションID
            $table->foreignId('user_id')->nullable()->index(); // アクセス元IP
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable(); // ブラウザ情報
            $table->longText('payload'); // セッションデータ本体
            $table->integer('last_activity')->index(); // 最終アクセス日時
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void // ← php artisan migrate:rollback を実行したときに動く

    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
