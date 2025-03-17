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
        $history = array_merge(
            [
                'results' => [],
                'numResults' => 0,
                'errors' => [],
            ],
            $this->bestSellerHistoryService->get(),
        );
        return view('best-seller-history', $history);
    }
}
