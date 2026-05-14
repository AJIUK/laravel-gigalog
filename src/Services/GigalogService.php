<?php

namespace Gigalog\Services;

use Gigalog\Abstracts\GigalogEvent;
use Gigalog\Models\Gigalog;
use Gigalog\Support\GigalogEagerBag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class GigalogService
{
    public function create(
        GigalogEvent $event,
    ): Gigalog
    {
        $subject = $event->getSubject();
        $causer = $event->getCauser();
        $preparedData = $event->getPreparedData();
        $group = $event->getGroup();

        return Gigalog::create([
            'class_name' => $event::class,
            'version' => $event::VERSION,
            'subject_id' => $subject->getKey(),
            'subject_type' => $subject->getMorphClass(),
            'causer_id' => $causer?->getKey(),
            'causer_type' => $causer?->getMorphClass(),
            'data' => $preparedData,
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

        return $this->queryList($query, $perPage);
    }

    public function queryList(Builder $query, $perPage = 20)
    {
        $query->with(['subject', 'causer']);
        $query->orderBy('created_at', 'desc');
        $paginator = $query->paginate($perPage);
        $this->loadEagers($paginator->getCollection());
        return $paginator;
    }

    /**
     * Загрузить зависимости
     */
    public function loadEagers(Collection $gigalogs)
    {
        $eagerBag = new GigalogEagerBag();
        foreach ($gigalogs as &$gigalog) {
            if (!$gigalog instanceof Gigalog) continue;
            $resGen = $gigalog->getResGen();
            if (!$resGen) continue;
            $eagerBag->mergeBag($resGen->getEagerBag());
        }
        $eagerBag->load();
    }
}
