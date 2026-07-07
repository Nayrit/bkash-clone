<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

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
    Route::get('/agent/dashboard', function () {
        return "Agent Dashboard Coming Soon";
    })->name('agent.dashboard');
});


// ------------- CUSTOMER ROUTES -------------
Route::middleware(['auth', 'role:customer'])->group(function () {
    Route::get('/customer/dashboard', function () {
        return "Customer Dashboard Coming Soon";
    })->name('customer.dashboard');
});


// ------------- BREEZE PROFILE ROUTES -------------
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';