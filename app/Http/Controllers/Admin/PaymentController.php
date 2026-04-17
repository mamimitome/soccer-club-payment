<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * 管理者向け支払い状況一覧コントローラー
 *
 * このコントローラーの目的：
 * 月ごとの全会員の支払い状況（月謝・ビジター）を一覧で確認できる管理画面を提供します。
 *
 * 表示内容：
 * - 正会員の月謝支払い状況（支払い済み / 未払い）
 * - ビジターの都度払い履歴
 * - 月別の売上サマリー（総売上・月謝収入・ビジター収入）
 *
 * セキュリティ：
 * - routes/web.php で middleware(['auth', 'role:admin']) を設定しているため
 *   管理者（admin）のみアクセスできます。
 *
 * 担当するエンドポイント：
 * - GET /admin/payments : 支払い状況一覧（index）
 */
class PaymentController extends Controller
{
    /**
     * 支払い状況一覧を表示する
     *
     * GET /admin/payments?year=2026&month=4
     *
     * クエリパラメーターで表示月を切り替えられます。
     * 未指定の場合は当月を表示します。
     *
     * @param  \Illuminate\Http\Request  $request  year・month クエリパラメーターを含む
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        // =============================================
        // 表示対象月の決定
        // =============================================

        // クエリパラメーターから年・月を取得する
        // 例：?year=2026&month=3 → 2026年3月を表示
        $year  = (int) $request->input('year',  now()->year);
        $month = (int) $request->input('month', now()->month);

        // 年・月の値が有効な範囲かチェックする（不正な値を弾く）
        // Carbon::createSafe() : 無効な日付の場合は false を返す（例: month=13 は無効）
        $safeDate = Carbon::createSafe($year, $month, 1);
        if ($safeDate === false) {
            // 無効な年月が指定された場合は当月にフォールバック
            $year  = now()->year;
            $month = now()->month;
        }

        // 表示対象月を Carbon オブジェクトとして生成（月の1日）
        $targetDate  = Carbon::create($year, $month, 1)->startOfMonth();
        // 当月の1日（未来月判定に使う）
        $currentDate = now()->startOfMonth();

        // 未来の月は表示しない（データが存在しないため）
        if ($targetDate->gt($currentDate)) {
            $targetDate = $currentDate->copy();
            $year  = $targetDate->year;
            $month = $targetDate->month;
        }

        // =============================================
        // 正会員の支払い状況を取得
        // =============================================

        // 全正会員を取得し、対象月の月謝支払い状況を一緒に読み込む（with）
        // with() : 関連するデータを事前に一括取得して N+1 問題を防ぐ
        // N+1問題とは: ループ内でクエリを実行し続けると DB に大量のリクエストが発生する問題
        $members = User::where('role', 'member')
            ->with([
                // 'payments' リレーションをカスタム条件で絞り込んで取得する
                // 条件: payment_type が monthly_fee、status が succeeded、
                //       billing_month が対象年月の範囲内
                'payments' => function ($query) use ($year, $month) {
                    $query->where('payment_type', 'monthly_fee')
                          ->where('status', 'succeeded')
                          ->whereYear('billing_month', $year)
                          ->whereMonth('billing_month', $month);
                }
            ])
            ->orderBy('name') // 名前順で並べる
            ->get()
            ->map(function ($member) {
                // map() : コレクションの各要素を変換する（JavaScript の .map() と同じ）
                // 対象月の月謝が支払い済みかどうかを判定するフラグを追加
                // isNotEmpty() : コレクションに1件以上の要素があれば true
                $member->isPaid = $member->payments->isNotEmpty();

                // 支払い日時（支払い済みの場合のみ値が入る、未払いの場合は null）
                $member->paidAt = $member->payments->first()?->paid_at;

                return $member;
            })
            // 未払い（isPaid = false = 0）を先頭、支払い済み（isPaid = true = 1）を後ろに並べ替え
            // sortBy() : 指定したキーで昇順ソート（false < true なので未払いが先頭になる）
            ->sortBy('isPaid')
            ->values(); // sortBy() 後はインデックスが飛び番になるため values() でリセット

        // =============================================
        // ビジターの決済履歴を取得（対象月）
        // =============================================

        // 対象月に paid_at（決済日時）がある succeeded なビジター決済を取得
        $visitorPayments = Payment::with('user') // user リレーションも一緒に取得
            ->where('payment_type', 'visitor_fee')
            ->where('status', 'succeeded')
            ->whereYear('paid_at', $year)
            ->whereMonth('paid_at', $month)
            ->orderBy('paid_at', 'desc') // 新しい決済が上に来るよう降順ソート
            ->get();

        // =============================================
        // 売上サマリーを計算
        // =============================================

        // 月謝：支払い済み人数
        $paidMemberCount   = $members->where('isPaid', true)->count();
        // 月謝：未払い人数
        $unpaidMemberCount = $members->where('isPaid', false)->count();

        // 月謝収入：支払い済み人数 × 月謝金額（9,000円）
        // config() : config/stripe.php に定義した料金設定を取得する
        $monthlyFeeRevenue = $paidMemberCount * config('stripe.prices.monthly_fee');

        // ビジター収入：対象月の succeeded な visitor_fee の合計
        // sum('amount') : コレクションの amount カラムの合計値を計算する
        $visitorRevenue = $visitorPayments->sum('amount');

        // 総売上：月謝収入 + ビジター収入
        $totalRevenue = $monthlyFeeRevenue + $visitorRevenue;

        // 月謝の理論上の最大収入（全員が支払った場合）
        $maxMonthlyRevenue = $members->count() * config('stripe.prices.monthly_fee');

        // 月謝徴収率（%）：支払い済み人数 ÷ 全正会員数 × 100
        // 正会員が0人の場合は0%（ゼロ除算防止）
        $collectionRate = $members->count() > 0
            ? round(($paidMemberCount / $members->count()) * 100)
            : 0;

        // =============================================
        // 月ナビゲーション用データを計算
        // =============================================

        // 前月：対象月の1ヶ月前
        $prevMonth = $targetDate->copy()->subMonth();

        // 翌月：対象月の1ヶ月後（未来月の場合は null）
        $nextMonthDate = $targetDate->copy()->addMonth();
        // 翌月が未来の場合はナビゲーションリンクを表示しない
        $hasNextMonth  = $nextMonthDate->lte($currentDate);
        $nextMonth     = $hasNextMonth ? $nextMonthDate : null;

        // 現在表示中の月が当月かどうかのフラグ
        $isCurrentMonth = $targetDate->isSameMonth($currentDate);

        return view('admin.payments.index', compact(
            'members',          // 正会員一覧（isPaid・paidAt フラグ付き）
            'visitorPayments',  // 対象月のビジター決済履歴
            'paidMemberCount',        // 月謝支払い済み人数
            'unpaidMemberCount',      // 月謝未払い人数
            'monthlyFeeRevenue',      // 月謝収入合計
            'visitorRevenue',         // ビジター収入合計
            'totalRevenue',           // 総売上
            'maxMonthlyRevenue',      // 理論上の最大月謝収入
            'collectionRate',         // 月謝徴収率（%）
            'year',             // 表示中の年
            'month',            // 表示中の月
            'targetDate',       // 表示中の月（Carbon オブジェクト）
            'prevMonth',        // 前月（Carbon オブジェクト）
            'nextMonth',        // 翌月（Carbon オブジェクト、翌月が未来なら null）
            'isCurrentMonth',   // 当月かどうか
        ));
    }
}
