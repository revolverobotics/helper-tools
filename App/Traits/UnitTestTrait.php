<?php

namespace App\Submodules\ToolsLaravelMicroservice\App\Traits;

trait UnitTestTrait
{
    private function verifyTestKey()
    {
        if (!$this->request->has('test_key'))
            throw new \BadRequestHttpException('[test_key] must be provided.');

        if (is_null(env('TEST_KEY', null)) || $this->request->input('test_key', 'asdf') != env('TEST_KEY', 'nomatch'))
            throw new \FatalErrorException('Provided [test_key] is invalid.');
    }

    private function verifyTestVariables()
    {
        $testArray = [
            env('TEST_KEY', null),
            env('TEST_DEVICE_A', null),
            env('TEST_DEVICE_B', null)
        ];

        if (in_array(null, $testArray))
            throw new \FatalErrorException('You must set all the appropriate environment variables (prefixed with TEST_) in order to run unit and post-deployment tests.  Please see .env.example.');
    }

    private function performCleanupOperations()
    {
        \DB::statement('
            DELETE from devices WHERE hardware_id = \''.env('TEST_DEVICE_A').'\';
        ');

        \DB::statement('
            DELETE from devices WHERE hardware_id = \''.env('TEST_DEVICE_B').'\';
        ');
    }
}
