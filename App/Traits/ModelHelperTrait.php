<?php

namespace App\Submodules\ToolsLaravelMicroservice\App\Traits;

trait ModelHelperTrait
{
    public function magicSearch(array $input)
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

        $magicArray = [];

        // while (list ($index, $key) = each($searchKeys)) {
        //     $magicArray[$key] = $input[$key];
        // }
// \Log::debug($this->where($magicArray)->get());
        // return $this->where($magicArray)->paginate(5);

        $magicMethod = '';

        $model = $this->query();

        foreach ($searchKeys as $key) {
            if (is_string($input[$key])) {
                $model->where($key, $input[$key]);
                // $magicMethod .= "->where('{$key}', '{$input[$key]}')";
                // call_user_func_array([$this, 'where'], [$key, $input[$key]]);
            } elseif (is_array($input[$key])) {
                $model->whereIn($key, $input[$key]);
                // $lookup = str_replace("\n", '', var_export($input[$key], true));
                // $lookup = '['.implode(', ', $input[$key]).']';
                // $magicMethod .= "->whereIn('{$key}', $lookup)";
                // call_user_func_array([$this, 'whereIn'], [$key, $input[$key]]);
            }
        }

        // eval('$this'.$magicMethod.'->paginate(5);');

        return $model->paginate(5);
    }
}
