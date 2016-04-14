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

    protected function verifyTestVariables()
    {
        try {
            $this->setTestVariables();
        } catch (\Exception $e) {
            throw new \FatalErrorException(
                "You must create a method called setTestVariables() in a \n".
                "UnitTestController that extends BaseUnitTestController. \n".
                "\$this->testVariables must be set to an array of variables \n".
                "to test."
            );
        }

        if (in_array(null, $this->testVariables)) {
            throw new \FatalErrorException(
                "You must set all the appropriate environment variables \n".
                "(prefixed with TEST_) in order to run unit and \n".
                "post-deployment tests.  Please see .env.example."
            );
        }
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
