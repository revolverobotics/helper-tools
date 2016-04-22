<?php

namespace App\Submodules\ToolsLaravelMicroservice\App\Traits;

trait ModelHelperTrait
{
    protected $magicResult;

    public function magicSearch(array $input, $pagination = 5)
    {
        if (is_null($this->queryable)) {
            throw new \FatalErrorException(
                'Please specify columns available '.
                'for Magic Search in $queryable.'
            );
        }

        // Find any input that matches $queryable
        $searchKeys = array_intersect(
            array_keys($input),
            $this->queryable
        );

        if (count($searchKeys) < 1) {
            throw new \BadRequestHttpException(
                'No input keys matched any searchable columns/fields.'
            );
        }

        $model = $this->query();

        foreach ($searchKeys as $key) {
            if (is_string($input[$key])) {
                $model->where($key, $input[$key]);
            } elseif (is_array($input[$key])) {
                $model->whereIn($key, $input[$key]);
            }
        }

        $result = $model->paginate($pagination);

        $this->magicResult = $result;

        return $result;
    }

    public function dataKeyToModelName($override = null)
    {
        if (!isset($this->magicResult)) {
            return false;
        }

        $result = $this->magicResult->toArray();

        if (!isset($result['data'])) {
            return false;
        }

        if (is_null($override)) {
            $name = $this->table;
        } else {
            $name = $override;
        }

        $result[$name] = $result['data'];
        unset($result['data']);

        return $this->trimPagination($result);
    }

    protected function trimPagination($result)
    {
        if ($result['last_page'] == 1) {
            unset($result['per_page']);
            unset($result['current_page']);
            unset($result['last_page']);
            unset($result['next_page_url']);
            unset($result['prev_page_url']);
            unset($result['from']);
            unset($result['to']);
        }

        return $result;
    }

    public function getResult()
    {
        if (!isset($this->magicResult)) {
            return false;
        }

        return $this->magicResult;
    }
}
