<?php

namespace App\Submodules\ToolsLaravelMicroservice\App\Traits;

trait BaseUnitTestTrait
{
    protected $testVariables;

    protected function verifyTestKey()
    {
        if (!$this->request->has('test_key')) {
            throw new \BadRequestHttpException('[test_key] must be provided.');
        }

        $inputTestKey = $this->request->input('test_key', str_random(32));
        $envTestKey = env('TEST_KEY', str_random(32));

        if (is_null(env('TEST_KEY', null))
            || $inputTestKey != $envTestKey) {
                throw new \FatalErrorException(
                    'Provided [test_key] is invalid.'
                );
            }
    }

    protected function runCustomChecks()
    {
        // Add any microservice-specific pre-test checks in
        // UnitTestController extends BaseUnitTestController
    }

    protected function performCleanupOperations()
    {
        throw new \FatalErrorException(
            "Each microservice will have a different set of cleanup ops.\n".
            "Extend the BaseUnitTestController and create a service-specific\n".
            "UnitTestTrait if necessary."
        );
    }
}