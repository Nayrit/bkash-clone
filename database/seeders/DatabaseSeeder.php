<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use App\Models\SystemSetting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Initialize Default Platform Fee & Commission Settings
        SystemSetting::setVal('send_money_fee_flat', '5.00');         // Flat 5 BDT per Send Money
        SystemSetting::setVal('cash_out_fee_percentage', '2.00');     // 2% Total Cash-Out Fee (20 Tk per 1000)
        SystemSetting::setVal('agent_commission_percentage', '1.50'); // 1.5% Agent Share (15 Tk per 1000)
        SystemSetting::setVal('admin_fee_percentage', '0.50');        // 0.5% Admin Share (5 Tk per 1000)

        // 2. Create System Administrator
        $admin = User::create([
            'name' => 'System Administrator',
            'phone' => '01700000000',
            'email' => 'admin@bkash.test',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);
        Wallet::create([
            'user_id' => $admin->id,
            'balance' => 100000.00, // Initial System Float: 100,000 BDT
            'cash_in_hand' => 0.00,
            'admin_due' => 0.00,
        ]);

        // 3. Create Default Test Agent
        $agent = User::create([
            'name' => 'Rahim Agent',
            'phone' => '01711111111',
            'email' => 'agent@bkash.test',
            'password' => Hash::make('password'),
            'role' => 'agent',
        ]);
        Wallet::create([
            'user_id' => $agent->id,
            'balance' => 0.00,
            'cash_in_hand' => 0.00,
            'admin_due' => 0.00,
        ]);

        // 4. Create Default Test Customer
        $customer = User::create([
            'name' => 'Karim Customer',
            'phone' => '01722222222',
            'email' => 'customer@bkash.test',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);
        Wallet::create([
            'user_id' => $customer->id,
            'balance' => 0.00,
            'cash_in_hand' => 0.00,
            'admin_due' => 0.00,
        ]);
    }
}
