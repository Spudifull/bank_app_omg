<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property array $additional_info
 * @method static updateOrCreate(string[] $array, array $attributes)
 * @method static where(string $string, string $string1)
 */
class JobMetadata extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_name', 'last_successful_run', 'additional_info'
    ];

    protected $casts = [
        'last_successful_run' => 'datetime',
        'additional_info' => 'array',
    ];

    /**
     * Обновляет метаданные задачи с новым временем успешного выполнения и дополнительными атрибутами.
     *
     * Этот метод автоматически использует Eloquent метод updateOrCreate для обновления или создания записи.
     *
     * @param string $jobName Имя задачи
     * @param array $attributes Дополнительные атрибуты для обновления
     * @return void
     */
    public static function updateJobMetadata(string $jobName, array $attributes): void
    {
        static::updateOrCreate(['job_name' => $jobName], $attributes);
    }

    /**
     * Дополнительный метод для управления информацией о задаче.
     *
     * @param string $infoKey Ключ информации
     * @param mixed $infoValue Значение информации
     */
    public function addOrUpdateInfo(string $infoKey, mixed $infoValue): void
    {
        $additionalInfo = $this->additional_info ?? [];
        $additionalInfo[$infoKey] = $infoValue;
        $this->additional_info = $additionalInfo;
        $this->save();
    }
}
