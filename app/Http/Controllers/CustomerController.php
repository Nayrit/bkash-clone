<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Transaction;

class CustomerController extends Controller
{
    // 1. Show the Dashboard
    public function index()
    {
        $user = Auth::user();
        
        // Get the user's transaction history (both sent and received)
        $transactions = Transaction::with(['sender', 'receiver'])
                                   ->where('sender_id', $user->id)
                                   ->orWhere('receiver_id', $user->id)
                                   ->latest()
                                   ->limit(25)
                                   ->get();

        return view('customer.dashboard', compact('user', 'transactions'));
    }

    // 2. Process the Send Money Request (P2P Transfer)
    public function sendMoney(Request $request)
    {
        // 1. Strict Validation
        $request->validate([
            'phone' => 'required|string|exists:users,phone',
            'amount' => 'required|numeric|min:10', // Minimum 10 BDT
        ]);

        $sender = Auth::user();
        $receiver = User::where('phone', $request->phone)->first();
        $amount = round((float) $request->amount, 2);

        // 2. Security Checks
        if ($sender->id === $receiver->id) {
            return back()->withErrors(['phone' => 'You cannot send money to yourself.']);
        }

        $fee = round((float) \App\Models\SystemSetting::getVal('send_money_fee_flat', 5.00), 2);
        $totalRequired = round($amount + $fee, 2);

        // 3. Database Transaction with Pessimistic Locking
        DB::transaction(function () use ($sender, $receiver, $amount, $fee, $totalRequired) {
            $senderWallet = \App\Models\Wallet::where('user_id', $sender->id)->lockForUpdate()->firstOrFail();
            $receiverWallet = \App\Models\Wallet::where('user_id', $receiver->id)->lockForUpdate()->firstOrFail();
            $treasuryWallet = \App\Models\Wallet::whereHas('user', fn ($q) => $q->where('role', 'admin'))->lockForUpdate()->first();

            if ($senderWallet->balance < $totalRequired) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => "Insufficient balance. Send Money requires amount + ৳{$fee} fee (Total: ৳{$totalRequired})."
                ]);
            }

            // Deduct from Sender (Principal + 5 Taka Fee)
            $senderWallet->decrement('balance', $totalRequired);

            // Add Principal to Receiver
            $receiverWallet->increment('balance', $amount);

            // 5 Taka goes directly to Admin Treasury
            if ($treasuryWallet) {
                $treasuryWallet->increment('balance', $fee);
            }

            // Create Immutable Ledger Receipt
            Transaction::create([
                'txn_id' => uniqid('TXN_'),
                'type' => 'send_money',
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'amount' => $amount,
                'fee' => $fee,
                'agent_commission' => 0.00,
                'admin_fee' => $fee
            ]);
        });

        return back()->with('status', 'Money sent successfully to ' . $receiver->name . '!');
    }

    // 3. Process Customer Cash-Out to Agent
    public function cashOut(Request $request)
    {
        $request->validate([
            'agent_phone' => 'required|string|exists:users,phone',
            'amount' => 'required|numeric|min:50', // Minimum 50 BDT for Cash Out
        ]);

        $customer = Auth::user();
        $agent = User::where('phone', $request->agent_phone)->first();

        if (!$agent || $agent->role !== 'agent') {
            return back()->withErrors(['agent_phone' => 'The provided phone number is not a registered Agent account.']);
        }

        $amount = round((float) $request->amount, 2);
        
        // Fee Settings: 2% total (20/1000), 1.5% Agent (15/1000), 0.5% Admin (5/1000)
        $feeRate = (float) \App\Models\SystemSetting::getVal('cash_out_fee_percentage', 2.00);
        $agentCommissionRate = (float) \App\Models\SystemSetting::getVal('agent_commission_percentage', 1.50);

        $fee = round($amount * ($feeRate / 100), 2);
        $agentCommission = round($amount * ($agentCommissionRate / 100), 2);
        $adminRevenue = round($fee - $agentCommission, 2);
        $totalRequired = round($amount + $fee, 2);

        DB::transaction(function () use ($customer, $agent, $amount, $fee, $agentCommission, $adminRevenue, $totalRequired) {
            // Lock Customer, Agent, and Admin Treasury wallets to prevent race conditions
            $customerWallet = \App\Models\Wallet::where('user_id', $customer->id)->lockForUpdate()->firstOrFail();
            $agentWallet = \App\Models\Wallet::where('user_id', $agent->id)->lockForUpdate()->firstOrFail();
            $treasuryWallet = \App\Models\Wallet::whereHas('user', fn ($q) => $q->where('role', 'admin'))->lockForUpdate()->first();

            if ($customerWallet->balance < $totalRequired) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => "Insufficient balance. Total required including fee (৳{$fee}): ৳{$totalRequired}."
                ]);
            }

            // State Mutation
            // 1. Customer pays e-money float (Amount + 20/1000 Fee)
            $customerWallet->decrement('balance', $totalRequired);
            
            // 2. Agent receives e-money float (Amount + 15/1000 Commission) and returns physical hand cash to Customer
            $agentWallet->increment('balance', $amount + $agentCommission);
            $deductHandCash = min($amount, $agentWallet->cash_in_hand);
            if ($deductHandCash > 0) {
                $agentWallet->decrement('cash_in_hand', $deductHandCash);
            }

            // 3. Admin Treasury receives 5/1000 Platform Revenue
            if ($treasuryWallet) {
                $treasuryWallet->increment('balance', $adminRevenue);
            }

            // Single Ledger Entry containing complete breakdown
            Transaction::create([
                'txn_id' => uniqid('TXN_'),
                'type' => 'cash_out',
                'sender_id' => $customer->id,
                'receiver_id' => $agent->id,
                'amount' => $amount,
                'fee' => $fee,
                'agent_commission' => $agentCommission,
                'admin_fee' => $adminRevenue
            ]);
        });

        return back()->with('status', "Cash-Out of ৳{$amount} completed! Total Fee: ৳{$fee} (Agent Commission: ৳{$agentCommission}).");
    }
}