<?php

use App\Http\Controllers\BestSellerHistory;
use Illuminate\Support\Facades\Route;

Route::get('/api/1/best-seller-history', [BestSellerHistory::class, 'search']);
