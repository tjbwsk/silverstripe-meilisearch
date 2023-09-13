<?php

namespace BimTheBam\Meilisearch\Form;

use BimTheBam\Meilisearch\Index;
use Meilisearch\Contracts\SearchQuery;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionException;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CheckboxSetField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\RequiredFields;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\Validator;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use Throwable;

/**
 * Class SearchForm
 * @package BimTheBam\Meilisearch\Form
 */
class SearchForm extends Form
{
    /**
     * @param RequestHandler|null $controller
     * @param string $name
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Throwable
     */
    public function __construct(RequestHandler $controller = null, string $name = self::DEFAULT_NAME)
    {
        parent::__construct(
            $controller,
            $name,
            $this->defaultFields(),
            $this->defaultActions(),
            $this->defaultValidator()
        );

        $this->setFormMethod('GET');
        $this->disableSecurityToken();
    }

    /**
     * @return FieldList
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function defaultFields(): FieldList
    {
        $fields = FieldList::create(
            TextField::create(
                'q',
                _t(__CLASS__ . '.FIELD_LABEL_Q', 'Search term')
            )
                ->setAttribute('minlength', 3)
                ->setDescription(
                    _t(__CLASS__ . '.FIELD_DESCRIPTION_Q', 'Minimum 3 characters.')
                ),
        );

        if (count($availableIndexes = $this->getIndexes(true)) > 1) {
            $indexes = [];

            foreach ($availableIndexes as $index) {
                $indexes[$index->getUniqueKey()] = $index->getDataObjectSingleton()->i18n_plural_name();
            }

            $fields->push(
                CheckboxSetField::create(
                    'SearchIn',
                    _t(__CLASS__ . '.FIELD_LABEL_SEARCH_IN', 'Limit search to'),
                    $indexes,
                ),
            );
        }

        $this->extend('updateDefaultFields', $fields);

        return $fields;
    }

    /**
     * @return FieldList
     */
    protected function defaultActions(): FieldList
    {
        $actions = FieldList::create(
            FormAction::create(
                'doSearch',
                _t(__CLASS__ . '.ACTION_DO_SEARCH', 'Search')
            ),
        );

        $this->extend('updateDefaultActions', $actions);

        return $actions;
    }

    /**
     * @return Validator
     */
    protected function defaultValidator(): Validator
    {
        $validator = RequiredFields::create([
            'q',
        ]);

        $this->extend('updateDefaultValidator', $validator);

        return $validator;
    }

    /**
     * @var array|null
     */
    protected ?array $indexes = null;

    /**
     * @return array|Index[]
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Throwable
     */
    protected function getIndexes(bool $sortedAndSiteTreePrioritized = false): array
    {
        if (($cached = ($this->indexes[(int)$sortedAndSiteTreePrioritized] ?? null)) !== null) {
            return $cached;
        }

        $indexes = [];

        if (count($indexClasses = ClassInfo::subclassesFor(Index::class, false)) > 0) {
            $siteTreeIndex = null;

            foreach ($indexClasses as $indexClass) {
                /** @var Index $index */
                $index = Injector::inst()->get($indexClass);

                if (($sng = $index->getDataObjectSingleton()) !== null) {
                    if ($sortedAndSiteTreePrioritized && $index->handles(SiteTree::class)) {
                        $siteTreeIndex = $index;
                        continue;
                    }

                    $indexes[$sng->i18n_plural_name()] = $index;
                }
            }

            if ($sortedAndSiteTreePrioritized) {
                ksort($indexes);

                foreach ($indexes as $key => $index) {
                    unset($indexes[$key]);
                    $indexes[] = $index;
                }

                if ($siteTreeIndex !== null) {
                    $indexes = array_merge(
                        [
                            $siteTreeIndex,
                        ],
                        $indexes,
                    );
                }
            }
        }

        $this->indexes[(int)$sortedAndSiteTreePrioritized] = $indexes;

        return $indexes;
    }

    /**
     * @return string|null
     */
    public function getQ(): ?string
    {
        $data = $this->getData();

        if (!empty($q = trim($data['q'] ?? ''))) {
            return $q;
        }

        return null;
    }

    /**
     * @return ArrayList
     * @throws NotFoundExceptionInterface
     * @throws ReflectionException
     * @throws Throwable
     */
    public function getResultsByIndex(): ArrayList
    {
        $results = ArrayList::create();

        $searchIn = ($data = $this->getData())['SearchIn'] ?? null;

        if (empty($q = trim($data['q'] ?? ''))) {
            return $results;
        }

        $queries = [];

        foreach ($this->getIndexes(true) as $index) {
            if ($searchIn !== null && !in_array($index->getUniqueKey(), $searchIn)) {
                continue;
            }

            $results->push(ArrayData::create([
                'IndexName' => $index->getDataObjectSingleton()->i18n_plural_name(),
                'IndexUniqueKey' => $index->getUniqueKey(),
                'Results' => $index->search($q),
            ]));
        }

        return $results;
    }
}
