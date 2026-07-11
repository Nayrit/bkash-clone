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

        // 3. Database Transaction with Pessimistic Locking
        DB::transaction(function () use ($sender, $receiver, $amount) {
            $senderWallet = \App\Models\Wallet::where('user_id', $sender->id)->lockForUpdate()->firstOrFail();
            $receiverWallet = \App\Models\Wallet::where('user_id', $receiver->id)->lockForUpdate()->firstOrFail();

            if ($senderWallet->balance < $amount) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => 'Insufficient balance.'
                ]);
            }

            // Deduct from Sender
            $senderWallet->decrement('balance', $amount);

            // Add to Receiver
            $receiverWallet->increment('balance', $amount);

            // Create Immutable Ledger Receipt
            Transaction::create([
                'txn_id' => uniqid('TXN_'),
                'type' => 'send_money',
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'amount' => $amount,
                'fee' => 0.00
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

        $fee = round($amount * ($feeRate / 100), 2);                  // 20 Taka per 1,000
        $agentCommission = round($amount * ($agentCommissionRate / 100), 2); // 15 Taka per 1,000
        $adminRevenue = round($fee - $agentCommission, 2);            // 5 Taka per 1,000
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
            // 1. Customer pays Amount + 20/1000 Fee
            $customerWallet->decrement('balance', $totalRequired);
            
            // 2. Agent receives Amount + 15/1000 Commission
            $agentWallet->increment('balance', $amount + $agentCommission);

            // 3. Admin Treasury receives 5/1000 Platform Revenue
            if ($treasuryWallet) {
                $treasuryWallet->increment('balance', $adminRevenue);
            }

            // Primary Ledger Entry: Customer Cash-Out
            Transaction::create([
                'txn_id' => uniqid('TXN_'),
                'type' => 'cash_out',
                'sender_id' => $customer->id,
                'receiver_id' => $agent->id,
                'amount' => $amount,
                'fee' => $fee
            ]);

            // Secondary Ledger Entry: Agent Commission Receipt
            if ($agentCommission > 0) {
                Transaction::create([
                    'txn_id' => uniqid('TXN_'),
                    'type' => 'commission',
                    'sender_id' => $treasuryWallet ? $treasuryWallet->user_id : $customer->id,
                    'receiver_id' => $agent->id,
                    'amount' => $agentCommission,
                    'fee' => 0.00
                ]);
            }
        });

        return back()->with('status', "Cash-Out of ৳{$amount} completed! Total Fee: ৳{$fee} (Agent Commission: ৳{$agentCommission}).");
    }
}