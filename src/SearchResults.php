<?php

namespace BimTheBam\Meilisearch;

use BimTheBam\Meilisearch\SearchResults\Result;
use SilverStripe\Control\Controller;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\PaginatedList;
use SilverStripe\ORM\SS_List;
use SilverStripe\View\ViewableData;

/**
 * Class SearchResults
 * @package BimTheBam\Meilisearch
 * @property string $IndexUniqueKey
 * @property int $Limit
 * @property int $EstimatedTotal
 * @property bool $OverLimit
 * @property int $Count
 * @property ArrayList|null $List
 */
class SearchResults extends ViewableData
{
    /**
     * @var ArrayList|null
     */
    protected ?ArrayList $results = null;

    /**
     * @var int
     */
    protected int $limit = 100;

    /**
     * @var int
     */
    protected int $estimatedTotal = 0;

    /**
     * @param Index $index
     */
    public function __construct(protected readonly Index $index, protected readonly string $q)
    {
        parent::__construct();
    }

    /**
     * @return string
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function getIndexUniqueKey(): string
    {
        return $this->index->getUniqueKey();
    }

    /**
     * @return string
     */
    public function getQ(): string
    {
        return $this->q;
    }

    /**
     * @return int
     */
    public function getLimit(): int
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setLimit(int $limit): self
    {
        $this->limit = $limit > 0 ? $limit : 100;

        return $this;
    }

    /**
     * @return int
     */
    public function getEstimatedTotal(): int
    {
        return $this->estimatedTotal;
    }

    /**
     * @param int $total
     * @return $this
     */
    public function setEstimatedTotal(int $total): self
    {
        $this->estimatedTotal = $total >= 0 ? $total : 0;

        return $this;
    }

    /**
     * @return bool
     */
    public function getOverLimit(): bool
    {
        return $this->estimatedTotal > $this->limit;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->results?->count() ?? 0;
    }

    /**
     * @param Result $result
     * @return $this
     */
    public function add(Result $result): self
    {
        if ($this->results === null) {
            $this->results = ArrayList::create();
        }

        $this->results->add($result);

        return $this;
    }

    /**
     * @return array
     */
    public function getIDs(): array
    {
        return $this->results?->column('RecordID') ?? [];
    }

    /**
     * @var array
     */
    protected array $cachedList = [];

    /**
     * @param bool $paginated
     * @param int $perPage
     * @return SS_List|null
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Throwable
     */
    public function getList(bool $paginated = false, int $perPage = 10): ?SS_List
    {
        if ($this->results === null) {
            return null;
        }

        $cacheKey = (int)$paginated . '_' . $perPage;

        if (($results = ($this->cachedList[$cacheKey] ?? null)) !== null) {
            return $results;
        }

        if (!$paginated) {
            $this->cachedList[$cacheKey] = $this->results;
        } else {
            ($list = PaginatedList::create($this->results, Controller::curr()->getRequest()))
                ->setPageLength($perPage)
                ->setPaginationGetVar($list->getPaginationGetVar() . '-' . $this->index->getUniqueKey());

            $this->cachedList[$cacheKey] = $list;
        }

        return $this->cachedList[$cacheKey];
    }
}
