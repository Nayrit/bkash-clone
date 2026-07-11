<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'phone' => '01700000000',
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        \App\Models\SystemSetting::setVal('cash_out_fee_percentage', '2.00'); // 20 Tk per 1000
        \App\Models\SystemSetting::setVal('agent_commission_percentage', '1.50'); // 15 Tk per 1000
        \App\Models\SystemSetting::setVal('admin_fee_percentage', '0.50'); // 5 Tk per 1000
        \App\Models\SystemSetting::setVal('cash_in_commission_percentage', '1.50'); // 15 Tk per 1000 Cash-In Commission
    }
}


