{{--
    正会員 月謝決済フォーム

    このページで使うStripe関連の技術：
    ─────────────────────────────────────────────
    Stripe.js とは？
    Stripeが提供するJavaScriptライブラリです。
    カード番号・有効期限・CVCをStripeのサーバーに直接送信してくれます。
    これにより、カード情報がこのアプリのサーバーに一切保存されないため
    セキュリティリスクをゼロにできます（PCI DSSという国際標準に準拠）。

    Stripe Elements とは？
    Stripe.jsが提供するUIコンポーネントです。
    カード入力フォームのHTML要素を提供してくれます。
    テーマやスタイルをカスタマイズして、サイトのデザインに合わせられます。
    ─────────────────────────────────────────────
--}}
@extends('layouts.app')

@section('title', '月謝のお支払い - サッカークラブ管理システム')

{{--
    Stripe.js をこのページだけに読み込む

    @push('stripe') : layouts/app.blade.php の @stack('stripe') に追加される
    決済ページ以外ではStripe.jsを読み込まないことで、
    不要なJSの読み込みを避けパフォーマンスを改善する
--}}
@push('stripe')
    {{-- Stripe.js 本体（必ずこのURLから読み込む） --}}
    <script src="https://js.stripe.com/v3/"></script>
@endpush

@section('content')

<div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- ページヘッダー --}}
    <div class="mb-8">
        <a href="{{ route('member.dashboard') }}"
           class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-900 mb-4 transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            ダッシュボードに戻る
        </a>
        <h1 class="text-2xl font-bold text-gray-900">月謝のお支払い</h1>
        <p class="mt-1 text-sm text-gray-500">{{ $billingMonth }}分</p>
    </div>

    {{-- =============================================
         支払い内容の確認カード
         ============================================= --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6 mb-5">
        <h2 class="text-sm font-semibold text-gray-700 mb-4">お支払い内容</h2>
        <div class="flex justify-between items-center py-3 border-t border-gray-100">
            <span class="text-sm text-gray-600">{{ $billingMonth }}分 月謝</span>
            <span class="text-sm font-medium text-gray-900">¥{{ number_format($amount) }}</span>
        </div>
        <div class="flex justify-between items-center py-3 border-t border-gray-100">
            <span class="text-base font-semibold text-gray-900">合計</span>
            <span class="text-2xl font-bold text-gray-900">¥{{ number_format($amount) }}</span>
        </div>
    </div>

    {{-- =============================================
         Stripe 決済フォームカード
         ============================================= --}}
    <div class="bg-white rounded-xl border border-gray-200 p-6">
        <h2 class="text-sm font-semibold text-gray-700 mb-5">クレジットカード情報</h2>

        {{-- エラーメッセージ表示エリア（JavaScriptで制御） --}}
        <div id="payment-error" class="hidden mb-4 rounded-lg bg-red-50 border border-red-200 p-4">
            <div class="flex gap-2">
                <svg class="h-5 w-5 text-red-400 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd"/>
                </svg>
                <p id="payment-error-message" class="text-sm text-red-700"></p>
            </div>
        </div>

        {{-- 成功メッセージ表示エリア（JavaScriptで制御） --}}
        <div id="payment-success" class="hidden mb-4 rounded-lg bg-club-green-50 border border-club-green-200 p-4">
            <div class="flex gap-2">
                <svg class="h-5 w-5 text-club-green-500 shrink-0" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/>
                </svg>
                <p id="payment-success-message" class="text-sm text-club-green-800"></p>
            </div>
        </div>

        {{-- =============================================
             Stripe Elements のカード入力フィールド
             =============================================
             id="card-element" の div に Stripe.js が
             カード入力フォームを自動的に埋め込みます。
             --}}
        <div class="mb-5">
            <label class="block text-sm font-medium text-gray-700 mb-2">
                カード番号・有効期限・セキュリティコード
            </label>
            {{-- ↓ここにStripe.jsがiframeを埋め込む --}}
            <div id="card-element"
                 class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-3
                        focus-within:ring-2 focus-within:ring-club-green-500 focus-within:border-transparent
                        transition-colors">
                {{-- Stripe.jsがここにカード入力UIを生成する --}}
            </div>
            {{-- Stripe Elementsのエラーメッセージ（カード番号が無効など）--}}
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
                   bg-club-green-600 text-white
                   hover:bg-club-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2
                   focus:ring-club-green-500
                   disabled:bg-gray-300 disabled:cursor-not-allowed
                   transition-colors duration-150 cursor-pointer"
        >
            {{-- ローディングスピナー（処理中に表示） --}}
            <svg id="loading-spinner" class="hidden animate-spin h-4 w-4 text-white"
                 fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor"
                      d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
            </svg>
            <span id="button-text">¥{{ number_format($amount) }} を支払う</span>
        </button>

        {{-- セキュリティ表示 --}}
        <div class="mt-4 flex items-center justify-center gap-1.5 text-xs text-gray-400">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
            </svg>
            <span>Stripe により安全に処理されます。カード情報はこのサーバーに保存されません。</span>
        </div>
    </div>

</div>

{{--
    =============================================
    JavaScriptセクション
    =============================================
    @push('scripts') : layouts/app.blade.php の @stack('scripts') に追加される
    Stripe.jsの処理はページごとに異なるため、このページ専用のJSをここに書きます
--}}
@push('scripts')
<script>
/**
 * Stripe.js を使った決済処理
 *
 * 処理の流れ：
 * 1. ページロード時：Stripe.js を初期化してカード入力フォームを表示
 * 2. ボタンクリック時：
 *    a. サーバー（/member/payment/intent）に PaymentIntentの作成を依頼
 *    b. サーバーから client_secret を受け取る
 *    c. Stripe.js が client_secret を使ってカード情報をStripeに送信
 *    d. 決済成功後、サーバー（/member/payment/complete）に通知
 *    e. ダッシュボードにリダイレクト
 */

// =============================================
// 1. Stripe.js の初期化
// =============================================

// Stripe() の引数は「公開可能キー」（pk_test_... で始まるもの）
// Bladeの {{ }} でPHPの変数をJavaScriptに渡す
// @json() でエスケープ処理（XSS対策）
// @json() : PHP変数をJavaScriptで安全に使えるJSON形式に変換する（XSS対策）
const stripe = Stripe(@json($stripeKey));

/**
 * Stripe Elements の初期化
 * Elements = カード入力フォームのUIコンポーネント
 */
const elements = stripe.elements();

/**
 * カード入力フィールドの作成と設定
 * Stripeが提供するスタイルをカスタマイズできる
 */
const cardElement = elements.create('card', {
    style: {
        base: {
            // フォントサイズ
            fontSize: '15px',
            // テキスト色
            color: '#111827',
            // フォントファミリー
            fontFamily: '"Noto Sans JP", system-ui, sans-serif',
            // プレースホルダーの色
            '::placeholder': {
                color: '#9ca3af',
            },
        },
        // バリデーションエラー時のスタイル
        invalid: {
            color: '#ef4444',
            iconColor: '#ef4444',
        },
    },
    // ロケールを日本語に（エラーメッセージが日本語で表示される）
    locale: 'ja',
});

// id="card-element" のdivにカード入力フォームを埋め込む
cardElement.mount('#card-element');

// =============================================
// カード入力のリアルタイムバリデーション
// =============================================
// カード番号を入力するたびに呼ばれる（リアルタイムエラー表示）
cardElement.on('change', function(event) {
    const errorDisplay = document.getElementById('card-errors');

    if (event.error) {
        // エラーがある場合はメッセージを表示
        errorDisplay.textContent = event.error.message;
        errorDisplay.classList.remove('hidden');
    } else {
        // エラーがない場合は非表示
        errorDisplay.textContent = '';
        errorDisplay.classList.add('hidden');
    }
});

// =============================================
// 2. 支払いボタンのクリック処理
// =============================================
document.getElementById('submit-button').addEventListener('click', async function() {

    // ボタンを無効化（二重送信防止）
    setLoading(true);

    try {
        // =============================================
        // a. サーバーに PaymentIntent の作成を依頼
        // =============================================
        // fetch() は JavaScript でHTTPリクエストを送る方法（Ajax通信）
        // await は非同期処理が完了するまで待つ
        const intentResponse = await fetch('{{ route('member.payment.intent') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                // LaravelのCSRF対策：metaタグからCSRFトークンを取得して送る
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                // JSONレスポンスを要求（LaravelのAjaxリクエスト判定に必要）
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        const intentData = await intentResponse.json();

        // サーバーエラーの処理
        if (!intentResponse.ok) {
            showError(intentData.error || '決済の準備中にエラーが発生しました。');
            setLoading(false);
            return;
        }

        // =============================================
        // b. client_secret を取得してStripeで決済実行
        // =============================================
        // stripe.confirmCardPayment() :
        // client_secret とカード情報を使ってStripeサーバーに決済を依頼する
        const { error, paymentIntent } = await stripe.confirmCardPayment(
            intentData.clientSecret, // サーバーから受け取った client_secret
            {
                payment_method: {
                    card: cardElement, // 入力されたカード情報
                    billing_details: {
                        name: '{{ $user->name }}',    // カード名義人
                        email: '{{ $user->email }}',  // メールアドレス
                    },
                },
            }
        );

        if (error) {
            // Stripeの決済エラー（カードの期限切れ、残高不足など）
            // error.message はStripeが日本語で返してくれる（locale: 'ja' の効果）
            showError(error.message);
            setLoading(false);
            return;
        }

        // =============================================
        // d. サーバーに決済完了を通知
        // =============================================
        // paymentIntent.status が 'succeeded' であることを確認後、
        // サーバーに通知してDBを更新してもらう
        if (paymentIntent.status === 'succeeded') {
            const completeResponse = await fetch('{{ route('member.payment.complete') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    payment_intent_id: paymentIntent.id,
                }),
            });

            const completeData = await completeResponse.json();

            if (completeData.success) {
                // 成功メッセージを表示
                showSuccess(completeData.message || '決済が完了しました！');

                // 2秒後にダッシュボードにリダイレクト
                setTimeout(() => {
                    window.location.href = completeData.redirect;
                }, 2000);
            } else {
                showError(completeData.error || '決済の記録中にエラーが発生しました。');
                setLoading(false);
            }
        }

    } catch (e) {
        // ネットワークエラーなど予期しないエラー
        console.error('決済処理エラー:', e);
        showError('通信エラーが発生しました。インターネット接続を確認してください。');
        setLoading(false);
    }
});

// =============================================
// ヘルパー関数（UIの状態管理）
// =============================================

/**
 * ローディング状態を切り替える
 * @param {boolean} isLoading - true: ローディング中, false: 通常
 */
function setLoading(isLoading) {
    const button  = document.getElementById('submit-button');
    const spinner = document.getElementById('loading-spinner');
    const text    = document.getElementById('button-text');

    button.disabled = isLoading;
    spinner.classList.toggle('hidden', !isLoading);
    text.textContent = isLoading ? '処理中...' : '¥{{ number_format($amount) }} を支払う';
}

/**
 * エラーメッセージを表示する
 * @param {string} message - 表示するエラーメッセージ
 */
function showError(message) {
    const errorDiv = document.getElementById('payment-error');
    const errorMsg = document.getElementById('payment-error-message');

    errorMsg.textContent = message;
    errorDiv.classList.remove('hidden');

    // 成功メッセージは非表示にする
    document.getElementById('payment-success').classList.add('hidden');

    // エラー表示位置までスクロール
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

/**
 * 成功メッセージを表示する
 * @param {string} message - 表示する成功メッセージ
 */
function showSuccess(message) {
    const successDiv = document.getElementById('payment-success');
    const successMsg = document.getElementById('payment-success-message');

    successMsg.textContent = message;
    successDiv.classList.remove('hidden');

    // エラーメッセージは非表示にする
    document.getElementById('payment-error').classList.add('hidden');

    // ボタンを成功状態に変更
    const button = document.getElementById('submit-button');
    button.disabled = true;
    button.classList.remove('bg-club-green-600', 'hover:bg-club-green-700');
    button.classList.add('bg-gray-300');
    document.getElementById('button-text').textContent = '完了！';
}
</script>
@endpush

@endsection
