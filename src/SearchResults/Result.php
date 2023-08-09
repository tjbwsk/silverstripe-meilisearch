<?php

namespace BimTheBam\Meilisearch\SearchResults;

use SilverStripe\ORM\DataObject;
use SilverStripe\View\ViewableData;

/**
 * Class Result
 * @package BimTheBam\Meilisearch\SearchResults
 * @property DataObject|null $Record
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
     * @param string $RecordClassName
     * @param int $RecordID
     */
    public function __construct(
        public readonly string $RecordClassName,
        public readonly int $RecordID,
        public readonly float $Score
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

        return $this->record = DataObject::get_by_id($this->RecordClassName, $this->RecordID);
    }
}
