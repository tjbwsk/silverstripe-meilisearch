<?php

namespace BimTheBam\Meilisearch\Model\Extension;

use BimTheBam\Meilisearch\Index;
use BimTheBam\Meilisearch\Model\Document;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use Throwable;

/**
 * Class SiteTree
 * @package BimTheBam\Meilisearch\Model\Extension
 * @mixin \SilverStripe\CMS\Model\SiteTree
 * @property \SilverStripe\CMS\Model\SiteTree $owner
 */
class SiteTree extends SearchableVersioned
{
    /**
     * @return void
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Throwable
     */
    public function onAfterPublish(): void
    {
        if ($this->owner->ShowInSearch) {
            parent::onAfterPublish();
        } else {
            Index::for_class($this->owner)->remove(
                Document::create($this->owner)
            );
        }
    }

    /**
     * @return void
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Throwable
     */
    public function onAfterWrite(): void
    {
        parent::onAfterWrite();

        if (!$this->owner->ShowInSearch) {
            Index::for_class($this->owner)->remove(
                Document::create($this->owner)
            );
        }
    }
}
