<?php

namespace App\Services;

use DomainException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class BestSellerHistoryService
{
    protected const URI = '{+endpoint}/svc/books/v{version}/lists/best-sellers/history.json?api-key={apiKey}&author={author}&title={title}&offset={offset}';
    protected const URI_ISBN_PART = '&isbn={isbn}';

    protected string $apiHost = '';
    protected string $apiKey = '';

    public function __construct()
    {
        $this->apiHost = config('services.nyt_api_host', '');
        $this->apiKey = config('services.nyt_api_key', '');
    }

    public function search(
        string $author,
        int $isbn,
        string $title,
        int $offset,
        int $version,
    ): array {
        if (empty($this->apiHost) || empty($this->apiKey)) {
            throw new RuntimeException('You must set an API host and key.');
        }

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

        // V1
        if (!empty($response['headers']['errors'])) {
            return ['errors' => $response['headers']['errors']];
        }

        // V2 and V3
        if (!empty($response['errors'])) {
            return ['errors' => $response['errors']];
        }

        if ($version === 1) {
            return [
                'results' => $response['body'],
                'numResults' => $response['headers']['num_results'],
            ];
        }

        return [
            'results' => $response['results'],
            'numResults' => $response['num_results'],
        ];
    }
}
