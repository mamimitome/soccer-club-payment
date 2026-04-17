{{-- 管理者ダッシュボード --}}
@extends('layouts.app')

@section('title', '管理ダッシュボード - サッカークラブ管理システム')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ページヘッダー --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">管理ダッシュボード</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ now()->format('Y年m月d日') }} 時点の状況
        </p>
    </div>

    {{-- =============================================
         サマリーカード（数字の概要）
         ============================================= --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-8">

        {{-- 正会員数 --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">正会員数</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        {{ number_format($memberCount) }}
                        {{-- number_format() : 数字に3桁区切りのカンマを付ける（例: 1,234） --}}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">月謝 9,000円 / 月</p>
                </div>
                <div class="p-2.5 bg-club-green-100 rounded-lg">
                    <svg class="w-5 h-5 text-club-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- ビジター数 --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">ビジター数</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">{{ number_format($visitorCount) }}</p>
                    <p class="mt-1 text-xs text-gray-500">都度払い 2,500円 / 回</p>
                </div>
                <div class="p-2.5 bg-blue-100 rounded-lg">
                    <svg class="w-5 h-5 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- 今月の売上 --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">今月の売上</p>
                    <p class="mt-2 text-3xl font-bold text-gray-900">
                        ¥{{ number_format($thisMonthRevenue) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">{{ now()->format('Y年m月') }}</p>
                </div>
                <div class="p-2.5 bg-club-gold-100 rounded-lg">
                    <svg class="w-5 h-5 text-club-gold-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- 決済失敗件数 --}}
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">未対応（決済失敗）</p>
                    <p class="mt-2 text-3xl font-bold {{ $failedPaymentsCount > 0 ? 'text-red-600' : 'text-gray-900' }}">
                        {{ number_format($failedPaymentsCount) }}
                    </p>
                    <p class="mt-1 text-xs text-gray-500">要確認</p>
                </div>
                <div class="p-2.5 {{ $failedPaymentsCount > 0 ? 'bg-red-100' : 'bg-gray-100' }} rounded-lg">
                    <svg class="w-5 h-5 {{ $failedPaymentsCount > 0 ? 'text-red-600' : 'text-gray-500' }}"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- =============================================
         最近の決済履歴テーブル
         ============================================= --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-900">最近の決済履歴</h2>
        </div>

        @if($recentPayments->isEmpty())
            {{-- isEmpty() : コレクションが空かどうかを確認する --}}
            <div class="px-5 py-10 text-center">
                <p class="text-sm text-gray-400">決済履歴がありません</p>
            </div>
        @else
            <div class="overflow-x-auto">
                {{-- overflow-x-auto : 画面が小さいとき横スクロールを許可 --}}
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">会員名</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">種別</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">金額</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">状態</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">決済日時</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        {{-- @foreach : コレクションをループして各レコードを表示 --}}
                        @foreach($recentPayments as $payment)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3.5 text-sm text-gray-900">
                                {{-- オプショナルチェーン: user が null の場合でもエラーにならない --}}
                                {{ $payment->user?->name ?? '不明' }}
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-600">
                                {{ $payment->payment_type === 'monthly_fee' ? '月謝' : 'ビジター' }}
                            </td>
                            <td class="px-5 py-3.5 text-sm font-medium text-gray-900">
                                ¥{{ number_format($payment->amount) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $payment->status === 'succeeded' ? 'bg-club-green-100 text-club-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $payment->status === 'succeeded' ? '成功' : '失敗' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-sm text-gray-500">
                                {{-- format() : 日付のフォーマットを指定 --}}
                                {{ $payment->paid_at?->format('Y/m/d H:i') ?? '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

@endsection
