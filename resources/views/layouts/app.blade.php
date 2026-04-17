{{--
    共通レイアウトテンプレート

    Bladeテンプレートとは？
    Laravelの標準テンプレートエンジンです。
    PHPのコードをHTMLに埋め込む際に、読みやすい構文で書けます。

    このファイルの役割：
    すべての画面で共通するHTML（<head>、ナビゲーションバーなど）を定義します。
    各ページはこのレイアウトを「継承」して、独自のコンテンツだけを書けばOKです。

    @yield('content') と書かれた部分に、各ページのコンテンツが埋め込まれます。
--}}
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    {{-- ビューポート設定：スマートフォンでも正しく表示されるようにする --}}
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- CSRFトークン：フォームのセキュリティ対策（偽サイトからの不正送信を防ぐ） --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- @yield('title') : 各ページから title を受け取る。なければデフォルト値を使う --}}
    <title>@yield('title', 'サッカークラブ管理システム')</title>

    {{-- Viteでビルドしたアセット（CSS・JS）を読み込む --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{--
        Stripe.js の読み込み（CDN）

        なぜ CDN（外部URL）から読み込むの？
        Stripeは「必ずこのURLから読み込むこと」を要求しています。
        自分のサーバーにコピーして使うことは許可されていません。
        これはStripeがリアルタイムで不正検知などのアップデートを
        行えるようにするためです。

        @stack('stripe') : 決済ページのみStripe.jsを読み込む
        （全ページで読み込む必要はないため、必要なページだけに限定する）
    --}}
    @stack('stripe')
</head>
<body class="bg-gray-50 text-gray-900 antialiased">

    {{-- =============================================
         ナビゲーションバー（ログイン時のみ表示）
         ============================================= --}}
    @auth
    {{-- @auth : ログインしているユーザーにだけ表示する --}}
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">

                {{-- 左側：ロゴ・サービス名 --}}
                <div class="flex items-center gap-3">
                    {{-- サッカーボールアイコン --}}
                    <div class="w-8 h-8 bg-club-green-600 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8z"/>
                            <path d="M12 6l-1.5 4.5H6l3.75 2.73L8.25 18 12 15.27 15.75 18l-1.5-4.77L18 10.5h-4.5z"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-gray-900 text-sm">
                        サッカークラブ
                    </span>
                </div>

                {{-- 右側：ロールバッジ + ユーザー名 + ログアウト --}}
                <div class="flex items-center gap-4">

                    {{-- ロールバッジ（ロールによって色が変わる） --}}
                    @if(auth()->user()->isAdmin())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            管理者
                        </span>
                    @elseif(auth()->user()->isMember())
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-club-green-100 text-club-green-800">
                            正会員
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            ビジター
                        </span>
                    @endif

                    {{-- ログインユーザー名 --}}
                    <span class="text-sm text-gray-600">
                        {{ auth()->user()->name }} さん
                    </span>

                    {{-- ログアウトボタン --}}
                    {{-- フォームを使う理由: GETではなくPOSTでログアウトする（セキュリティのため） --}}
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        {{-- @csrf : CSRF攻撃を防ぐための隠しトークンを自動挿入 --}}
                        <button type="submit"
                            class="text-sm text-gray-500 hover:text-gray-900 transition-colors">
                            ログアウト
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>
    @endauth

    {{-- =============================================
         フラッシュメッセージ（一時的な通知）
         =============================================
         session('success') : 成功メッセージ（例: ログアウトしました）
         session('error')   : エラーメッセージ（例: アクセス権限がありません）
         --}}
    @if(session('message') || session('error'))
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4">
        @if(session('message'))
            <div class="rounded-lg bg-club-green-50 border border-club-green-200 p-4">
                <p class="text-sm text-club-green-800">{{ session('message') }}</p>
            </div>
        @endif
        @if(session('error'))
            <div class="rounded-lg bg-red-50 border border-red-200 p-4">
                <p class="text-sm text-red-800">{{ session('error') }}</p>
            </div>
        @endif
    </div>
    @endif

    {{-- =============================================
         メインコンテンツエリア
         =============================================
         @yield('content') : 各ページのコンテンツがここに入る
         --}}
    <main>
        @yield('content')
    </main>

    {{-- =============================================
         フッター
         ============================================= --}}
    <footer class="border-t border-gray-200 mt-auto py-6">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <p class="text-center text-xs text-gray-400">
                © {{ date('Y') }} サッカークラブ管理システム
            </p>
        </div>
    </footer>

    {{--
        @stack('scripts') : 各ページから @push('scripts') で追加されたJSを出力する
        ページ固有のJavaScriptコードをここに集約することで、
        必要なページにだけスクリプトを読み込める（パフォーマンス改善）
    --}}
    @stack('scripts')

</body>
</html>
