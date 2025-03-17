<?php

namespace App\Services;

use DomainException;
use Illuminate\Support\Facades\Http;

class BestSellerHistoryService
{
    protected const URI = 'https://api.nytimes.com/svc/books/v%d/lists/best-sellers/history.json?api-key=%s';
    protected const DEFAULT_VERSION = 3;

    protected string $apiKey = '';

    public function __construct()
    {
        $this->apiKey = config('services.nyt_api_key', '');
    }

    public function get(
        $version = self::DEFAULT_VERSION,
    ): array {
        if (empty($this->apiKey)) {
            return [];
        }

        $key = match ($version) {
            1 => 'body',
            2, 3 => 'results',
            default => throw new DomainException("Unknown version $version."),
        };

        $uri = sprintf(self::URI, $version, $this->apiKey);
        $rawResponse = Http::get($uri);
        $response = $rawResponse->json();

        if (empty($response)) {
            throw new DomainException("Empty response.");
        }

        return $response[$key];
    }
}
