<?php

namespace App\Http\Controllers;

use App\Services\BestSellerHistoryService;
use Illuminate\View\View;

class BestSellerHistory extends Controller
{
    public function __construct(
        protected BestSellerHistoryService $bestSellerHistoryService,
    ) {
    }

    public function get(): View
    {
        return view('best-seller-history', ['data' => $this->bestSellerHistoryService->get()]);
    }
}
