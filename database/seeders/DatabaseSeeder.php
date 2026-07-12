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

        // 2. Create Admin Account (admin@gmail.com) -> 100,000 float
        $admin = User::create([
            'name' => 'System Administrator',
            'phone' => '01700000000',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('Haha@1234'),
            'role' => 'admin',
        ]);
        $admin->wallet()->update([
            'balance' => 100000.00,
            'cash_in_hand' => 0.00,
            'admin_due' => 0.00,
        ]);

        // 3. Create Agent Account (agent@gmail.com)
        $agent = User::create([
            'name' => 'Rahim Agent',
            'phone' => '01711111111',
            'email' => 'agent@gmail.com',
            'password' => Hash::make('Haha@1234'),
            'role' => 'agent',
        ]);
        $agent->wallet()->update([
            'balance' => 0.00,
            'cash_in_hand' => 0.00,
            'admin_due' => 0.00,
        ]);

        // 4. Create Customer Account (customer@gmail.com)
        $customer = User::create([
            'name' => 'Karim Customer',
            'phone' => '01722222222',
            'email' => 'customer@gmail.com',
            'password' => Hash::make('Haha@1234'),
            'role' => 'customer',
        ]);
        $customer->wallet()->update([
            'balance' => 0.00,
            'cash_in_hand' => 0.00,
            'admin_due' => 0.00,
        ]);

        // 5. Create Second Customer Account (itty@gmail.com)
        $itty = User::create([
            'name' => 'Itty',
            'phone' => '01733333333',
            'email' => 'itty@gmail.com',
            'password' => Hash::make('Haha@1234'),
            'role' => 'customer',
        ]);
        $itty->wallet()->update([
            'balance' => 0.00,
            'cash_in_hand' => 0.00,
            'admin_due' => 0.00,
        ]);
    }
}
