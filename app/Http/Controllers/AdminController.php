<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AgentFundingRequest;
use App\Models\Transaction; // Required for the Audit Trail and Approvals
use App\Models\User;
use App\Models\Wallet;
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
        $totalTransactions = Transaction::count();
        $recentTransactions = Transaction::with(['sender', 'receiver'])->latest()->limit(50)->get();

        return view('admin.dashboard', compact('admin', 'pendingRequests', 'totalUsers', 'totalSystemBalance', 'totalTransactions', 'recentTransactions'));
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
            
            // Move the Money
            $admin->wallet->decrement('balance', $fundRequest->amount);
            $agent->wallet->increment('balance', $fundRequest->amount);

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

    // Audit Trail: Shows all transactions
    public function transactions()
    {
        $transactions = Transaction::with(['sender', 'receiver'])->latest()->paginate(20);
        return view('admin.transactions', compact('transactions'));
    }
}