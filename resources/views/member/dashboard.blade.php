{{-- 正会員ダッシュボード --}}
@extends('layouts.app')

@section('title', '会員ダッシュボード - サッカークラブ管理システム')

@section('content')

<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ページヘッダー --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">
            こんにちは、{{ $user->name }} さん
        </h1>
        <p class="mt-1 text-sm text-gray-500">正会員ダッシュボード</p>
    </div>

    {{-- =============================================
         今月の月謝ステータスカード
         ============================================= --}}
    <div class="mb-6">
        @if($currentMonthPaid)
            {{-- 今月の月謝が支払済みの場合 --}}
            <div class="bg-club-green-50 border border-club-green-200 rounded-xl p-5 flex items-start gap-4">
                <div class="p-2 bg-club-green-100 rounded-lg shrink-0">
                    <svg class="w-5 h-5 text-club-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-club-green-900">
                        {{ now()->format('Y年m月') }}の月謝は支払い済みです
                    </p>
                    <p class="mt-0.5 text-xs text-club-green-700">
                        月謝 ¥9,000 — 引き落とし完了
                    </p>
                </div>
            </div>
        @else
            {{-- 未払いの場合 --}}
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 flex items-start gap-4">
                <div class="p-2 bg-amber-100 rounded-lg shrink-0">
                    <svg class="w-5 h-5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z"/>
                    </svg>
                </div>
                <div class="flex-1">
                    <p class="text-sm font-semibold text-amber-900">
                        {{ now()->format('Y年m月') }}の月謝が未払いです
                    </p>
                    <p class="mt-0.5 text-xs text-amber-700">
                        月謝 ¥9,000 — お支払いをお願いします
                    </p>
                    <a href="{{ route('member.pay') }}"
                       class="mt-3 inline-flex items-center px-4 py-2 bg-amber-600 text-white text-sm font-medium rounded-lg hover:bg-amber-700">
                        月謝を支払う →
                    </a>
                </div>
            </div>
        @endif
    </div>

    {{-- =============================================
         会員情報カード
         ============================================= --}}
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-6">
        <h2 class="text-sm font-semibold text-gray-900 mb-4">会員情報</h2>
        <dl class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div>
                <dt class="text-xs text-gray-500">お名前</dt>
                <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ $user->name }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">メールアドレス</dt>
                <dd class="mt-0.5 text-sm font-medium text-gray-900">{{ $user->email }}</dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">会員種別</dt>
                <dd class="mt-0.5">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-club-green-100 text-club-green-800">
                        正会員（月謝 ¥9,000）
                    </span>
                </dd>
            </div>
            <div>
                <dt class="text-xs text-gray-500">電話番号</dt>
                <dd class="mt-0.5 text-sm font-medium text-gray-900">
                    {{ $user->phone ?? '未登録' }}
                </dd>
            </div>
        </dl>
    </div>

    {{-- =============================================
         決済履歴
         ============================================= --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-900">直近の決済履歴</h2>
        </div>

        @if($payments->isEmpty())
            <div class="px-5 py-10 text-center">
                <p class="text-sm text-gray-400">決済履歴がありません</p>
            </div>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach($payments as $payment)
                <li class="px-5 py-4 flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900">
                            @if($payment->billing_month)
                                {{ \Carbon\Carbon::parse($payment->billing_month)->format('Y年m月') }}分 月謝
                            @else
                                月謝
                            @endif
                        </p>
                        <p class="mt-0.5 text-xs text-gray-400">
                            {{ $payment->paid_at?->format('Y/m/d') ?? $payment->created_at->format('Y/m/d') }}
                        </p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900">¥{{ number_format($payment->amount) }}</p>
                        <span class="inline-flex items-center text-xs
                            {{ $payment->status === 'succeeded' ? 'text-club-green-600' : 'text-red-500' }}">
                            {{ $payment->status === 'succeeded' ? '✓ 完了' : '✗ 失敗' }}
                        </span>
                    </div>
                </li>
                @endforeach
            </ul>
        @endif
    </div>

</div>

@endsection
