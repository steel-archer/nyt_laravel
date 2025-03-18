<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class BestSellerHistoryService extends AbstractNytApiService
{
    protected const URI = '{+endpoint}/svc/books/v{version}/lists/best-sellers/history.json?api-key={apiKey}&author={author}&title={title}&offset={offset}';
    protected const URI_ISBN_PART = '&isbn={isbn}';

    public function search(
        string $author,
        int $isbn,
        string $title,
        int $offset,
        int $version,
    ): array {
        if (empty($this->apiHost) || empty($this->apiKey)) {
            return ['errors' => 'You must set an API host and key.'];
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
            }

            $rawResponse = Http::withUrlParameters($params)->get($rawUri);
        } catch (ConnectionException) {
            return ['errors' => ['Connection exception.']];
        }

        return $this->processResult($rawResponse, $version);
    }
}
