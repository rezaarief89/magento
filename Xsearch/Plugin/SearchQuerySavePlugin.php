<?php 
namespace Wow\Xsearch\Plugin;

use Magento\Search\Model\ResourceModel\Query as QueryModel;

class SearchQuerySavePlugin
{
    public function beforeSaveIncrementalPopularity(QueryModel $subject, $incrementalPopularity)
    {
        $queryText = $incrementalPopularity->getQueryText();
        $modifiedQueryText = str_replace('"','',$queryText);
		$incrementalPopularity->setQueryText($modifiedQueryText);

        return [$incrementalPopularity];
    }
}
