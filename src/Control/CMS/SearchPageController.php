<?php

namespace BimTheBam\Meilisearch\Control\CMS;

use BimTheBam\Meilisearch\Form\SearchForm;
use BimTheBam\Meilisearch\Model\CMS\SearchPage;
use SilverStripe\Forms\Form;

/**
 * Class SearchPageController
 * @package BimTheBam\Meilisearch\Control\CMS
 * @mixin SearchPage
 */
class SearchPageController extends \PageController
{
    /**
     * @var array|string[]
     */
    private static array $allowed_actions = [
        'Form',
    ];

    /**
     * @return Form
     */
    public function Form(): Form
    {
        return SearchForm::create(
            $this,
            'Form',
        );
    }

    /**
     * @param array $data
     * @param SearchForm $form
     * @return \SilverStripe\View\ViewableData_Customised
     */
    public function doSearch(array $data, SearchForm $form)
    {
        $form->loadDataFrom($data);

        return $this->customise([
            'Form' => $form,
            'IsSearch' => true,
        ]);
    }
}
