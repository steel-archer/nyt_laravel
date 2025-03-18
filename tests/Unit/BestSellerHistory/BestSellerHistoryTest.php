<?php

namespace Tests\Unit\BestSellerHistory;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BestSellerHistoryTest extends TestCase
{
    protected const CORRECT_RESPONSE_FILE = __DIR__ . '/stubs/correct.json';
    protected const CORRECT_RESPONSE_FILE_V1 = __DIR__ . '/stubs/correctV1.json';

    protected string $apiHost = '';
    protected string $apiKey = '';
    protected string $apiEndpoint = '';
    protected string $ourEndpoint = 'http://localhost/api/%d/best-seller-history';

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiHost = config('services.nyt_api_host', '');
        $this->apiKey = 'dummy_api_key';
        Config::set('services.nyt_api_key', $this->apiKey);
        $this->apiEndpoint = $this->apiHost . "/svc/books/v%d/lists/best-sellers/history.json?api-key=$this->apiKey*";
    }

    /**
     * @throws JsonException
     */
    #[DataProvider('correctDataProvider')]
    public function testCorrect(array $params, string $json): void
    {
        $version = $params['version'] ?? 3;
        $endpoint = sprintf($this->apiEndpoint, $version);
        Http::fake([
            $endpoint => Http::response($json),
        ]);

        $response = $this->get(
            route(
                'api.best-seller-history.search',
                $params,
            ),
        );

        self::assertEquals(200, $response->status());

        // We also add version to any correct response from NYT API.
        $jsonArray = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $jsonArray['version'] = $version;
        $json = json_encode($jsonArray, JSON_THROW_ON_ERROR);

        self::assertJsonStringEqualsJsonString($json, $response->getContent());
    }

    public static function correctDataProvider(): iterable
    {
        for ($version = 0; $version <=3; $version++) {
            // No version (so 3).
            if ($version === 0) {
                $params = [];
                yield [$params, file_get_contents(self::CORRECT_RESPONSE_FILE)];
            } elseif ($version === 1) {
                $params = [
                    'version' => 1,
                ];
                yield [$params, file_get_contents(self::CORRECT_RESPONSE_FILE_V1)];
            } else {
                $params = [
                    'version' => $version,
                ];
                yield [$params, file_get_contents(self::CORRECT_RESPONSE_FILE)];
            }
        }
    }
}
