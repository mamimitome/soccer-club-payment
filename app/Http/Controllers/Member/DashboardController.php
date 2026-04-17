<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * 正会員ダッシュボードコントローラー
 *
 * 正会員（member）専用の画面を管理します。
 * RoleMiddleware により、member ロール以外はアクセスできません。
 *
 * 担当する画面：
 * - /member/dashboard : 会員ダッシュボード（月謝の支払い状況・履歴）
 */
class DashboardController extends Controller
{
    /**
     * 正会員ダッシュボードを表示する
     *
     * ログイン中の会員の決済履歴などを取得して画面に渡します。
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 現在ログインしているユーザーを取得
        // Auth::user() はログインユーザーのモデルインスタンスを返す
        $user = Auth::user();

        // この会員の直近12ヶ月の決済履歴を取得
        // リレーション payments() は User モデルで定義済み
        $payments = $user->payments()
            ->orderBy('created_at', 'desc') // 新しい順
            ->take(12)                       // 最大12件（1年分）
            ->get();

        // 今月の月謝支払い状況を確認
        // 今月分の決済が成功しているかどうか
        $currentMonthPaid = $user->payments()
            ->where('payment_type', 'monthly_fee')  // 月謝のみ
            ->where('status', 'succeeded')           // 成功したもの
            ->whereMonth('billing_month', now()->month)
            ->whereYear('billing_month', now()->year)
            ->exists(); // 存在するかどうか（true/false）

        return view('member.dashboard', compact(
            'user',
            'payments',
            'currentMonthPaid'
        ));
    }
}
