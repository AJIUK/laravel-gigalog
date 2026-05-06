<?php

namespace Gigalog\Models;

use Gigalog\Abstracts\GigalogEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property string $class_name
 * @property string|null $group
 * @property string $version
 * @property string $subject_type
 * @property int $subject_id
 * @property string|null $causer_type
 * @property int|null $causer_id
 * @property array<array-key, mixed>|null $data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent|null $causer
 * @property-read Model|\Eloquent $subject
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gigalog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gigalog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gigalog query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gigalog whereCauserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gigalog whereCauserType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gigalog whereClassName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gigalog whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gigalog whereData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gigalog whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gigalog whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gigalog whereSubjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gigalog whereSubjectType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gigalog whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Gigalog whereVersion($value)
 * @mixin \Eloquent
 */
class Gigalog extends Model
{
    protected $fillable = [
        'class_name',
        'version',
        'group',
        'causer_id',
        'causer_type',
        'data',
        'subject_id',
        'subject_type',
    ];

    protected $casts = [
        'data' => 'array',
    ];

    private ?GigalogEvent $_event = null;

    /**
     * Получить имя таблицы из конфига или использовать значение по умолчанию
     */
    public function getTable()
    {
        return config('gigalog.gigalog_table', 'gigalogs');
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function getEvent(): ?GigalogEvent
    {
        if ($this->_event) {
            return $this->_event;
        }

        $eventClass = $this->class_name;
        if (!class_exists($eventClass)) {
            return null;
        }
        if (!is_subclass_of($eventClass, GigalogEvent::class)) {
            return null;
        }

        $this->_event = new $eventClass($this);

        return $this->_event;
    }
}
