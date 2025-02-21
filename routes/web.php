<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;

Route::get('/', [OrderController::class, 'index'])->name('order.list');
Route::get('/order/detail/{id}', [OrderController::class, 'detail'])->name('order.detail');
Route::post('/order/add', [OrderController::class, 'add'])->name('order.add');
Route::post('/order/payment/update', [OrderController::class, 'updatePayment'])->name('order.payment.update');
Route::post('/order/auth', [OrderController::class, 'auth'])->name('order.auth');
