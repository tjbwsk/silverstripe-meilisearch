<?php

namespace BimTheBam\Meilisearch\Dev\Task;

use BimTheBam\Meilisearch\Index;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Versioned\Versioned;
use Throwable;

/**
 * Class RebuildAllIndexesTask
 * @package BimTheBam\Meilisearch\Dev\Task
 */
class RebuildAllIndexesTask extends BuildTask
{
    /**
     * @var string
     */
    private static string $segment = 'meilisearch-rebuild-all-indexes';

    /**
     * @var string
     */
    protected $title = 'Rebuild all meilisearch indexes';

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @param $request
     * @return void
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Throwable
     */
    public function run($request): void
    {
        $stage = $request->getVar('stage');

        Versioned::withVersionedMode(function () use ($stage) {
            if ($stage) {
                Versioned::set_stage($stage);
            }

            foreach (ClassInfo::subclassesFor(Index::class, false) as $indexClass) {
                /** @var Index $index */
                $index = Injector::inst()->create($indexClass);

                $index->rebuild();
            }
        });
    }
}
