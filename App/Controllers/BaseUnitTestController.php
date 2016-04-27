<?php

namespace App\Submodules\ToolsLaravelMicroservice\App\Controllers;

use Illuminate\Http\Request;

use App\Submodules\ToolsLaravelMicroservice\App\Traits\BaseUnitTestTrait;

use Cache;

class BaseUnitTestController extends CustomController
{
    use BaseUnitTestTrait;

    public function postPreTest()
    {
        $this->verifyTestKey();

        $this->runCustomChecks();

        $this->makeResponse([], 'Pre-test finished.');

        return $this->success();
    }

    public function getCache()
    {
        $this->makeResponse(['data' => Cache::get('PHPUnitTest')]);

        return $this->success();
    }

    public function putCache()
    {
        Cache::put('PHPUnitTest', 'testData', 60);

        return $this->success();
    }

    public function postCleanup()
    {
        $this->verifyTestKey();

        $this->performCleanupOperations();

        $this->makeResponse([], 'Cleanup finished.');

        return $this->success();
    }
}
