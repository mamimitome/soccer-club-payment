<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Payment;

/**
 * 管理者ダッシュボードコントローラー
 *
 * 管理者（admin）専用の画面を管理します。
 * RoleMiddleware により、admin ロール以外はアクセスできません。
 *
 * 担当する画面：
 * - /admin/dashboard : 管理ダッシュボード（会員数・売上の概要）
 */
class DashboardController extends Controller
{
    /**
     * 管理者ダッシュボードを表示する
     *
     * 画面に表示するデータ（会員数・売上など）を集めて、
     * ビュー（HTML）に渡します。
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // =============================================
        // ダッシュボード用のデータを集計する
        // =============================================

        // 正会員の総数を取得
        // where('role', 'member') : role が 'member' のユーザーだけを絞り込む
        // count() : 件数を数える
        $memberCount = User::where('role', 'member')->count();

        // ビジターの総数を取得
        $visitorCount = User::where('role', 'visitor')->count();

        // 今月の売上合計を計算
        // whereMonth() : 今月のレコードだけを絞り込む
        // whereYear()  : 今年のレコードだけを絞り込む
        // where('status', 'succeeded') : 決済成功のもののみ
        // sum('amount') : amount カラムの合計を計算
        $thisMonthRevenue = Payment::where('status', 'succeeded')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        // 最近の決済履歴（新しい順に10件）
        // with('user') : リレーションを一緒に取得（N+1問題を防ぐ）
        // latest()     : created_at の降順で並び替え
        // take(10)     : 最大10件取得
        $recentPayments = Payment::with('user')
            ->where('status', 'succeeded')
            ->latest('paid_at')
            ->take(10)
            ->get();

        // 未払い（失敗）件数
        $failedPaymentsCount = Payment::where('status', 'failed')->count();

        // ビュー（resources/views/admin/dashboard.blade.php）にデータを渡す
        // compact() は変数名をキーにした配列を作る便利関数
        return view('admin.dashboard', compact(
            'memberCount',
            'visitorCount',
            'thisMonthRevenue',
            'recentPayments',
            'failedPaymentsCount'
        ));
    }
}
