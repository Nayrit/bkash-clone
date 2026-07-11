<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    // Protect against mass-assignment vulnerabilities
    protected $fillable = ['user_id', 'balance', 'cash_in_hand', 'admin_due'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}