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
});


// ------------- AGENT ROUTES -------------
Route::middleware(['auth', 'role:agent'])->group(function () {
    
    Route::get('/agent/dashboard', [AgentController::class, 'index'])->name('agent.dashboard');
    Route::post('/agent/request-funds', [AgentController::class, 'requestFunds'])->name('agent.request.funds');

});


// ------------- CUSTOMER ROUTES -------------
Route::middleware(['auth', 'role:customer'])->group(function () {
    
    Route::get('/customer/dashboard', [CustomerController::class, 'index'])->name('customer.dashboard');
    
    // The Send Money Action
    Route::post('/customer/send-money', [CustomerController::class, 'sendMoney'])->name('customer.send.money');

});



// ------------- BREEZE PROFILE ROUTES -------------
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';