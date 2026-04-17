<?php

namespace App\Http\Controllers\Visitor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

/**
 * ビジター決済コントローラー
 *
 * ビジター（visitor）専用の決済画面を管理します。
 * RoleMiddleware により、visitor ロール以外はアクセスできません。
 *
 * 担当する画面：
 * - /visitor/pay : ビジター都度払い画面（2,500円）
 *
 * ※ 実際のStripe決済処理はStep3以降で実装します。
 *   このStep2では画面の表示のみ実装します。
 */
class PayController extends Controller
{
    /**
     * ビジター決済画面を表示する
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 現在ログインしているビジターを取得
        $user = Auth::user();

        // このビジターの過去の決済履歴を取得（新しい順に5件）
        $recentPayments = $user->payments()
            ->where('payment_type', 'visitor_fee') // ビジター料金のみ
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // ビジター料金（固定値）
        // 定数として定義することで、後で変更が必要になったときに
        // このファイルだけ修正すれば済む
        $amount = 2500; // 2,500円

        return view('visitor.pay', compact(
            'user',
            'recentPayments',
            'amount'
        ));
    }
}
