<?php

namespace App\Jobs;

use App\Exception\CurrencyFailedException;
use App\Models\JobMetadata;
use DOMDocument;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use App\Models\Currencies;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Задача для получения и кэширования данных о курсах валют.
 */
class FetchCurrenciesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5; // Максимальное количество попыток выполнения задачи
    public int $backoff = 60; // Время ожидания между попытками выполнения задачи

    /**
     * Основной метод выполнения задачи.
     *
     * Проверяет наличие данных в кэше и, если их нет, запрашивает данные у внешнего API.
     * @throws CurrencyFailedException
     */
    public function handle(): void
    {
        Log::info("Attempt {$this->attempts()}: Starting FetchCurrenciesJob.");

        $key = 'currencies_data'; // Ключ для кэширования данных

        // Проверка наличия данных в кэше
        if (Cache::has($key)) {
            $this->logCachedData($key);
            return;
        }

        // Запрос данных, если они отсутствуют в кэше
        $this->fetchAndCacheCurrencyData();
    }

    /**
     * Запрашивает данные у внешнего API и кэширует их.
     * @throws CurrencyFailedException
     */
    protected function fetchAndCacheCurrencyData(): void
    {
        try {
            Log::info('Fetching currency data from external API.');
            $response = Http::get(config('currency.api_url'));

            if (!$response->successful()) {
                Log::error('Failed to fetch currency data from API.');
                throw new CurrencyFailedException('API response was unsuccessful.');
            }

            // Обработка и кэширование полученных данных
            $this->processResponse($response->body());

            Log::info('Currency data fetched successfully and cached.');
        } catch (Exception $e) {
            $this->logError('An exception occurred during the API call.', $e);
            throw $e;
        }
    }

    /**
     * Обрабатывает полученные XML данные и преобразует их в JSON.
     * @throws Exception
     */
    protected function processResponse(string $xmlContent): void
    {
        $this->convertXmlToJson($xmlContent);
    }

    /**
     * Конвертирует XML в JSON, добавляет данные о курсах валют в базу данных и кэш.
     * @throws Exception
     */
    protected function convertXmlToJson($xmlContent): void
    {
        $xmlContentUtf8 = mb_convert_encoding($xmlContent, 'UTF-8', 'Windows-1251');
        $xmlContentUtf8 = preg_replace('/<\?xml version="1.0" encoding="windows-1251"\?>/i', '<?xml version="1.0" encoding="UTF-8"?>', $xmlContentUtf8);

        $xml = simplexml_load_string($xmlContentUtf8);

        $date = Carbon::createFromFormat('d.m.Y', (string)$xml['Date'])->toDateString();
        Log::info($date);

        if ($xml === false) {
            throw new Exception("Failed to parse XML");
        }

        $currenciesData = [];
        foreach ($xml->Valute as $valute) {
            $currenciesData[] = [
                'char_code' => (string)$valute->CharCode,
                'name' => (string)$valute->Name, // Используем атрибут Name для названия валюты
                'value' => (double)str_replace(',', '.', $valute->Value),
                'date' => $date,
            ];
        }

        Currencies::upsertCurrencies($currenciesData, ['char_code', 'date'], ['value', 'name']);

        $responseData = [
            "status" => true,
            "message" => "Текущие курсы валют успешно обновлены",
            "data" => $currenciesData
        ];

        $jsonCurrenciesData = json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        Cache::put('currencies_data', $jsonCurrenciesData, now()->addHours(4));

        Log::info('FetchCurrenciesJob completed successfully. Data cached as JSON.');
    }


    /**
     * Логирует ошибки во время выполнения задачи.
     */
    protected function logError(string $message, Exception $e = null): void
    {
        Log::channel('custom')->error($message, [
            'exception' => $e ? $e->getMessage() : 'N/A',
        ]);
    }

    /**
     * Логирует данные, полученные из кэша. Была написана для контроля данных
     */
    protected function logCachedData($key): void
    {
        $cachedData = Cache::get($key);
        Log::info("Currency data retrieved from cache");
    }

    /**
     * Обрабатывает случаи неудачного выполнения задачи.
     */
    public function failed(Exception $exception): void
    {
        Log::error("Job failed on attempt {$this->attempts()}: {$exception->getMessage()}");

        JobMetadata::updateJobMetadata('fetch-currencies', [
            'last_attempt_failed' => now(),
            'failure_reason' => $exception->getMessage()
        ]);

        if ($this->attempts() < $this->tries) {
            Log::info("Retrying FetchCurrenciesJob. Attempt: {$this->attempts()}");
        } else {
            Log::info("Max attempts reached for FetchCurrenciesJob. Job permanently failed.");
        }
    }
}
