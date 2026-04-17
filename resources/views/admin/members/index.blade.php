{{--
    会員一覧画面（管理者専用）

    このページで管理者は以下のことができます：
    - 全会員（正会員・ビジター）の一覧を確認する
    - 名前またはメールアドレスで会員を検索する
    - 各会員の今月の支払い状況を確認する
    - 会員の追加・編集・削除画面へ移動する
--}}
@extends('layouts.app')

@section('title', '会員管理 - サッカークラブ管理システム')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ページヘッダー --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">会員管理</h1>
            <p class="mt-1 text-sm text-gray-500">正会員・ビジターの追加・編集・削除ができます</p>
        </div>
        {{-- 会員追加ボタン --}}
        <a href="{{ route('admin.members.create') }}"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            会員を追加
        </a>
    </div>

    {{-- =============================================
         検索フォーム
         ============================================= --}}
    <div class="bg-white rounded-xl border border-gray-200 p-4 mb-6">
        {{-- GET メソッドで検索（URLに ?search=xxx が付く） --}}
        <form method="GET" action="{{ route('admin.members.index') }}" class="flex gap-3">
            <div class="flex-1">
                <input
                    type="text"
                    name="search"
                    value="{{ $search }}"
                    placeholder="名前またはメールアドレスで検索..."
                    class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                >
            </div>
            <button type="submit"
                class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-lg hover:bg-gray-700 transition-colors">
                検索
            </button>
            {{-- 検索をリセットするボタン（検索中のときだけ表示） --}}
            @if($search)
            <a href="{{ route('admin.members.index') }}"
               class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                クリア
            </a>
            @endif
        </form>
    </div>

    {{-- =============================================
         会員一覧テーブル
         ============================================= --}}
    <div class="bg-white rounded-xl border border-gray-200">

        {{-- テーブルヘッダー --}}
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-sm font-semibold text-gray-900">
                会員一覧
                {{-- paginate() の total() : 総件数を返す --}}
                <span class="ml-2 text-xs font-normal text-gray-400">全{{ $members->total() }}名</span>
            </h2>
        </div>

        @if($members->isEmpty())
            {{-- 検索結果がゼロの場合のメッセージ --}}
            <div class="px-5 py-12 text-center">
                <svg class="mx-auto w-10 h-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-sm text-gray-400">
                    @if($search)
                        「{{ $search }}」に一致する会員が見つかりません
                    @else
                        会員が登録されていません
                    @endif
                </p>
            </div>
        @else
            <div class="overflow-x-auto">
                {{-- overflow-x-auto : 画面が狭いときに横スクロールできるようにする --}}
                <table class="min-w-full divide-y divide-gray-100">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">名前</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">メールアドレス</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">電話番号</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">役割</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">入会日</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">今月の支払い</th>
                            <th class="px-5 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wide">操作</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @foreach($members as $member)
                        <tr class="hover:bg-gray-50 transition-colors">

                            {{-- 名前 --}}
                            <td class="px-5 py-3.5 text-sm font-medium text-gray-900">
                                {{ $member->name }}
                            </td>

                            {{-- メールアドレス --}}
                            <td class="px-5 py-3.5 text-sm text-gray-600">
                                {{ $member->email }}
                            </td>

                            {{-- 電話番号 --}}
                            <td class="px-5 py-3.5 text-sm text-gray-600">
                                {{ $member->phone ?? '—' }}
                                {{-- ?? '—' : null の場合はダッシュを表示 --}}
                            </td>

                            {{-- 役割バッジ --}}
                            <td class="px-5 py-3.5">
                                @if($member->isMember())
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-club-green-100 text-club-green-800">
                                        正会員
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        ビジター
                                    </span>
                                @endif
                            </td>

                            {{-- 入会日（アカウント作成日） --}}
                            <td class="px-5 py-3.5 text-sm text-gray-500">
                                {{ $member->created_at->format('Y/m/d') }}
                            </td>

                            {{-- 今月の支払い状況 --}}
                            {{-- succeeded_payments_count : withCount() で取得した今月の決済成功件数 --}}
                            <td class="px-5 py-3.5">
                                @if($member->isMember())
                                    {{-- 正会員は今月の月謝支払い状況を表示 --}}
                                    @if($member->succeeded_payments_count > 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-club-green-100 text-club-green-800">
                                            済
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                            未払い
                                        </span>
                                    @endif
                                @else
                                    {{-- ビジターは都度払いのため「—」で表示 --}}
                                    <span class="text-xs text-gray-400">都度払い</span>
                                @endif
                            </td>

                            {{-- 操作ボタン --}}
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-2">

                                    {{-- 編集ボタン --}}
                                    <a href="{{ route('admin.members.edit', $member) }}"
                                       class="text-xs text-gray-600 border border-gray-300 px-3 py-1 rounded hover:bg-gray-100 transition-colors">
                                        編集
                                    </a>

                                    {{-- 削除ボタン（確認ダイアログ付き） --}}
                                    {{--
                                        なぜフォームを使うの？
                                        HTMLのリンク（<a>タグ）はGETリクエストしか送れません。
                                        削除にはDELETEメソッドが適切なので、フォームを使います。
                                        @method('DELETE') : フォームのPOSTをDELETEとして扱う指示（HTMLはDELETE未対応のため）
                                    --}}
                                    <form
                                        method="POST"
                                        action="{{ route('admin.members.destroy', $member) }}"
                                        class="inline"
                                        {{-- onsubmit : フォーム送信前に確認ダイアログを表示 --}}
                                        onsubmit="return confirm('{{ $member->name }} さんを削除しますか？\nこの操作は取り消せません。')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="text-xs text-red-600 border border-red-200 px-3 py-1 rounded hover:bg-red-50 transition-colors">
                                            削除
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- =============================================
                 ページネーション（ページ分割のナビゲーション）
                 ============================================= --}}
            @if($members->hasPages())
            {{-- hasPages() : ページが2ページ以上ある場合のみ表示 --}}
            <div class="px-5 py-4 border-t border-gray-100">
                {{-- appends() : 検索キーワードをページ切り替え時も保持する --}}
                {{ $members->appends(['search' => $search])->links() }}
            </div>
            @endif
        @endif
    </div>

    {{-- ダッシュボードへ戻るリンク --}}
    <div class="mt-6">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">
            ← ダッシュボードへ戻る
        </a>
    </div>

</div>

@endsection
