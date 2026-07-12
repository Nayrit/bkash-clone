<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AgentFundingRequest;
use App\Models\Transaction; // Required for the Audit Trail and Approvals
use App\Models\User;
use App\Models\Wallet;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Required for database transactions

class AdminController extends Controller
{
    // Dashboard: Shows Stats and Pending Requests
    public function index()
    {
        $admin = Auth::user();
        $pendingRequests = AgentFundingRequest::with('agent')->where('status', 'pending')->latest()->get();

        $totalUsers = User::count();
        $totalSystemBalance = Wallet::sum('balance');
        $totalAgentCashInHand = Wallet::sum('cash_in_hand');
        $totalAdminDue = Wallet::sum('admin_due');
        $totalTransactions = Transaction::count();
        $recentTransactions = Transaction::with(['sender', 'receiver'])->latest()->limit(50)->get();

        // Retrieve dynamic system settings for fee & commission management
        $sendMoneyFeeFlat = SystemSetting::getVal('send_money_fee_flat', '5.00');
        $cashOutFeePercent = SystemSetting::getVal('cash_out_fee_percentage', '2.00');
        $agentCommissionPercent = SystemSetting::getVal('agent_commission_percentage', '1.50');

        return view('admin.dashboard', compact(
            'admin', 'pendingRequests', 'totalUsers', 'totalSystemBalance',
            'totalAgentCashInHand', 'totalAdminDue', 'totalTransactions',
            'recentTransactions', 'sendMoneyFeeFlat', 'cashOutFeePercent', 'agentCommissionPercent'
        ));
    }

    // Logic: Approves the Agent's funding request
    public function approveRequest($id)
    {
        $fundRequest = AgentFundingRequest::findOrFail($id);

        if ($fundRequest->status !== 'pending') {
            return back()->with('error', 'Request already processed.');
        }

        $admin = Auth::user();
        $agent = $fundRequest->agent;

        // Uses a Transaction to ensure money is moved safely or not at all
        DB::transaction(function () use ($fundRequest, $admin, $agent) {
            
            // Move the Money & track Agent due to Admin
            $admin->wallet->decrement('balance', $fundRequest->amount);
            $agent->wallet->increment('balance', $fundRequest->amount);
            $agent->wallet->increment('admin_due', $fundRequest->amount);

            // Log the transaction
            Transaction::create([
                'txn_id' => uniqid('TXN_'),
                'type' => 'system_float',
                'sender_id' => $admin->id,
                'receiver_id' => $agent->id,
                'amount' => $fundRequest->amount,
                'fee' => 0.00
            ]);

            // Update request status
            $fundRequest->update(['status' => 'approved']);
        });

        return back()->with('status', 'Request Approved! Float has been transferred to the Agent.');
    }

    // Audit Trail: Shows all transactions with search and date filter capabilities
    public function transactions(Request $request)
    {
        $query = Transaction::with(['sender', 'receiver']);

        if ($request->filled('q')) {
            $search = trim($request->q);
            $query->where(function ($q) use ($search) {
                $q->where('txn_id', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('amount', 'like', "%{$search}%")
                  ->orWhere('created_at', 'like', "%{$search}%")
                  ->orWhereHas('sender', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  })
                  ->orWhereHas('receiver', function ($rq) use ($search) {
                      $rq->where('name', 'like', "%{$search}%")
                         ->orWhere('phone', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $transactions = $query->latest()->paginate(25)->withQueryString();
        return view('admin.transactions', compact('transactions'));
    }

    // Dynamic Settings: Update Platform Fees and Commissions
    public function updateSettings(Request $request)
    {
        $request->validate([
            'send_money_fee_flat' => 'required|numeric|min:0',
            'cash_out_fee_percentage' => 'required|numeric|min:0|max:100',
            'agent_commission_percentage' => 'required|numeric|min:0|max:100',
        ]);

        if ((float) $request->agent_commission_percentage > (float) $request->cash_out_fee_percentage) {
            return back()->withErrors(['agent_commission_percentage' => 'Agent commission percentage cannot exceed the total Cash-Out fee percentage.']);
        }

        SystemSetting::setVal('send_money_fee_flat', round((float) $request->send_money_fee_flat, 2));
        SystemSetting::setVal('cash_out_fee_percentage', round((float) $request->cash_out_fee_percentage, 2));
        SystemSetting::setVal('agent_commission_percentage', round((float) $request->agent_commission_percentage, 2));

        return back()->with('status', 'Platform Fees & Commission structures updated successfully!');
    }
}