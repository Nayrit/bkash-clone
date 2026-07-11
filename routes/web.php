<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AgentController;
use App\Http\Controllers\CustomerController;






Route::get('/', function () {
    return view('welcome');
});

// ------------- THE TRAFFIC COP -------------
// Breeze redirects here after login. We instantly reroute them based on role.
Route::get('/dashboard', function () {
    $role = auth()->user()->role;
    
    if ($role === 'admin') {
        return redirect()->route('admin.dashboard');
    } elseif ($role === 'agent') {
        return redirect()->route('agent.dashboard');
    } else {
        return redirect()->route('customer.dashboard');
    }
})->middleware(['auth', 'verified'])->name('dashboard');


// ------------- ADMIN ROUTES -------------
Route::middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/admin/dashboard', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/transactions', [AdminController::class, 'transactions'])->name('admin.transactions');
    Route::post('/admin/fund-request/{id}/approve', [AdminController::class, 'approveRequest'])->name('admin.request.approve');
});



// ------------- AGENT ROUTES -------------
Route::middleware(['auth', 'role:agent'])->group(function () {
    Route::get('/agent/dashboard', [AgentController::class, 'index'])->name('agent.dashboard');
    Route::post('/agent/request-funds', [AgentController::class, 'requestFunds'])->name('agent.request.funds');
    Route::post('/agent/cash-in', [AgentController::class, 'cashIn'])->name('agent.cash.in');
    Route::post('/agent/cash-out', [AgentController::class, 'cashOut'])->name('agent.cash.out');
});


// ------------- CUSTOMER ROUTES -------------
Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/customer/dashboard', [CustomerController::class, 'index'])->name('customer.dashboard');
    Route::post('/customer/send-money', [CustomerController::class, 'sendMoney'])->name('customer.send.money');
    Route::post('/customer/cash-out', [CustomerController::class, 'cashOut'])->name('customer.cash.out');
});




// ------------- BREEZE PROFILE ROUTES -------------
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';