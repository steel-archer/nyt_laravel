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

    public function testNoApiKey(): void
    {
        Config::set('services.nyt_api_key', '');
        $endpoint = sprintf($this->apiEndpoint, 3);
        Http::fake([
            $endpoint => Http::response(),
        ]);

        $response = $this->get(route('api.best-seller-history.search', []));

        self::assertEquals(500, $response->status());

        $json = '{"errors":"You must set an API host and key."}';
        self::assertJsonStringEqualsJsonString($json, $response->getContent());
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

        $response = $this->get(route('api.best-seller-history.search', $params));

        self::assertEquals(200, $response->status());

        // We also add version to any correct response from NYT API.
        $jsonArray = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $jsonArray['version'] = $version;
        $json = json_encode($jsonArray, JSON_THROW_ON_ERROR);

        self::assertJsonStringEqualsJsonString($json, $response->getContent());
    }

    public static function correctDataProvider(): iterable
    {
        for ($version = 0; $version <= 3; $version++) {
            if ($version === 0) { // No version (so 3).
                $basicParams = [];
                $content = file_get_contents(self::CORRECT_RESPONSE_FILE);
            } elseif ($version === 1) {
                $basicParams = ['version' => 1];
                $content = file_get_contents(self::CORRECT_RESPONSE_FILE_V1);
            } else {
                $basicParams = ['version' => $version];
                $content = file_get_contents(self::CORRECT_RESPONSE_FILE);
            }

            // Author
            $params = $basicParams;
            $params['author'] = '';
            yield [$params, $content];

            $params = $basicParams;
            $params['author'] = 'George R.R. Martin';
            yield [$params, $content];

            // Title
            $params = $basicParams;
            $params['title'] = '';
            yield [$params, $content];

            $params = $basicParams;
            $params['title'] = 'FIRE AND BLOOD';
            yield [$params, $content];

            // ISBN
            $params = $basicParams;
            $params['isbn'] = 1524796298;
            yield [$params, $content];

            $params = $basicParams;
            $params['isbn'] = 9781524796297;
            yield [$params, $content];

            // Offset
            $params = $basicParams;
            $params['offset'] = 0;
            yield [$params, $content];
        }
    }
}
