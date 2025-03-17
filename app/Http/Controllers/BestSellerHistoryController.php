<?php

namespace App\Http\Controllers;

use App\Services\BestSellerHistoryService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;


class BestSellerHistoryController extends Controller
{
    protected const VALIDATION_ERROR_CODE = 422;

    public function __construct(
        protected BestSellerHistoryService $bestSellerHistoryService,
    ) {
    }

    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make(
            $request->all(),
            $this->getValidationRules(),
        );

        if ($validator->fails()) {
            $errors = $validator->errors(); // Get the errors
            return response()->json(['errors' => $errors], self::VALIDATION_ERROR_CODE);
        }

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

    protected function getValidationRules(): array
    {
        return [
            'author' => 'string|nullable',
            'isbn' => [
                'integer',
                'nullable',
                function (string $attribute, mixed $value, Closure $fail) {
                    // @todo check multiple ISBNs.
                    if ($value !== null && !in_array(strlen((string) $value), [10, 13])) {
                        $fail("The $attribute should be either absent or contain exactly 10 or 13 digits.");
                    }
                }
            ],
            'title' => 'string|nullable',
            'offset' => [
                'integer',
                'nullable',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($value !== null && $value % 20 !== 0) {
                        $fail("The $attribute must be a multiple of 20.");
                    }
                }
            ],
            'version' => 'integer|nullable|min:1|max:3',
        ];
    }
}
