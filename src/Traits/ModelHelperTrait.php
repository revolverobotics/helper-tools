<?php

namespace Revolve\Microservice\Traits;

trait ModelHelperTrait
{
    /**
     * Results from the magicSearch method.
     *
     * @var Illuminate\Pagination\LengthAwarePaginator
     */
    protected $magicResult;

    /**
     * Searches for a model based on any number of input received.
     * Searchable columns/attributes are specified in the model's
     * $queryable property.
     *
     * @param  array $input
     * @param  int $pagination
     * @return Illuminate\Pagination\LengthAwarePaginator
     *
     * @throws Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     * @throws Symfony\Component\HttpKernel\Exception\NotAcceptableHttpException
     */
    public function magicSearch(array $input, $pagination = 5)
    {
        if (is_null($this->queryable)) {
            throw new \BadRequestHttpException(
                'Please specify columns available '.
                'for Magic Search in Model::$queryable.'
            );
        }

        // Find any input that matches $queryable
        $searchKeys = array_intersect(
            array_keys($input),
            $this->queryable
        );

        if (count($searchKeys) < 1) {
            throw new \NotAcceptableHttpException(
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

        if ($pagination > 0) {
            $result = $model->paginate($pagination);
        }

        $this->magicResult = $result;

        return $result;
    }
}
