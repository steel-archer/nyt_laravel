<?php

namespace Tests\Unit\BestSellerHistory;

use App\Services\BestSellerHistoryService;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception as MockException;
use Tests\TestCase;

class BestSellerHistoryTest extends TestCase
{
    protected const CORRECT_RESPONSE_FILE = __DIR__ . '/stubs/correct.json';
    protected const CORRECT_RESPONSE_FILE_V1 = __DIR__ . '/stubs/correctV1.json';
    protected const EMPTY_RESPONSE_FILE = __DIR__ . '/stubs/empty.json';
    protected const EMPTY_RESPONSE_FILE_V1 = __DIR__ . '/stubs/emptyV1.json';

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

        $response = $this->get(route('api.best-seller-history.search'));

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

            $params = $basicParams;
            $params['offset'] = 20;
            $content = $version === 1
                ? file_get_contents(self::EMPTY_RESPONSE_FILE_V1)
                : file_get_contents(self::EMPTY_RESPONSE_FILE);
            yield [$params, $content];
        }
    }

    /**
     * @throws JsonException
     */
    #[DataProvider('validationErrorsDataProvider')]
    public function testValidationErrors(array $params, array $errors): void
    {
        $params['version'] = 3;
        $response = $this->get(route('api.best-seller-history.search', $params));

        self::assertEquals(422, $response->status());

        self::assertJsonStringEqualsJsonString(json_encode(['errors' => $errors], JSON_THROW_ON_ERROR), $response->getContent());
    }

    public static function validationErrorsDataProvider(): iterable
    {
        yield [['isbn' => 10], ['isbn' => ['The isbn should be either absent or contain exactly 10 or 13 digits.']]];
        yield [['offset' => 10], ['offset' => ['The offset must be a multiple of 20.']]];
        yield [['offset' => 'aa'], ['offset' => ['The offset field must be an integer.']]];
    }

    /**
     * @throws JsonException
     * @throws MockException
     */
    public function testGeneralException(): void
    {
        $mockService = $this->createMock(BestSellerHistoryService::class);
        $mockService->method('search')->willThrowException(new Exception('Unknown error'));
        $this->app->instance(BestSellerHistoryService::class, $mockService);

        $params['version'] = 3;
        $response = $this->get(route('api.best-seller-history.search', $params));

        self::assertEquals(500, $response->status());

        $errors = ['errors' => ['Unknown error']];

        self::assertJsonStringEqualsJsonString(json_encode($errors, JSON_THROW_ON_ERROR), $response->getContent());
    }

    public function testConnectionException()
    {
        $params['version'] = 3;
        $endpoint = sprintf($this->apiEndpoint, 3);
        Http::fake([
            $endpoint => function () {
                throw new ConnectionException();
            }
        ]);

        $response = $this->get(route('api.best-seller-history.search', $params));

        $errors = ['errors' => ['Connection exception.']];

        self::assertJsonStringEqualsJsonString(json_encode($errors, JSON_THROW_ON_ERROR), $response->getContent());
    }
}
