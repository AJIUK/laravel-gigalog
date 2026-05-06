<?php

namespace Gigalog\Services;

use Gigalog\Abstracts\GigalogEvent;
use Gigalog\Models\Gigalog;
use Gigalog\Support\GigalogEagerBag;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class GigalogService
{
    public function create(
        string $eventClassName,
        Model $subject,
        ?Model $causer = null,
        ?array $data = null,
    ): Gigalog
    {
        if (!class_exists($eventClassName)) {
            throw new \InvalidArgumentException("Class $eventClassName does not exist");
        }
        if (!is_subclass_of($eventClassName, GigalogEvent::class)) {
            throw new \InvalidArgumentException("Class $eventClassName is not a subclass of GigalogEvent");
        }

        $group = $eventClassName::getGroup();

        return Gigalog::create([
            'class_name' => $eventClassName,
            'version' => $eventClassName::VERSION,
            'subject_id' => $subject->getKey(),
            'subject_type' => $subject->getMorphClass(),
            'causer_id' => $causer?->getKey(),
            'causer_type' => $causer?->getMorphClass(),
            'data' => $data,
            'group' => $group?->getCode(),
        ]);
    }

    public function list(
        ?Model $subject = null,
        ?Model $causer = null,
        ?string $group = null,
        $perPage = 20
    )
    {
        $query = Gigalog::query();

        $query->orderBy('created_at', 'desc');

        if ($subject) {
            $query->where('subject_id', $subject->getKey());
            $query->where('subject_type', $subject->getMorphClass());
        }
        if ($causer) {
            $query->where('causer_id', $causer->getKey());
            $query->where('causer_type', $causer->getMorphClass());
        }
        if ($group) {
            $query->where('group', $group);
        }

        $query->with(['subject', 'causer']);

        $paginator = $query->paginate($perPage);

        $this->loadEagers($paginator->getCollection());

        return $paginator;
    }

    /**
     * Загрузить зависимости
     */
    public function loadEagers(Collection $items)
    {
        $eagerBag = new GigalogEagerBag();
        foreach ($items as &$item) {
            if (!$item instanceof Gigalog) continue;
            $event = $item->getEvent();
            if (!$event) continue;
            $eagerBag->mergeBag($event->getEagerBag());
        }
        $eagerBag->load();
    }
}
