<?php

namespace App\Services;

use DomainException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BestSellerHistoryService
{
    protected const URI = '{+endpoint}/svc/books/v{version}/lists/best-sellers/history.json?api-key={apiKey}&author={author}&title={title}&offset={offset}';
    protected const URI_ISBN_PART = '&isbn={isbn}';
    protected const DEFAULT_VERSION = 3;

    protected string $apiHost = '';
    protected string $apiKey = '';

    public function __construct()
    {
        $this->apiHost = config('services.nyt_api_host', '');
        $this->apiKey = config('services.nyt_api_key', '');
    }

    public function search(
        string $author = '',
        int $isbn = 0,
        string $title = '',
        int $offset = 0,
        int $version = self::DEFAULT_VERSION,
    ): array {
        if (empty($this->apiHost) || empty($this->apiKey)) {
            return [];
        }

        // @todo number; errors
        $resultKey = match ($version) {
            1 => 'body',
            2, 3 => 'results',
            default => throw new DomainException("Unknown version $version."),
        };

        try {
            $rawUri = self::URI;
            $params = [
                'endpoint' => $this->apiHost,
                'apiKey' => $this->apiKey,
                'version' => $version,
                'author' => $author,
                'title' => $title,
                'offset' => $offset,
            ];

            /**
             * Looks like ISBN should be either valid or absent at all.
             * So by default we do not include it.
             */
            if (!empty($isbn)) {
                $params['isbn'] = $isbn;
                $rawUri .= self::URI_ISBN_PART;
                // @todo check what's wrong with multiple ISBNs.
            }

            $rawResponse = Http::withUrlParameters($params)->get($rawUri);
        } catch (ConnectionException) {
            return ['errors' => "Connection exception."];
        }

        $response = $rawResponse->json();

        if (empty($response)) {
            return ['errors' => "Empty response."];
        }

        if (!empty($response['fault'])) {
            return ['errors' => [$response['fault']['faultstring']]];
        }

        if (!empty($response['errors'])) {
            return ['errors' => $response['errors']];
        }

        return [
            'results' => $response[$resultKey],
            'numResults' => $response['num_results'], // @todo For V1 it's headers.num_results
        ];
    }
}
