<?php

namespace App\Submodules\ToolsLaravelMicroservice\App\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Submodules\ToolsLaravelMicroservice\App\Traits\BaseUnitTestTrait;

use Cache;

class BaseUnitTestController extends Controller {

    use BaseUnitTestTrait;

    public function __construct(Request $request)
    {
        $this->initialize($request);
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

        $this->verifyTestVariables();

        $this->performCleanupOperations();

        $this->makeResponse([], 'Cleanup finished.');

        return $this->success();
    }
}
