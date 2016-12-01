<?php

namespace Revolve\Microservice\Http\Controllers;

use Cache;
use Illuminate\Http\Request;

use Revolve\Microservice\Traits\BaseUnitTestTrait;

class BaseUnitTestController extends CustomController
{
    use BaseUnitTestTrait;

    public function preTest()
    {
        $this->auth();

        $this->runCustomChecks();

        $this->makeResponse([], 'Pre-test finished.');

        return $this->success();
    }

    public function testGetCache()
    {
        $this->auth();

        $this->makeResponse(['data' => Cache::get('PHPUnitTest')]);

        return $this->success();
    }

    public function testPutCache()
    {
        $this->auth();

        Cache::put('PHPUnitTest', 'testData', 60);

        return $this->success();
    }

    public function cleanup()
    {
        $this->auth();

        $this->performCleanupOperations();

        $this->makeResponse([], 'Cleanup finished.');

        return $this->success();
    }

    protected function auth()
    {
        // Extend with any auth logic needed to access these routes
    }
}
