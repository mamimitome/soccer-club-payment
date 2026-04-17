{{--
    管理者向けビジター代行決済画面

    この画面でスタッフは：
    1. ビジターをドロップダウンから選択する
    2. そのビジターのカードを使って参加費（2,500円）を決済する
    3. 決済はビジターのユーザーIDに紐付けて記録される

    使用場面：
    - ビジターが自分でスマートフォンを使えない場合
    - スタッフが受付で代わりに決済処理をする場合
--}}
@extends('layouts.app')

@section('title', 'ビジター代行決済 - サッカークラブ管理システム')

@push('stripe')
    <script src="https://js.stripe.com/v3/"></script>
@endpush

@section('content')

<div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ページヘッダー --}}
    <div class="mb-6">
        <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('admin.dashboard') }}" class="hover:text-gray-900 transition-colors">ダッシュボード</a>
            <span class="text-gray-300">/</span>
            <span class="text-gray-900 font-medium">ビジター代行決済</span>
        </div>
        <h1 class="text-2xl font-bold text-gray-900">ビジター代行決済</h1>
        <p class="mt-1 text-sm text-gray-500">
            スタッフがビジターの代わりに参加費を受け付けます
        </p>
    </div>

    {{-- =============================================
         STEP 1: ビジター選択フォーム
         ============================================= --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
        <div class="flex items-center gap-2 mb-4">
            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-900 text-white text-xs font-bold">1</span>
            <h2 class="text-sm font-semibold text-gray-900">ビジターを選択</h2>
        </div>

        {{--
            GET メソッドでビジターを選択する
            選択後に ?visitor_id=xxx がURLに付き、ページがリロードされて
            Step 2の決済フォームが表示される
        --}}
        <form method="GET" action="{{ route('admin.visitor.pay') }}" class="flex gap-3">
            <div class="flex-1">
                <select
                    name="visitor_id"
                    class="w-full px-4 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-gray-900 focus:border-transparent"
                    onchange="this.form.submit()"
                >
                    {{-- ビジターが未選択の場合のデフォルト表示 --}}
                    <option value="">— ビジターを選んでください —</option>

                    {{-- visitors に含まれる全ビジターを選択肢として表示 --}}
                    @foreach($visitors as $visitor)
                        <option
                            value="{{ $visitor->id }}"
                            {{-- selected: 現在選択中のビジターにチェックを入れる --}}
                            {{ $selectedVisitorId == $visitor->id ? 'selected' : '' }}
                        >
                            {{ $visitor->name }}（{{ $visitor->email }}）
                        </option>
                    @endforeach
                </select>
            </div>
        </form>

        @if($visitors->isEmpty())
            {{-- ビジターが1人も登録されていない場合のメッセージ --}}
            <p class="mt-3 text-sm text-gray-400">
                ビジターが登録されていません。
                <a href="{{ route('admin.members.create') }}" class="text-gray-700 underline">会員管理</a>
                からビジターを追加してください。
            </p>
        @endif
    </div>

    {{-- =============================================
         STEP 2: 決済フォーム（ビジター選択後に表示）
         ============================================= --}}
    @if($selectedVisitor)

        {{-- 選択されたビジターの確認カード --}}
        <div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-6">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-blue-100 rounded-lg">
                    <svg class="w-4 h-4 text-blue-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold text-blue-900">{{ $selectedVisitor->name }}</p>
                    <p class="text-xs text-blue-700">{{ $selectedVisitor->email }}</p>
                </div>
                <div class="ml-auto">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        ビジター
                    </span>
                </div>
            </div>
        </div>

        {{-- お支払い内容 --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-5">
            <div class="flex items-center gap-2 mb-4">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-900 text-white text-xs font-bold">2</span>
                <h2 class="text-sm font-semibold text-gray-900">お支払い内容を確認</h2>
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
        </div>

        {{-- Stripe 決済フォーム --}}
        <div class="bg-white rounded-xl border border-gray-200 p-6 mb-6">
            <div class="flex items-center gap-2 mb-5">
                <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-900 text-white text-xs font-bold">3</span>
                <h2 class="text-sm font-semibold text-gray-900">クレジットカード情報を入力</h2>
            </div>

            {{-- エラーメッセージ表示エリア --}}
            <div id="payment-error" class="hidden mb-4 rounded-lg bg-red-50 border border-red-200 p-4">
                <div class="flex gap-2">
                    <svg class="h-5 w-5 text-red-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                    </svg>
                    <p id="payment-error-message" class="text-sm text-red-700"></p>
                </div>
            </div>

            {{-- 成功メッセージ表示エリア --}}
            <div id="payment-success" class="hidden mb-4 rounded-lg bg-club-green-50 border border-club-green-200 p-4">
                <div class="flex gap-2">
                    <svg class="h-5 w-5 text-club-green-500 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                    </svg>
                    <p id="payment-success-message" class="text-sm text-club-green-800"></p>
                </div>
            </div>

            {{-- Stripe Elements のカード入力フィールド --}}
            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    カード番号・有効期限・セキュリティコード
                </label>
                <div id="card-element"
                     class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-3
                            focus-within:ring-2 focus-within:ring-gray-900 focus-within:border-transparent
                            transition-colors">
                </div>
                <p id="card-errors" class="mt-1.5 text-xs text-red-600 hidden"></p>
            </div>

            {{-- テスト用カード番号の案内 --}}
            <div class="mb-5 rounded-lg bg-blue-50 border border-blue-200 p-4">
                <p class="text-xs font-semibold text-blue-800 mb-1">テスト環境でのテスト方法</p>
                <ul class="text-xs text-blue-700 space-y-0.5">
                    <li>カード番号: <code class="font-mono bg-blue-100 px-1 rounded">4242 4242 4242 4242</code></li>
                    <li>有効期限: 未来の日付（例: 12/34）</li>
                    <li>セキュリティコード: 任意の3桁（例: 123）</li>
                    <li>郵便番号: 任意の数字（例: 10000）</li>
                </ul>
            </div>

            {{-- 支払いボタン --}}
            <button
                id="submit-button"
                type="button"
                class="w-full flex justify-center items-center gap-2 py-3 px-4 rounded-lg text-sm font-semibold
                       bg-gray-900 text-white
                       hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-900
                       disabled:bg-gray-300 disabled:cursor-not-allowed
                       transition-colors duration-150 cursor-pointer"
            >
                <svg id="loading-spinner" class="hidden animate-spin h-4 w-4 text-white"
                     fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor"
                          d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
                {{-- ボタンに「誰の」決済かを表示して誤操作を防ぐ --}}
                <span id="button-text">
                    {{ $selectedVisitor->name }} さんの参加費 ¥{{ number_format($amount) }} を受け付ける
                </span>
            </button>

            {{-- セキュリティ表示 --}}
            <div class="mt-4 flex items-center justify-center gap-1.5 text-xs text-gray-400">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
                <span>Stripe により安全に処理されます。</span>
            </div>
        </div>

        {{-- =============================================
             選択されたビジターの最近の決済履歴
             ============================================= --}}
        @if($recentPayments->isNotEmpty())
        <div class="bg-white rounded-xl border border-gray-200">
            <div class="px-5 py-4 border-b border-gray-200">
                <h2 class="text-sm font-semibold text-gray-900">
                    {{ $selectedVisitor->name }} さんの最近の参加履歴
                </h2>
            </div>
            <ul class="divide-y divide-gray-100">
                @foreach($recentPayments as $payment)
                <li class="px-5 py-3.5 flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-900">参加費</p>
                        <p class="text-xs text-gray-400">
                            {{ $payment->paid_at?->format('Y/m/d H:i') ?? $payment->created_at->format('Y/m/d H:i') }}
                            {{-- notes（メモ）があれば表示：管理者代行の場合など --}}
                            @if($payment->notes)
                                <span class="ml-1 text-gray-400">（{{ $payment->notes }}）</span>
                            @endif
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

    @else
        {{-- ビジターが未選択の場合のガイダンス --}}
        <div class="bg-gray-50 border-2 border-dashed border-gray-200 rounded-xl p-10 text-center">
            <svg class="mx-auto w-10 h-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <p class="text-sm text-gray-400">上のドロップダウンからビジターを選択してください</p>
        </div>
    @endif

    {{-- ダッシュボードへ戻るリンク --}}
    <div class="mt-6">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-gray-500 hover:text-gray-900 transition-colors">
            ← ダッシュボードへ戻る
        </a>
    </div>

</div>

@push('scripts')
@if($selectedVisitor)
<script>
/**
 * 管理者代行決済のStripe.js処理
 *
 * 処理の流れ：
 * 1. Stripe.js を初期化してカード入力フォームを表示
 * 2. ボタンクリック時：
 *    a. サーバーに PaymentIntent の作成を依頼（visitor_id を送る）
 *    b. client_secret を受け取ってStripeで決済実行
 *    c. 決済成功後にサーバーに通知
 *    d. ページをリロードして履歴を更新
 */

// Stripe.js の初期化
const stripe = Stripe('{{ $stripeKey }}');
const elements = stripe.elements({ locale: 'ja' });

// カード入力フィールドの作成
const cardElement = elements.create('card', {
    style: {
        base: {
            fontSize: '15px',
            color: '#111827',
            fontFamily: '"Noto Sans JP", system-ui, sans-serif',
            '::placeholder': { color: '#9ca3af' },
        },
        invalid: {
            color: '#ef4444',
            iconColor: '#ef4444',
        },
    },
});

cardElement.mount('#card-element');

// リアルタイムバリデーション（カード入力中にエラーを表示）
cardElement.on('change', function(event) {
    const errorDisplay = document.getElementById('card-errors');
    if (event.error) {
        errorDisplay.textContent = event.error.message;
        errorDisplay.classList.remove('hidden');
    } else {
        errorDisplay.textContent = '';
        errorDisplay.classList.add('hidden');
    }
});

// 支払いボタンのクリック処理
document.getElementById('submit-button').addEventListener('click', async function() {
    setLoading(true);

    try {
        // a. サーバーに PaymentIntent 作成を依頼
        // visitor_id を送ることで「誰のための決済か」をサーバーに伝える
        const intentResponse = await fetch('{{ route('admin.visitor.payment.intent') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({
                visitor_id: {{ $selectedVisitor->id }},
            }),
        });

        const intentData = await intentResponse.json();

        if (!intentResponse.ok) {
            showError(intentData.error || '決済の準備中にエラーが発生しました。');
            setLoading(false);
            return;
        }

        // b. client_secret を使ってStripeで決済実行
        const { error, paymentIntent } = await stripe.confirmCardPayment(
            intentData.clientSecret,
            {
                payment_method: {
                    card: cardElement,
                    billing_details: {
                        name: '{{ $selectedVisitor->name }}',
                        email: '{{ $selectedVisitor->email }}',
                    },
                },
            }
        );

        if (error) {
            showError(error.message);
            setLoading(false);
            return;
        }

        // c. サーバーに決済完了を通知
        if (paymentIntent.status === 'succeeded') {
            const completeResponse = await fetch('{{ route('admin.visitor.payment.complete') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    payment_intent_id: paymentIntent.id,
                    visitor_id: {{ $selectedVisitor->id }},
                }),
            });

            const completeData = await completeResponse.json();

            if (completeData.success) {
                showSuccess(completeData.message || '参加費を受け付けました。');

                // 2秒後にページをリロードして履歴を更新する
                setTimeout(() => {
                    window.location.href = completeData.redirect;
                }, 2000);
            } else {
                showError(completeData.error || '決済の記録中にエラーが発生しました。');
                setLoading(false);
            }
        }

    } catch (e) {
        console.error('代行決済エラー:', e);
        showError('通信エラーが発生しました。インターネット接続を確認してください。');
        setLoading(false);
    }
});

/** ローディング状態を切り替える */
function setLoading(isLoading) {
    const button  = document.getElementById('submit-button');
    const spinner = document.getElementById('loading-spinner');
    const text    = document.getElementById('button-text');

    button.disabled = isLoading;
    spinner.classList.toggle('hidden', !isLoading);
    text.textContent = isLoading
        ? '処理中...'
        : '{{ $selectedVisitor->name }} さんの参加費 ¥{{ number_format($amount) }} を受け付ける';
}

/** エラーメッセージを表示する */
function showError(message) {
    const errorDiv = document.getElementById('payment-error');
    const errorMsg = document.getElementById('payment-error-message');
    errorMsg.textContent = message;
    errorDiv.classList.remove('hidden');
    document.getElementById('payment-success').classList.add('hidden');
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/** 成功メッセージを表示する */
function showSuccess(message) {
    const successDiv = document.getElementById('payment-success');
    const successMsg = document.getElementById('payment-success-message');
    successMsg.textContent = message;
    successDiv.classList.remove('hidden');
    document.getElementById('payment-error').classList.add('hidden');

    const button = document.getElementById('submit-button');
    button.disabled = true;
    button.classList.remove('bg-gray-900', 'hover:bg-gray-700');
    button.classList.add('bg-gray-300');
    document.getElementById('button-text').textContent = '完了！';
}
</script>
@endif
@endpush

@endsection
