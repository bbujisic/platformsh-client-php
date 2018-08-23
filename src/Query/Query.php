<?php

namespace Platformsh\Client\Query;

abstract class Query implements QueryInterface
{
    protected $params = [];

    /**
     * Get the URL query parameters.
     *
     * @return array
     */
    public function getParams(): array
    {
        $params = $this->cleanUpFilter($this->params);

        //var_dump($params); die();

        return $params;
    }

    protected function setFilter($name, $value) {
        $this->params['filter'][$name] = $value;
    }

    protected function validateList($name, $value, $allowedValues) {
        if (!in_array($value, $allowedValues)) {
            // @todo QueryValidationException
            throw new \Exception(sprintf('Value %s for parameter %s is not allowed', $value, $name));
        }
    }


    // Me no likey
    private function cleanUpFilter($query)
    {
        if (!isset($query['filter'])) {
            return $query;
        }


        $filters = array_filter(
            $query['filter'],
            function ($value) {
                return $value !== null;
            }
        );

        $filters = array_map(
            function ($value) {
                return is_array($value) ? ['value' => $value, 'operator' => 'IN'] : $value;
            },
            $filters
        );


        if (count($filters)) {
            $query['filter'] = $filters;
        } else {
            unset($query['filter']);
        }

        return $query;
    }

}
