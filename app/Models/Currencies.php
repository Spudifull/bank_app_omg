<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property $value
 * @method static upsert(array $values, array|string $uniqueBy, array|null $update = null)
 */
class Currencies extends Model
{
    use HasFactory;

    // Определение защищенных атрибутов для массового присваивания
    protected $fillable = ['char_code', 'value', 'date'];

    // Указание формата даты для атрибута
    protected $casts = [
        'date' => 'date',
    ];


    /**
     * Аксессор для получения значения валюты в форматированном виде.
     *
     * @return string
     */
    public function getFormattedValueAttribute(): string
    {
        return number_format($this->value, 2, '.', '') . ' RUB';
    }

    /**
     * Скоуп для фильтрации по дате.
     *
     * @param Builder $query
     * @param mixed $date
     * @return Builder
     */
    public function scopeFilterByDate(Builder $query, mixed $date): Builder
    {
        return $query->whereDate('date', $date);
    }

    /**
     * Обертка для метода upsert Eloquent.
     *
     * @param array $currenciesData Данные для вставки или обновления.
     * @param array $uniqueBy Уникальные атрибуты для определения существующих записей.
     * @param array $updateFields Поля для обновления в существующих записях.
     * @return void
     */
    public static function upsertCurrencies(array $currenciesData, array $uniqueBy, array $updateFields): void
    {
        self::upsert($currenciesData, $uniqueBy, $updateFields);
    }
}
