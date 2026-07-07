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
        $transactions = Transaction::where('sender_id', $user->id)
                                   ->orWhere('receiver_id', $user->id)
                                   ->latest()
                                   ->limit(10) // Show only the last 10 for the dashboard
                                   ->get();

        return view('customer.dashboard', compact('user', 'transactions'));
    }

    // 2. Process the Send Money Request
    public function sendMoney(Request $request)
    {
        // 1. Strict Validation
        $request->validate([
            'phone' => 'required|string|exists:users,phone', // Must be a registered user
            'amount' => 'required|numeric|min:10', // Minimum 10 BDT
        ]);

        $sender = Auth::user();
        $receiver = User::where('phone', $request->phone)->first();
        $amount = $request->amount;

        // 2. Security Checks
        if ($sender->id === $receiver->id) {
            return back()->withErrors(['phone' => 'You cannot send money to yourself.']);
        }

        if ($sender->wallet->balance < $amount) {
            return back()->withErrors(['amount' => 'Insufficient balance.']);
        }

        // 3. The Database Transaction (All or Nothing)
        DB::transaction(function () use ($sender, $receiver, $amount) {
            
            // Deduct from Sender
            $sender->wallet->decrement('balance', $amount);
            
            // Add to Receiver
            $receiver->wallet->increment('balance', $amount);

            // Create Receipt
            Transaction::create([
                'txn_id' => uniqid('TXN_'),
                'type' => 'send_money',
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'amount' => $amount,
                'fee' => 0.00 // Assuming no fee for now
            ]);
        });

        return back()->with('status', 'Money sent successfully to ' . $receiver->name . '!');
    }
}