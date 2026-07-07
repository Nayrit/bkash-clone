<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AgentFundingRequest;
use Illuminate\Support\Facades\Auth;

class AgentController extends Controller
{
    // 1. Show the Dashboard
    public function index()
    {
        $agent = Auth::user();
        
        // Fetch this specific agent's previous requests so they can track them
        $fundingRequests = AgentFundingRequest::where('agent_id', $agent->id)
                                ->latest()
                                ->get();

        return view('agent.dashboard', compact('agent', 'fundingRequests'));
    }

    // 2. Process the Request
    public function requestFunds(Request $request)
    {
        // Strictly validate that they are asking for a real number (minimum 500 BDT)
        $request->validate([
            'amount' => 'required|numeric|min:500'
        ]);

        // Insert the request into the database
        AgentFundingRequest::create([
            'agent_id' => Auth::id(),
            'amount' => $request->amount,
            'status' => 'pending'
        ]);

        // Send them back to the dashboard with a success message
        return back()->with('status', 'Funding request successfully sent to the Treasury!');
    }
}