<?php

namespace Platformsh\Client\Fetcher;

/**
 * Pagination-aware data fetcher.
 */
class CollectionFetcher extends Fetcher
{
    private $currentPage = 1;
    private $hasNextPage = true;
    private $countRemote;


    public function fetch()
    {
        if (!$this->hasNextPage) {
            return false;
        }

        $data = $this->fetchPage($this->currentPage++);

        return $this->parseData($data);
    }

    public function setCurrentPage($pageId)
    {
        $this->currentPage = $pageId;
    }

    public function getCurrentPage()
    {
        return $this->currentPage;
    }

    private function fetchPage($page)
    {
        $options = $this->options;
        $options['query']['page'] = $page;

        return $this->getData($options);
    }


    // @todo: Consider moving somewhere else.
    private function parseData($data)
    {
        $className = $this->apiResourceClassName;

        $this->countRemote = $data['count'];
        // If the next HAL link exists, the collection has its next page.
        $this->hasNextPage = (isset($data['_links']['next']) ? true : false);
        $out = [];
        foreach ($data[$className::COLLECTION_NAME] as $item) {
            $out[] = new $className($item, $this->baseUrl, $this->client);
        }

        return $out;
    }

}