<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AgentFundingRequest extends Model
{
    // Whitelist the columns we are allowing the controller to save
    protected $fillable = [
        'agent_id', 
        'amount', 
        'status'
    ];

    // You likely already have this from earlier, but ensure the relationship exists!
    public function agent()
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}