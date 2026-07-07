<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AgentFundingRequest;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function index()
    {
        $admin = Auth::user();
        
      $pendingRequests = AgentFundingRequest::with('agent')
                                ->where('status', 'pending')
                                ->latest()
                                ->get();

        return view('admin.dashboard', compact('admin', 'pendingRequests'));
    }
}