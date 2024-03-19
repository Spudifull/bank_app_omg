<?php

namespace App\Http\Controllers;

use App\Jobs\FetchCurrenciesJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class CurrencyController extends Controller
{

    /**
     * Получение общего списка курсов валют.
     *
     * Этот метод проверяет наличие кэшированных данных курсов валют.
     * Если данные найдены в кэше, возвращает их клиенту.
     * В противном случае инициирует задание для их получения и сообщает об этом клиенту.
     *
     * @return JsonResponse Ответ с данными о курсах валют или сообщение о запуске обновления.
     */
    public function getCurrencies(): JsonResponse
    {
        Log::info('getCurrencies call');

        $key = 'currencies_data';

        // Проверяем, есть ли данные в кэше
        if (Cache::has($key)) {
            $data = Cache::get($key);
            $cachedData = json_decode($data, true); // Декодируем данные из JSON

            // Возвращаем кэшированные данные
            return response()->json([
                "status" => $cachedData['status'],
                "message" => $cachedData['message'],
                "data" => $cachedData['data']
            ]);
        }

        // Если данных в кэше нет, отправляем задание на их получение
        Log::info('FetchCurrenciesJob call');
        dispatch(new FetchCurrenciesJob());

        // Возвращаем ответ о том, что данные ещё не готовы и их обновление было инициировано
        return response()->json([
            "status" => false,
            "message" => "Запрос на обновление курсов валют отправлен в обработку. Попробуйте позже.",
            "data" => null
        ], 202);
    }

    /**
     * Получение курса конкретной валюты.
     *
     * Метод пытается найти курс запрошенной валюты в кэше.
     * Если данные найдены, возвращает их клиенту.
     * Если данных нет, инициирует задание для их получения и сообщает клиенту, что данные еще не готовы.
     * Добавлен, как дополнительный показатель скила
     *
     * @param  Request  $request Запрос, содержащий код валюты.
     * @return JsonResponse Ответ с данными о курсе конкретной валюты или сообщение о запуске обновления.
     */
    public function getCurrency(Request $request): JsonResponse
    {
        Log::info('getCurrency call');

        $charCode = strtoupper($request->input('code')); // Приводим код валюты к верхнему регистру

        $key = 'currencies_data';

        if (Cache::has($key)) {
            $data = Cache::get($key);
            $allCurrencies = json_decode($data, true);

            // Проверяем, есть ли информация по запрошенной валюте в кэше
            $currencyData = collect($allCurrencies['data'])->where('char_code', $charCode)->first();

            if ($currencyData) {
                // Если данные по запрошенной валюте найдены в кэше
                return response()->json([
                    "status" => true,
                    "message" => "Курс валюты из кэша",
                    "data" => $currencyData
                ]);
            } else {
                // Если информации по запрошенной валюте нет
                return response()->json([
                    "status" => false,
                    "message" => "Информация по данной валюте отсутствует",
                    "data" => null
                ], 404);
            }
        }

        // Если данных в кэше нет, отправляем задание на получение данных
        Log::info('FetchCurrenciesJob call');
        dispatch(new FetchCurrenciesJob());

        // Возвращаем ответ, что данные в процессе обновления
        return response()->json([
            "status" => false,
            "message" => "Данные о курсах валют обновляются. Попробуйте обратиться позже.",
            "data" => null
        ], 202);
    }
}
