<?php

namespace App\Http\Controllers;

use App\Services\BestSellerHistoryService;
use Closure;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BestSellerHistoryController extends AbstractNytApiController
{
    public function __construct(
        protected BestSellerHistoryService $bestSellerHistoryService,
    ) {
    }

    public function search(Request $request): JsonResponse
    {
        $errors = $this->validate($request);
        if (!empty($errors->messages())) {
            return response()->json(['errors' => $errors], self::ERROR_CODE_VALIDATION);
        }

        try {
            // Do ?? if null has been passed.
            $searchResults = $this->bestSellerHistoryService->search(
                $request->get('author', '') ?? '',
                $request->get('isbn', 0) ?? 0,
                $request->get('title', '') ?? '',
                $request->get('offset', 0) ?? 0,
                $request->get('version', self::DEFAULT_VERSION) ?? self::DEFAULT_VERSION,
            );
        } catch (Exception $ex) {
            return response()->json(['errors' => [$ex->getMessage()]], self::ERROR_CODE_UNKNOWN);
        }

        $code = empty($searchResults['errors']) ? self::SUCCESS_CODE : self::ERROR_CODE_UNKNOWN;
        return response()->json($searchResults, $code);
    }

    protected function getValidationRules(): array
    {
        return [
            'author' => 'string|nullable',
            /**
             * N.B. Theoretically NYT API must support multiple ISBNs, separated by ";" character.
             * In practice I didn't manage to make it work (even with examples provided by NYT),
             * API just returns empty array.
             * So I made this parameter just an integer.
             */
            'isbn' => [
                'bail',
                'integer',
                'nullable',
                function (string $attribute, mixed $value, Closure $fail) {
                    if ($value !== null && !in_array(strlen((string) $value), [10, 13])) {
                        $fail("The $attribute should be either absent or contain exactly 10 or 13 digits.");
                    }
                }
            ],
            'title' => 'string|nullable',
            'offset' => [
                'bail',
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
