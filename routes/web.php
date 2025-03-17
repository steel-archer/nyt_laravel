<?php

use App\Http\Controllers\BestSellerHistory;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/best-seller-history', [BestSellerHistory::class, 'get']);
