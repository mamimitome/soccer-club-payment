{{-- ビジター決済画面 --}}
@extends('layouts.app')

@section('title', '都度払い - サッカークラブ管理システム')

@section('content')

<div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ページヘッダー --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">
            ビジター参加費のお支払い
        </h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $user->name }} さん — 都度払い
        </p>
    </div>

    {{-- =============================================
         金額確認カード
         ============================================= --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-sm font-semibold text-gray-900">お支払い内容</h2>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                ビジター
            </span>
        </div>

        <div class="border-t border-b border-gray-100 py-4 mb-4">
            <div class="flex justify-between items-center">
                <span class="text-sm text-gray-600">参加費（1回）</span>
                <span class="text-sm font-medium text-gray-900">¥{{ number_format($amount) }}</span>
            </div>
        </div>

        <div class="flex justify-between items-center">
            <span class="text-base font-semibold text-gray-900">合計</span>
            <span class="text-2xl font-bold text-gray-900">¥{{ number_format($amount) }}</span>
        </div>

        {{-- =============================================
             決済ボタン（Step3でStripe連携を実装予定）
             ============================================= --}}
        <div class="mt-6">
            <button
                type="button"
                disabled
                class="w-full flex justify-center py-3 px-4 rounded-lg text-sm font-semibold
                       bg-gray-100 text-gray-400 cursor-not-allowed"
            >
                クレジットカードで支払う（Step3で実装）
            </button>
            <p class="mt-2 text-center text-xs text-gray-400">
                Stripe決済はStep3で実装します
            </p>
        </div>
    </div>

    {{-- =============================================
         正会員への案内バナー
         ============================================= --}}
    <div class="bg-club-green-50 border border-club-green-200 rounded-xl p-5 mb-6">
        <div class="flex gap-3">
            <div class="p-2 bg-club-green-100 rounded-lg shrink-0">
                <svg class="w-4 h-4 text-club-green-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div>
                <p class="text-sm font-medium text-club-green-900">
                    正会員になるとお得です！
                </p>
                <p class="mt-0.5 text-xs text-club-green-700">
                    月4回以上参加する方は、月謝 ¥9,000 の正会員がお得です。<br>
                    正会員登録についてはスタッフまでお声がけください。
                </p>
            </div>
        </div>
    </div>

    {{-- =============================================
         過去の決済履歴
         ============================================= --}}
    @if($recentPayments->isNotEmpty())
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="text-sm font-semibold text-gray-900">最近の参加履歴</h2>
        </div>
        <ul class="divide-y divide-gray-100">
            @foreach($recentPayments as $payment)
            <li class="px-5 py-3.5 flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-900">参加費</p>
                    <p class="text-xs text-gray-400">
                        {{ $payment->paid_at?->format('Y/m/d') ?? $payment->created_at->format('Y/m/d') }}
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-sm font-medium text-gray-900">¥{{ number_format($payment->amount) }}</p>
                    <span class="text-xs {{ $payment->status === 'succeeded' ? 'text-club-green-600' : 'text-red-500' }}">
                        {{ $payment->status === 'succeeded' ? '✓ 完了' : '✗ 失敗' }}
                    </span>
                </div>
            </li>
            @endforeach
        </ul>
    </div>
    @endif

</div>

@endsection
