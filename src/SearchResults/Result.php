<?php

namespace BimTheBam\Meilisearch\SearchResults;

use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;

/**
 * Class Result
 * @package BimTheBam\Meilisearch\SearchResults
 * @property DataObject|null $Record
 * @property float $Score
 */
class Result extends ViewableData
{
    /**
     * @var bool
     */
    protected bool $recordFetched = false;

    /**
     * @var DataObject|null
     */
    protected ?DataObject $record = null;

    /**
     * @param string $recordClassName
     * @param int $recordID
     */
    public function __construct(
        protected readonly string $recordClassName,
        protected readonly int $recordID,
        protected readonly float $score
    ) {
        parent::__construct();
    }

    /**
     * @return DataObject|null
     */
    public function getRecord(): ?DataObject
    {
        if ($this->recordFetched) {
            return $this->record;
        }

        $this->recordFetched = true;

        return $this->record = DataObject::get_by_id($this->recordClassName, $this->recordID);
    }

    /**
     * @return float
     */
    public function getScore(): float
    {
        return $this->score;
    }
}
