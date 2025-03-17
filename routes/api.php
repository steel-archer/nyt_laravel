<?php

use App\Http\Controllers\BestSellerHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/api/1/best-seller-history', [BestSellerHistoryController::class, 'search']);
