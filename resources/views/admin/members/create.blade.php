{{--
    会員追加フォーム画面（管理者専用）

    管理者がこの画面から新しい会員（正会員・ビジター）を追加します。
    保存後は会員一覧画面に戻ります。
--}}
@extends('layouts.app')

@section('title', '会員追加 - サッカークラブ管理システム')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- パンくずリスト（現在地の表示） --}}
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('admin.members.index') }}" class="hover:text-gray-900 transition-colors">会員管理</a>
            <span class="text-gray-300">/</span>
            <span class="text-gray-900 font-medium">会員を追加</span>
        </nav>
    </div>

    {{-- フォームカード --}}
    <div class="bg-white rounded-xl border border-gray-200">

        {{-- カードヘッダー --}}
        <div class="px-6 py-5 border-b border-gray-200">
            <h1 class="text-base font-semibold text-gray-900">新しい会員を追加</h1>
            <p class="mt-1 text-sm text-gray-500">追加された会員はすぐにログインできます</p>
        </div>

        {{-- =============================================
             入力フォーム
             ============================================= --}}
        {{-- action: データの送信先（store メソッド） --}}
        {{-- method: POST でデータを送信する --}}
        <form method="POST" action="{{ route('admin.members.store') }}" class="px-6 py-5 space-y-5">
            @csrf
            {{-- @csrf : CSRF攻撃（フォームを偽サイトから送信する攻撃）を防ぐための隠しトークン --}}

            {{-- 名前 --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">
                    名前 <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    {{-- old('name') : バリデーションエラー後に入力値を保持する --}}
                    value="{{ old('name') }}"
                    placeholder="山田 太郎"
                    class="w-full px-4 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent
                        {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}"
                >
                {{-- エラーメッセージ表示 --}}
                @error('name')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- メールアドレス --}}
            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">
                    メールアドレス <span class="text-red-500">*</span>
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    placeholder="yamada@example.com"
                    class="w-full px-4 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent
                        {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}"
                >
                @error('email')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- パスワード --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                    パスワード <span class="text-red-500">*</span>
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="8文字以上"
                    class="w-full px-4 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent
                        {{ $errors->has('password') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}"
                >
                @error('password')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- パスワード（確認用） --}}
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">
                    パスワード（確認用） <span class="text-red-500">*</span>
                </label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    placeholder="もう一度入力"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                >
                <p class="mt-1.5 text-xs text-gray-400">上と同じパスワードを入力してください</p>
            </div>

            {{-- 役割（ロール） --}}
            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1.5">
                    役割 <span class="text-red-500">*</span>
                </label>
                <select
                    id="role"
                    name="role"
                    class="w-full px-4 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent
                        {{ $errors->has('role') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}"
                >
                    {{-- selected : 以前の選択値を保持する（バリデーションエラー後） --}}
                    <option value="">役割を選択してください</option>
                    <option value="member"  {{ old('role') === 'member'  ? 'selected' : '' }}>正会員（月謝 9,000円/月）</option>
                    <option value="visitor" {{ old('role') === 'visitor' ? 'selected' : '' }}>ビジター（都度払い 2,500円/回）</option>
                </select>
                @error('role')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 電話番号（任意） --}}
            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1.5">
                    電話番号
                    <span class="ml-1 text-xs font-normal text-gray-400">任意</span>
                </label>
                <input
                    type="tel"
                    id="phone"
                    name="phone"
                    value="{{ old('phone') }}"
                    placeholder="090-1234-5678"
                    class="w-full px-4 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent
                        {{ $errors->has('phone') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}"
                >
                @error('phone')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- =============================================
                 ボタンエリア
                 ============================================= --}}
            <div class="flex items-center justify-between pt-2">
                {{-- キャンセル（一覧に戻る） --}}
                <a href="{{ route('admin.members.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-900 transition-colors">
                    キャンセル
                </a>

                {{-- 保存ボタン --}}
                <button type="submit"
                    class="px-6 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                    会員を追加する
                </button>
            </div>
        </form>
    </div>

</div>

@endsection
