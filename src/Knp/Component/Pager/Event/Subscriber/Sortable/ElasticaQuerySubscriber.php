<?php

namespace Knp\Component\Pager\Event\Subscriber\Sortable;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Knp\Component\Pager\Event\ItemsEvent;
use Elastica\Query;
use Elastica\SearchableInterface;

class ElasticaQuerySubscriber implements EventSubscriberInterface
{
    public function items(ItemsEvent $event)
    {
        if (!is_array($event->target) || count($event->target) !== 2) {
            return;
        }

        [$searchable, $query] = $event->target;

        if (!$searchable instanceof SearchableInterface || !$query instanceof Query) {
            return;
        }

        $parametersResolver = $event->getParametersResolver();
        $field = $parametersResolver->getFieldToSort(
            $event->options['sortFieldParameterName'],
            $event->options['defaultSortFieldName'] ?? null
        );

        if ($field === null) {
            return;
        }

        $direction = $parametersResolver->getDirection(
            $event->options['sortDirectionParameterName'],
            $event->options['defaultSortDirection'] ?? 'asc'
        );

        $whiteList = $event->options['sortFieldWhitelist'] ?? [];
        if (count($whiteList) !== 0 && !in_array($field, $whiteList, true)) {
            throw new \UnexpectedValueException(
                sprintf('Cannot sort by: [%s] this field is not in whitelist', $field)
            );
        }

        $query->setSort([
            $field => ['order' => $direction],
        ]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'knp_pager.items' => ['items', 1]
        ];
    }
}
