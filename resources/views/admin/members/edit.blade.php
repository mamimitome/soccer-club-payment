{{--
    会員編集フォーム画面（管理者専用）

    管理者がこの画面から既存会員の情報を編集します。
    パスワード欄を空欄のままにすると、パスワードは変更されません。
    保存後は会員一覧画面に戻ります。
--}}
@extends('layouts.app')

@section('title', '会員編集 - サッカークラブ管理システム')

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- パンくずリスト --}}
    <div class="mb-6">
        <nav class="flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('admin.members.index') }}" class="hover:text-gray-900 transition-colors">会員管理</a>
            <span class="text-gray-300">/</span>
            <span class="text-gray-900 font-medium">{{ $member->name }}</span>
        </nav>
    </div>

    {{-- フォームカード --}}
    <div class="bg-white rounded-xl border border-gray-200">

        {{-- カードヘッダー --}}
        <div class="px-6 py-5 border-b border-gray-200">
            <h1 class="text-base font-semibold text-gray-900">会員情報を編集</h1>
            <p class="mt-1 text-sm text-gray-500">
                パスワードを変更しない場合は、パスワード欄を空欄のままにしてください
            </p>
        </div>

        {{-- =============================================
             編集フォーム
             ============================================= --}}
        {{--
            @method('PUT') : フォームのPOSTをPUTとして扱う
            なぜ PUT を使うの？
            HTTPメソッドには役割があります。
            GET: データを取得する
            POST: 新しいデータを作成する
            PUT: 既存のデータを更新する
            DELETE: データを削除する
            HTMLフォームはGETとPOSTしか対応していないため、
            @method('PUT') でPUTを指定します（Laravelが処理してくれる）
        --}}
        <form method="POST" action="{{ route('admin.members.update', $member) }}" class="px-6 py-5 space-y-5">
            @csrf
            @method('PUT')

            {{-- 名前 --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">
                    名前 <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    {{-- old('name', $member->name) : エラー後は入力値を、初回は現在の値を表示 --}}
                    value="{{ old('name', $member->name) }}"
                    class="w-full px-4 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent
                        {{ $errors->has('name') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}"
                >
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
                    value="{{ old('email', $member->email) }}"
                    class="w-full px-4 py-2.5 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent
                        {{ $errors->has('email') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}"
                >
                @error('email')
                    <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- パスワード（任意） --}}
            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                    新しいパスワード
                    <span class="ml-1 text-xs font-normal text-gray-400">変更する場合のみ入力</span>
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="空欄のままにすると変更されません"
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
                    新しいパスワード（確認用）
                </label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    placeholder="パスワードを変更する場合のみ入力"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                >
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
                    {{--
                        old('role', $member->role) :
                        - バリデーションエラー後は送信した値を使う
                        - 初回表示時は現在のロールを使う
                    --}}
                    <option value="member"  {{ old('role', $member->role) === 'member'  ? 'selected' : '' }}>正会員（月謝 9,000円/月）</option>
                    <option value="visitor" {{ old('role', $member->role) === 'visitor' ? 'selected' : '' }}>ビジター（都度払い 2,500円/回）</option>
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
                    value="{{ old('phone', $member->phone) }}"
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

                {{-- 更新ボタン --}}
                <button type="submit"
                    class="px-6 py-2.5 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                    変更を保存する
                </button>
            </div>
        </form>
    </div>

    {{-- =============================================
         アカウント情報（読み取り専用）
         ============================================= --}}
    <div class="mt-4 bg-gray-50 rounded-xl border border-gray-200 px-6 py-4">
        <h2 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-3">アカウント情報</h2>
        <div class="grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="text-gray-400">入会日：</span>
                <span class="text-gray-700">{{ $member->created_at->format('Y年m月d日') }}</span>
            </div>
            <div>
                <span class="text-gray-400">ユーザーID：</span>
                <span class="text-gray-700">#{{ $member->id }}</span>
            </div>
        </div>
    </div>

</div>

@endsection
