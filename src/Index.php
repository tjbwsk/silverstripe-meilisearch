<?php

namespace BimTheBam\Meilisearch;

use BimTheBam\Meilisearch\Model\Document;
use BimTheBam\Meilisearch\SearchResults\Result;
use Meilisearch\Client;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use ReflectionException;
use SilverStripe\Control\Director;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Versioned\Versioned;
use Throwable;
use TractorCow\Fluent\Extension\FluentExtension;
use TractorCow\Fluent\Extension\FluentVersionedExtension;
use TractorCow\Fluent\Model\Locale;
use TractorCow\Fluent\State\FluentState;

/**
 * Class Index
 * @package BimTheBam\Meilisearch
 */
abstract class Index
{
    use Injectable;
    use Configurable;

    /**
     * @var string|null
     */
    private static ?string $data_object_base_class = null;

    /**
     * @var array
     */
    private static array $excluded_classes = [];

    /**
     * @var bool
     */
    private static bool $check_can_view = false;

    /**
     * @var Client|null
     */
    protected static ?Client $client = null;

    /**
     * @var DataObject|null
     */
    protected ?DataObject $dataObjectSingleton = null;

    /**
     * @param string|null $indexName
     */
    public function __construct(readonly ?string $indexName = null)
    {
    }

    /**
     * @param string $class
     * @return Index|null
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Throwable
     */
    public static function for_class(string $class): ?self
    {
        foreach (ClassInfo::subclassesFor(Index::class, false) as $indexClass) {
            /** @var Index $index */
            $index = Injector::inst()->create($indexClass);

            if ($index->handles($class)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * @param string $class
     * @return bool
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function handles(string $class): bool
    {
        $classAncestry = ClassInfo::ancestry($class);

        return in_array($this->getDataObjectSingleton()::class, $classAncestry);
    }

    /**
     * @return Client
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public static function get_client(): Client
    {
        if (static::$client !== null) {
            return static::$client;
        }

        if (empty($hostAndPort = trim(Environment::getEnv('MEILISEARCH_HOST_AND_PORT') ?? ''))) {
            static::throw_or_log(new \RuntimeException('MEILISEARCH_HOST_AND_PORT not defined.'));
        }

        static::$client = new Client($hostAndPort, Environment::getEnv('MEILISEARCH_MASTER_KEY') ?: null);

        return static::$client;
    }

    /**
     * @throws Throwable
     * @throws NotFoundExceptionInterface
     */
    protected static function throw_or_log(Throwable $e): void
    {
        if (Director::isDev()) {
            throw $e;
        } else {
            /** @var LoggerInterface $logger */
            $logger = Injector::inst()->get(LoggerInterface::class);
            $logger->error($e);
        }
    }

    /**
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function getDataObjectSingleton(): ?DataObject
    {
        if ($this->dataObjectSingleton) {
            return $this->dataObjectSingleton;
        }

        if (empty($class = (static::config()->get('data_object_base_class')))) {
            static::throw_or_log(new \RuntimeException(static::class . '::data_object_base_class not defined.'));
        }

        if (!ClassInfo::exists($class)) {
            static::throw_or_log(
                new \RuntimeException(static::class . '::data_object_base_class (' . $class . ') does not exist.')
            );
        }

        if (!in_array(DataObject::class, ClassInfo::ancestry($class))) {
            static::throw_or_log(
                new \RuntimeException(
                    static::class . '::data_object_base_class (' . $class . ') ' .
                    'is not a valid subclass of ' . DataObject::class . '.'
                )
            );
        }

        try {
            $this->dataObjectSingleton = Injector::inst()->get($class);

            return $this->dataObjectSingleton;
        } catch (Throwable $e) {
            static::throw_or_log($e);
        }

        return null;
    }

    /**
     * @var string|null
     */
    protected ?string $uniqueKey = null;

    /**
     * @return string
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function getUniqueKey(): string
    {
        if ($this->uniqueKey !== null) {
            return $this->uniqueKey;
        }

        $this->uniqueKey = Uuid::uuid5(Uuid::NAMESPACE_OID, $this->getDataObjectSingleton()::class)->toString();

        return $this->uniqueKey;
    }

    /**
     * @return string|null
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function getIndexName(): ?string
    {
        if (($sng = $this->getDataObjectSingleton()) === null) {
            return null;
        }

        $indexName = strtolower(str_replace('\\', '_', $sng::class));

        if (ClassInfo::exists(FluentExtension::class) && $sng->hasExtension(FluentExtension::class)) {
            $indexName .= '_' . strtolower(Locale::getCurrentLocale()->Locale);
        }

        if (
            ($prefix = Environment::getEnv('MEILISEARCH_INDEXES_PREFIX')) !== false
            && !empty($prefix = trim($prefix ?? ''))
        ) {
            $prefix = preg_replace('/[^a-z|A-Z|0-9|_|-]/', '_', $prefix);

            $indexName = $prefix . '_' . $indexName;
        }

        return $indexName;
    }

    /**
     * @return DataList|null
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    protected function getDataList(): ?DataList
    {
        if (($sng = $this->getDataObjectSingleton()) === null) {
            return null;
        }

        return $sng::get();
    }

    /**
     * @return void
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function rebuild(): void
    {
        if (($sng = $this->getDataObjectSingleton()) === null) {
            return;
        }

        if (empty($indexName = $this->indexName)) {
            if (
                ClassInfo::exists(FluentExtension::class)
                && $sng->hasExtension(FluentExtension::class)
            ) {
                Locale::get()->each(function (Locale $locale) {
                    FluentState::singleton()->withState(function (FluentState $state) use ($locale) {
                        $state->setLocale($locale->Locale);

                        static::create($this->getIndexName())->rebuild();
                    });
                });

                return;
            }

            $indexName = $this->getIndexName();
        }

        static::get_client()->deleteIndex($indexName);

        $settings = [
            'searchableAttributes' => Document::get_searchable_fields($sng::class),
            'filterableAttributes' => Document::get_filterable_fields($sng::class),
            'sortableAttributes' => Document::get_sortable_fields($sng::class),
        ];

        static::get_client()->index($indexName)->updateSettings($settings);

        $oldStage = null;

        if (
            ClassInfo::exists(Versioned::class)
            && $sng->hasExtension(Versioned::class)
        ) {
            $oldStage = Versioned::get_stage();
            Versioned::set_stage(Versioned::LIVE);
        }

        $chunkSize = 500;
        $currentChunk = 0;

        // Keep looping until we run out of chunks
        while ($chunk = $this->getDataList()->limit($chunkSize, $chunkSize * $currentChunk)->getIterator()) {
            // Loop over all the item in our chunk
            foreach ($chunk as $record) {
                /** @var DataObject|FluentVersionedExtension|FluentExtension $record */
                if (
                    ($record->hasExtension(FluentVersionedExtension::class) && !$record->isPublishedInLocale())
                    || ($record->hasExtension(FluentExtension::class) && !$record->existsInLocale())
                ) {
                    continue;
                }

                $this->add(Document::create($record), false);
            }

            $this->commit();

            if ($chunk->count() < $chunkSize) {
                // If our last chunk had less item than our chunkSize, we've reach the end.
                break;
            }

            $currentChunk++;
        }

        if (
            ClassInfo::exists(Versioned::class)
            && $sng->hasExtension(Versioned::class)
            && $oldStage !== null
        ) {
            Versioned::set_stage($oldStage);
        }
    }

    /**
     * @var array|Document[]
     */
    protected array $uncommitted = [];

    /**
     * @param Document $document
     * @param bool $commit
     * @return $this
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function add(Document $document, bool $commit = true): self
    {
        $excludedClasses = static::config()->uninherited('excluded_classes') ?? [];

        if (!$excludedClasses || !in_array($document->record::class, $excludedClasses)) {
            $this->uncommitted[] = $document->toArray();

            if ($commit) {
                $this->commit();
            }
        }

        return $this;
    }

    /**
     * @return $this
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    protected function commit(): self
    {
        if (count($this->uncommitted) === 0) {
            return $this;
        }

        static::get_client()->index($this->getIndexName())->addDocuments($this->uncommitted, 'ID');

        $this->uncommitted = [];

        return $this;
    }

    /**
     * @param Document|array $documents
     * @return $this
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function remove(Document|array $documents): self
    {
        if (!is_array($documents)) {
            $documents = [$documents];
        }

        $documentIDs = [];

        foreach ($documents as $document) {
            if (!($document instanceof Document)) {
                continue;
            }

            $documentIDs[] = $document->id;
        }

        if (!empty($documentIDs)) {
            static::get_client()->index($this->getIndexName())->deleteDocuments($documentIDs);
        }

        return $this;
    }

    /**
     * @param string $q
     * @param array|null $filter
     * @param int|null $limit
     * @param array|null $sort
     * @return SearchResults
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function search(string $q, ?array $filter = null, ?int $limit = null, ?array $sort = null): SearchResults
    {
        if ($limit === null || $limit <= 0) {
            $limit = 100;
        }

        $options = [
            'limit' => $limit,
            'showRankingScore' => true,
        ];

        if ($filter !== null) {
            $options['filter'] = $filter;
        }

        if ($sort !== null) {
            $options['sort'] = $sort;
        }

        $meiliResults = static::get_client()->index($this->getIndexName())->search($q, $options);

        $estimatedTotalHits = $meiliResults->getEstimatedTotalHits();

        $results = SearchResults::create($this, $q);

        if (count($hits = $meiliResults->getHits()) > 0) {
            $ids = array_map(fn (array $hit) => $hit['ID'], $hits);

            if (static::config()->uninherited('check_can_view') ?? false) {
                $allowedIDs = $this->getDataList()
                    ->filter(['ID' => $ids])
                    ->filterByCallback(fn (DataObject $record) => $record->canView())
                    ->column('ID');

                $estimatedTotalHits -= (count($ids) - count($allowedIDs));

                $ids = array_intersect($ids, $allowedIDs);
            }

            foreach ($hits as $hit) {
                if (!in_array($hit['ID'], $ids)) {
                    continue;
                }

                $results->add(Result::create($hit['ClassName'], $hit['ID'], $hit['_rankingScore']));
            }
        }

        $results->Limit = $limit;
        $results->EstimatedTotal = $estimatedTotalHits;

        return $results;
    }
}
