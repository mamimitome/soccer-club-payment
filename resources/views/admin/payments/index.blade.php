{{-- 管理者 支払い状況一覧画面 --}}
@extends('layouts.app')

@section('title', '支払い状況一覧 - サッカークラブ管理システム')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- =============================================
         ページヘッダー（タイトル＋月ナビゲーション）
         ============================================= --}}
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">支払い状況一覧</h1>
            <p class="mt-1 text-sm text-gray-500">月謝・ビジター決済の状況を管理します</p>
        </div>

        {{-- 月ナビゲーション --}}
        {{-- 前月・翌月へ切り替えるボタン群 --}}
        <div class="flex items-center gap-3">
            {{-- 前月ボタン --}}
            {{-- route() : ルート名から URL を生成する --}}
            <a href="{{ route('admin.payments') }}?year={{ $prevMonth->year }}&month={{ $prevMonth->month }}"
               class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                {{-- ← 左矢印アイコン --}}
                <svg class="w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ $prevMonth->format('Y年n月') }}
            </a>

            {{-- 現在表示中の月（中央に大きく表示） --}}
            <span class="px-4 py-1.5 bg-gray-900 text-white text-sm font-semibold rounded-lg min-w-[120px] text-center">
                {{ $targetDate->format('Y年n月') }}
                {{-- 当月の場合は「（今月）」ラベルを追加 --}}
                @if($isCurrentMonth)
                    <span class="ml-1 text-xs font-normal opacity-75">今月</span>
                @endif
            </span>

            {{-- 翌月ボタン（当月の場合はグレーアウトして無効化） --}}
            @if($nextMonth)
                {{-- 翌月が存在する（過去月を表示中）場合はリンクを表示 --}}
                <a href="{{ route('admin.payments') }}?year={{ $nextMonth->year }}&month={{ $nextMonth->month }}"
                   class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50 transition-colors">
                    {{ $nextMonth->format('Y年n月') }}
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            @else
                {{-- 当月表示中のため翌月ボタンを無効化（未来月は存在しない） --}}
                <span class="inline-flex items-center px-3 py-1.5 border border-gray-200 rounded-lg text-sm text-gray-300 cursor-not-allowed">
                    翌月
                    <svg class="w-4 h-4 ml-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </span>
            @endif

            {{-- ダッシュボードに戻るボタン --}}
            <a href="{{ route('admin.dashboard') }}"
               class="inline-flex items-center gap-1.5 px-4 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                ダッシュボード
            </a>
        </div>
    </div>

    {{-- =============================================
         売上サマリーカード（4枚）
         ============================================= --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">

        {{-- 総売上 --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">総売上</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        ¥{{ number_format($totalRevenue) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">月謝 + ビジター</p>
                </div>
                <div class="p-2.5 bg-gray-100 rounded-lg">
                    <svg class="w-5 h-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- 月謝収入 --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">月謝収入</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        ¥{{ number_format($monthlyFeeRevenue) }}
                    </p>
                    {{-- 徴収率の表示：支払い済み人数 / 全正会員数 --}}
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $paidMemberCount }}人支払い済み（徴収率{{ $collectionRate }}%）
                    </p>
                </div>
                <div class="p-2.5 bg-club-green-100 rounded-lg">
                    <svg class="w-5 h-5 text-club-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- ビジター収入 --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">ビジター収入</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        ¥{{ number_format($visitorRevenue) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">
                        {{ $visitorPayments->count() }}件の決済
                    </p>
                </div>
                <div class="p-2.5 bg-blue-100 rounded-lg">
                    <svg class="w-5 h-5 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- 未払い件数 --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">月謝未払い</p>
                    <p class="mt-2 text-3xl font-bold {{ $unpaidMemberCount > 0 ? 'text-red-600' : 'text-gray-900' }}">
                        {{ number_format($unpaidMemberCount) }}
                        <span class="text-base font-medium">人</span>
                    </p>
                    {{-- 未払いがいる場合は機会損失額も表示 --}}
                    @if($unpaidMemberCount > 0)
                        <p class="mt-1 text-xs text-red-500">
                            未回収 ¥{{ number_format($unpaidMemberCount * config('stripe.prices.monthly_fee')) }}
                        </p>
                    @else
                        <p class="mt-1 text-xs text-club-green-600 font-medium">全員支払い済み</p>
                    @endif
                </div>
                <div class="p-2.5 {{ $unpaidMemberCount > 0 ? 'bg-red-100' : 'bg-club-green-100' }} rounded-lg">
                    <svg class="w-5 h-5 {{ $unpaidMemberCount > 0 ? 'text-red-600' : 'text-club-green-700' }}"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        @if($unpaidMemberCount > 0)
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        @else
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        @endif
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- =============================================
         正会員 月謝支払い状況テーブル
         ============================================= --}}
    <div class="bg-white rounded-xl border border-gray-200 mb-6">
        {{-- テーブルヘッダー --}}
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">正会員 月謝支払い状況</h2>
                <p class="mt-0.5 text-xs text-gray-500">未払い会員を上部に表示しています</p>
            </div>
            {{-- 支払い済み / 未払い の集計バッジ --}}
            <div class="flex items-center gap-2 text-xs">
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-club-green-100 text-club-green-800 font-medium">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    支払い済み {{ $paidMemberCount }}人
                </span>
                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full {{ $unpaidMemberCount > 0 ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-600' }} font-medium">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    未払い {{ $unpaidMemberCount }}人
                </span>
            </div>
        </div>

        @if($members->isEmpty())
            {{-- 正会員が1人もいない場合 --}}
            <div class="px-5 py-10 text-center">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <p class="text-sm text-gray-400">正会員が登録されていません</p>
            </div>
        @else
            <div class="overflow-x-auto">
                {{-- overflow-x-auto : 画面が小さいとき横スクロールを許可 --}}
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">会員名</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">メールアドレス</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">電話番号</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">支払い状態</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">支払い日時</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {{-- @foreach : コレクションをループして各正会員の行を表示 --}}
                        @foreach($members as $member)
                        {{-- 未払い会員の行は背景をうっすら赤にして目立たせる --}}
                        <tr class="{{ $member->isPaid ? 'hover:bg-gray-50' : 'bg-red-50 hover:bg-red-100' }} transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    {{-- アバター代わりの丸バッジ（名前の頭文字） --}}
                                    <div class="w-7 h-7 rounded-full {{ $member->isPaid ? 'bg-club-green-100 text-club-green-800' : 'bg-red-100 text-red-800' }} flex items-center justify-center text-xs font-bold flex-shrink-0">
                                        {{-- mb_substr() : 日本語対応の文字列切り出し（1文字目を取得） --}}
                                        {{ mb_substr($member->name, 0, 1) }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">{{ $member->name }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-600">
                                {{ $member->email }}
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-600">
                                {{ $member->phone ?? '-' }}
                            </td>
                            <td class="px-5 py-3.5">
                                @if($member->isPaid)
                                    {{-- 支払い済みバッジ --}}
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-club-green-100 text-club-green-800">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        支払い済み
                                    </span>
                                @else
                                    {{-- 未払いバッジ --}}
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                        </svg>
                                        未払い
                                    </span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-500">
                                {{-- オプショナルチェーン（?->）: paidAt が null の場合でもエラーにならない --}}
                                {{ $member->paidAt?->format('Y/m/d H:i') ?? '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- テーブルフッター：月謝収入の合計行 --}}
            <div class="px-5 py-3 bg-gray-50 border-t border-gray-200 flex justify-between items-center rounded-b-xl">
                <span class="text-xs text-gray-500">
                    正会員 {{ $members->count() }}人 / 月謝単価 ¥{{ number_format(config('stripe.prices.monthly_fee')) }}
                </span>
                <div class="text-right">
                    <span class="text-xs text-gray-500">月謝収入合計</span>
                    <span class="ml-3 text-sm font-bold text-gray-900">¥{{ number_format($monthlyFeeRevenue) }}</span>
                    <span class="ml-2 text-xs text-gray-400">/ 最大 ¥{{ number_format($maxMonthlyRevenue) }}</span>
                </div>
            </div>
        @endif
    </div>

    {{-- =============================================
         ビジター決済履歴テーブル
         ============================================= --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-semibold text-gray-900">ビジター決済履歴</h2>
                <p class="mt-0.5 text-xs text-gray-500">
                    {{ $targetDate->format('Y年n月') }}の都度払い一覧
                </p>
            </div>
            {{-- 合計件数と金額のバッジ --}}
            <div class="text-xs text-gray-500">
                {{ $visitorPayments->count() }}件 /
                <span class="font-semibold text-gray-900">¥{{ number_format($visitorRevenue) }}</span>
            </div>
        </div>

        @if($visitorPayments->isEmpty())
            {{-- 対象月のビジター決済がない場合 --}}
            <div class="px-5 py-10 text-center">
                <svg class="w-10 h-10 text-gray-300 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                <p class="text-sm text-gray-400">この月のビジター決済はありません</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">ビジター名</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">メールアドレス</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">金額</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">決済日時</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">備考</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($visitorPayments as $payment)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-7 h-7 rounded-full bg-blue-100 text-blue-800 flex items-center justify-center text-xs font-bold flex-shrink-0">
                                        {{ mb_substr($payment->user?->name ?? '?', 0, 1) }}
                                    </div>
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $payment->user?->name ?? '不明' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-600">
                                {{ $payment->user?->email ?? '-' }}
                            </td>
                            <td class="px-5 py-3.5 text-sm font-medium text-gray-900">
                                ¥{{ number_format($payment->amount) }}
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-500">
                                {{ $payment->paid_at?->format('Y/m/d H:i') ?? '-' }}
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-500">
                                {{-- notes カラムに「管理者代行決済」などのメモが入ることがある --}}
                                @if($payment->notes)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">
                                        {{ $payment->notes }}
                                    </span>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- ビジターテーブルフッター：合計金額 --}}
            <div class="px-5 py-3 bg-gray-50 border-t border-gray-200 flex justify-between items-center rounded-b-xl">
                <span class="text-xs text-gray-500">
                    都度払い単価 ¥{{ number_format(config('stripe.prices.visitor_fee')) }}
                </span>
                <div class="text-right">
                    <span class="text-xs text-gray-500">ビジター収入合計</span>
                    <span class="ml-3 text-sm font-bold text-gray-900">¥{{ number_format($visitorRevenue) }}</span>
                </div>
            </div>
        @endif
    </div>

</div>

@endsection
