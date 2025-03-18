<?php

namespace App\Services;

use Illuminate\Http\Client\Response;

abstract class AbstractNytApiService
{
    protected string $apiHost = '';
    protected string $apiKey = '';

    public function __construct()
    {
        $this->apiHost = config('services.nyt_api_host', '');
        $this->apiKey = config('services.nyt_api_key', '');
    }

    protected function processResult(Response $rawResponse, int $version): array
    {
        $response = $rawResponse->json();

        if (empty($response)) {
            return ['errors' => ['Empty response.']];
        }

        if (!empty($response['fault']['faultstring'])) {
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

        $response['version'] = $version;

        return $response;
    }
}
