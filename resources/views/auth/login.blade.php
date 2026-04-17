{{--
    ログイン画面

    @extends : 親レイアウト（layouts/app.blade.php）を継承する
    このファイルは @yield('content') の部分だけを書けばOK
--}}
@extends('layouts.app')

{{-- このページのタイトルを設定する --}}
@section('title', 'ログイン - サッカークラブ管理システム')

{{--
    @section('content') 〜 @endsection
    layouts/app.blade.php の @yield('content') に埋め込まれるコンテンツ
--}}
@section('content')

<div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">

    {{-- =============================================
         ロゴ・タイトルエリア
         ============================================= --}}
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        {{-- サッカーボールアイコン --}}
        <div class="flex justify-center">
            <div class="w-12 h-12 bg-club-green-600 rounded-xl flex items-center justify-center shadow-sm">
                <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="10" fill="none" stroke="currentColor" stroke-width="1.5"/>
                    <path d="M12 7l-2 6H5l4.5 3.27L8 22l4-2.9L16 22l-1.5-5.73L19 13h-5z" stroke="none"/>
                </svg>
            </div>
        </div>
        <h1 class="mt-4 text-center text-2xl font-bold tracking-tight text-gray-900">
            サッカークラブ
        </h1>
        <p class="mt-1 text-center text-sm text-gray-500">
            月謝管理システム
        </p>
    </div>

    {{-- =============================================
         ログインフォームカード
         ============================================= --}}
    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
        <div class="bg-white py-8 px-6 shadow-sm rounded-xl border border-gray-200 sm:px-10">

            <h2 class="mb-6 text-lg font-semibold text-gray-900">
                アカウントにログイン
            </h2>

            {{--
                エラーメッセージの表示
                バリデーションエラーや認証エラーがある場合に表示される
                $errors はLaravelが自動で用意する変数
            --}}
            @if($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-red-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                        </svg>
                        <ul class="ml-3 list-none">
                            {{-- $errors->all() : すべてのエラーメッセージを取得 --}}
                            @foreach($errors->all() as $error)
                                <li class="text-sm text-red-700">{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{--
                ログインフォーム

                action="{{ route('login') }}" : LoginController@login に送信
                method="POST" : GETではなくPOSTで送信（セキュリティのため）
            --}}
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                {{-- @csrf : CSRFトークンを埋め込む（必須のセキュリティ対策） --}}
                @csrf

                {{-- =====================
                     メールアドレス入力
                     ===================== --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                        メールアドレス
                    </label>
                    {{--
                        old('email') : バリデーションエラー後も入力値を保持する
                        :class="..." : エラーがある場合にボーダーを赤くする
                    --}}
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autocomplete="email"
                        required
                        value="{{ old('email') }}"
                        class="block w-full rounded-lg border px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400
                               focus:outline-none focus:ring-2 focus:ring-club-green-500 focus:border-transparent
                               transition-colors
                               {{ $errors->has('email') ? 'border-red-300 bg-red-50' : 'border-gray-300 bg-white' }}"
                        placeholder="example@soccer-club.com"
                    >
                </div>

                {{-- =====================
                     パスワード入力
                     ===================== --}}
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                        パスワード
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        required
                        class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400
                               focus:outline-none focus:ring-2 focus:ring-club-green-500 focus:border-transparent
                               transition-colors"
                        placeholder="パスワードを入力"
                    >
                </div>

                {{-- =====================
                     ログインを保持（Remember me）
                     ===================== --}}
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <input
                            id="remember"
                            name="remember"
                            type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 text-club-green-600
                                   focus:ring-club-green-500 cursor-pointer"
                        >
                        <label for="remember" class="text-sm text-gray-600 cursor-pointer">
                            ログイン状態を保持する
                        </label>
                    </div>
                </div>

                {{-- =====================
                     ログインボタン
                     ===================== --}}
                <button
                    type="submit"
                    class="w-full flex justify-center py-2.5 px-4 rounded-lg text-sm font-semibold
                           bg-club-green-600 text-white
                           hover:bg-club-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2
                           focus:ring-club-green-500
                           transition-colors duration-150 cursor-pointer"
                >
                    ログイン
                </button>
            </form>

            {{-- =============================================
                 ロール説明（開発中の確認用。本番では削除してください）
                 ============================================= --}}
            <div class="mt-6 pt-5 border-t border-gray-100">
                <p class="text-xs font-medium text-gray-400 mb-3">ロール別のログイン後の遷移先</p>
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-purple-100 text-purple-700 font-medium">admin</span>
                        <span>→ /admin/dashboard（管理ダッシュボード）</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-club-green-100 text-club-green-700 font-medium">member</span>
                        <span>→ /member/dashboard（会員ダッシュボード）</span>
                    </div>
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs bg-blue-100 text-blue-700 font-medium">visitor</span>
                        <span>→ /visitor/pay（ビジター決済画面）</span>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
