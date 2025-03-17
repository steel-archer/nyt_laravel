<?php

namespace App\Http\Controllers;

use App\Services\BestSellerHistoryService;
use Illuminate\Http\JsonResponse;

class BestSellerHistory extends Controller
{
    public function __construct(
        protected BestSellerHistoryService $bestSellerHistoryService,
    ) {
    }

    public function search(): JsonResponse
    {
        $history = array_merge(
            [
                'results' => [],
                'numResults' => 0,
                'errors' => [],
            ],
            $this->bestSellerHistoryService->search(),
        );

        return response()->json($history);
    }
}
