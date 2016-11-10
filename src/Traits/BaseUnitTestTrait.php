<?php

namespace Revolve\Microservice\Traits;

trait BaseUnitTestTrait
{
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
