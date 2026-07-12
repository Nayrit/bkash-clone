<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AgentFundingRequest;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AgentController extends Controller
{
    // 1. Show the Dashboard
    public function index()
    {
        $agent = Auth::user();

        $fundingRequests = AgentFundingRequest::where('agent_id', $agent->id)
            ->latest()
            ->get();

        $totalCommissionEarned = Transaction::where('receiver_id', $agent->id)
            ->sum('agent_commission');

        $transactions = Transaction::with(['sender', 'receiver'])
            ->where(function ($q) use ($agent) {
                $q->where('sender_id', $agent->id)
                  ->orWhere('receiver_id', $agent->id);
            })
            ->latest()
            ->limit(25)
            ->get();

        return view('agent.dashboard', compact('agent', 'fundingRequests', 'transactions', 'totalCommissionEarned'));
    }

    // 2. Process Float Request from Treasury
    public function requestFunds(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:500'
        ]);

        AgentFundingRequest::create([
            'agent_id' => Auth::id(),
            'amount' => round((float) $request->amount, 2),
            'status' => 'pending'
        ]);

        return back()->with('status', 'Funding request successfully sent to the Treasury!');
    }

    // 3. Process Customer Cash-In (Fresh physical cash deposited -> Customer gets e-money, Agent earns commission)
    public function cashIn(Request $request)
    {
        $request->validate([
            'customer_phone' => 'required|string|exists:users,phone',
            'amount' => 'required|numeric|min:20',
        ]);

        $agent = Auth::user();
        $customer = User::where('phone', $request->customer_phone)->first();

        if (!$customer || $customer->role !== 'customer') {
            return back()->withErrors(['customer_phone' => 'The provided phone number is not a registered Customer account.']);
        }

        $amount = round((float) $request->amount, 2);

        DB::transaction(function () use ($agent, $customer, $amount) {
            $agentWallet = Wallet::where('user_id', $agent->id)->lockForUpdate()->firstOrFail();
            $customerWallet = Wallet::where('user_id', $customer->id)->lockForUpdate()->firstOrFail();

            if ($agentWallet->balance < $amount) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => "Insufficient digital float balance (৳{$agentWallet->balance}). Request float from Treasury first."
                ]);
            }

            // Customer gets deposit amount; Agent digital float decreases; Agent physical cash increases
            $agentWallet->decrement('balance', $amount);
            $customerWallet->increment('balance', $amount);
            $agentWallet->increment('cash_in_hand', $amount);

            // Single Ledger Entry with 0 commission & 0 fee
            Transaction::create([
                'txn_id' => uniqid('TXN_'),
                'type' => 'cash_in',
                'sender_id' => $agent->id,
                'receiver_id' => $customer->id,
                'amount' => $amount,
                'fee' => 0.00,
                'agent_commission' => 0.00,
                'admin_fee' => 0.00
            ]);
        });

        return back()->with('status', "Cash-In of ৳{$amount} sent to Customer {$customer->name} ({$customer->phone})! Collected ৳{$amount} physical cash.");
    }

    // 4. Process Agent Cash-Out (Return float back to Admin Treasury)
    public function cashOut(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:100',
        ]);

        $agent = Auth::user();
        $amount = round((float) $request->amount, 2);

        DB::transaction(function () use ($agent, $amount) {
            $agentWallet = Wallet::where('user_id', $agent->id)->lockForUpdate()->firstOrFail();
            $treasuryWallet = Wallet::whereHas('user', fn ($q) => $q->where('role', 'admin'))->lockForUpdate()->firstOrFail();

            if ($agentWallet->balance < $amount) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'Insufficient digital float balance to return to Treasury.'
                ]);
            }

            // State Mutation: Agent returns digital float to Admin Treasury
            $agentWallet->decrement('balance', $amount);
            $treasuryWallet->increment('balance', $amount);

            // Returning float back to Treasury reduces Agent's Payable Due to Admin
            if ($agentWallet->admin_due > 0) {
                $agentWallet->decrement('admin_due', min($amount, $agentWallet->admin_due));
            }

            // Ledger Entry
            Transaction::create([
                'txn_id' => uniqid('TXN_'),
                'type' => 'cash_out',
                'sender_id' => $agent->id,
                'receiver_id' => $treasuryWallet->user_id,
                'amount' => $amount,
                'fee' => 0.00
            ]);
        });

        return back()->with('status', "Float withdrawal of ৳{$amount} returned to Admin Treasury! Payable Due updated.");
    }

    // 5. Settle Dues with Admin Treasury step-by-step (Physical Cash Settlement)
    public function remitToAdmin(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $agent = Auth::user();
        $amount = round((float) $request->amount, 2);

        DB::transaction(function () use ($agent, $amount) {
            $agentWallet = Wallet::where('user_id', $agent->id)->lockForUpdate()->firstOrFail();
            $treasuryWallet = Wallet::whereHas('user', fn ($q) => $q->where('role', 'admin'))->lockForUpdate()->firstOrFail();

            if ($agentWallet->admin_due <= 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'You currently have no outstanding Payable Due to Admin Treasury.'
                ]);
            }

            // State Mutation: Agent hands physical cash over to Admin Treasury to settle Admin Due
            $agentWallet->decrement('admin_due', min($amount, $agentWallet->admin_due));
            $agentWallet->decrement('cash_in_hand', $amount);

            Transaction::create([
                'txn_id' => uniqid('TXN_'),
                'type' => 'commission',
                'sender_id' => $agent->id,
                'receiver_id' => $treasuryWallet->user_id,
                'amount' => $amount,
                'fee' => 0.00,
                'agent_commission' => 0.00,
                'admin_fee' => $amount
            ]);
        });

        return back()->with('status', "Physical cash settlement of ৳{$amount} paid to Admin Treasury! Payable Due updated.");
    }
}