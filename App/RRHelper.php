<?php

namespace App\Submodules\ToolsLaravelMicroservice\App;

class RRHelper {

    public static function debugLog($resource,$info)
    {
        if (getenv('APP_DEBUG') == true)
            \Log::info("[".$resource."] ".$info);
    }

    /*
        Binds a singleton to the app to create your own
        log trail, or a user-defined stack trace, if you will.
    */
    public static function logTrail($input)
    {
        try
        {
            $currentString = \App::make('logTrail');
        }
        catch (\Exception $e)
        {
            \App::singleton('logTrail', 'stdClass');

            $currentString = \App::make('logTrail');
            $currentString->log = [];
        }

        if (is_array($input) || is_string($input))
            array_push($currentString->log, $input);

        else
            \Log::info($input);
    }

    public static function prettyJson($code=200, $input=[], $headers=[])
    {
        $responseData = $input;
        $statusArray = [];

        /*
            Check if we're passing data as the first parameter
        */
        if (is_array($code)):

            // Assume 200 OK if passing data
            $statusArray = ['statusCode' => 200];
            $responseData = $statusArray + $code;
            $code = 200;	// data has been assigned to responseData
                            // let's reassign $code to the status code

        elseif (is_integer($code)):

            // otherwise, read the status code, and include data
            $statusArray = ['statusCode' => $code];

            if (is_array($input)):

                $responseData = $statusArray + $input;

            else:

                $responseData = [];
                $responseData['data'] = $input;
                $responseData['statusCode'] = $code;

            endif;

        endif;

        // Finally, if we're in debug mode, let us know about it.
        if (getenv('APP_DEBUG') == true):

            $responseData[self::appName()] = 'debug';
            $responseData['url'] = \Request::fullUrl();
            try
            {
                $logTrail = app()->make('logTrail');
                $responseData['log'] = $logTrail->log;
            }
            catch (\Exception $e)
            {
                // no trail
            }
            $responseData['SQL Queries'] = count(\DB::getQueryLog());
            $responseData['response_time'] = microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'];

        endif;

        $headers['Content-Type'] = 'application/json';

        return response()->json($responseData, $code, $headers, JSON_PRETTY_PRINT);
    }

    // Helper to make Guzzle requests to other microservices
    public static function sendRequest($method, $microservice, $url, $data, $audit=1)
    {
        switch(\App::environment()):

            case "local":
                $extension = '.dev';
                break;

            case "development":
                $extension = '.dev';
                break;

            case "production":
                $extension = '.com';
                break;

            default:
                $extension = getenv('VPC_EXTENSION') ?: '.com';
                break;

        endswitch;

        $microserviceArray = [
            // new
            'devices'		=> 'devices.kubi-vpc'.$extension,
            'auditing'		=> 'auditing.kubi-vpc'.$extension,
            'users'		=> 'users.kubi-vpc'.$extension,
            'locations'		=> 'locations.kubi-vpc'.$extension,

            // legacy
            'kubi-service' => 'service.kubi-vpc'.$extension,
            'kubi-auditing' => 'auditing.kubi-vpc'.$extension,
            'kubi-users' => 'users.kubi-vpc'.$extension,
            'kubi-video' => 'video.kubi-vpc'.$extension,
        ];

        $client = new \GuzzleHttp\Client();

        // Sending application/x-www-for-urlencoded POST, PUT, & PATCH (non-GET) requests requires
        // `form_params` request options, instead of `query`
        if ($method != 'GET'):

            $data['form_params'] = $data['query'];
            unset($data['query']);

        endif;

        $dataArray = $data;
        $dataArray['http_errors'] = false; // don't fail on error (400, 500, etc.)

        // Forward headers (modified) to backend
        $headers = \Request::header();
        $headers['host'][0] = $microserviceArray[$microservice];
        $headers['connection'][0] = "close";
        // We're always going to be sending data to the backend
        // in a certain way, so let's let guzzle detect the Content-Type:
        unset($headers['content-type']);
        unset($headers['content-length']);
        $dataArray['headers'] = $headers;

        $rawResponse = $client->request($method, $microserviceArray[$microservice].$url, $dataArray);

        $parsedResponse = [];
        $parsedResponse['json'] = json_decode($rawResponse->getBody(), true);
        $parsedResponse['code'] = $rawResponse->getStatusCode();

        return $parsedResponse;
    }

    public static function getAuthorizationHeader($request)
    {
        $chunks = explode(" ", $request->header('Authorization'));

        if (isset($chunks[1]))
            return $chunks[1];

        return null;
    }

    public static function verifyRefererDomain($request)
    {
        // verify if a request is coming from our domain or not
        $domain = $request->header()['referer'][0];
        $foundLocal = strpos($domain, 'api.kubi-vpc.dev');
        $foundServer = strpos($domain, 'api.kubi.me');

        if ($foundLocal !== false || $foundServer !== false)
            return true;

        return false;
    }

    public static function forkCurl($method, $inputData, $url)
    {
        // sends an asynchronous request using a forked cURL process.
        // does not wait for a response.

        $data = http_build_query($inputData);
        $hdr  = "-H 'Content-Type: application/x-www-form-urlencoded' ";
        $cmd  = "curl --silent -X " . $method . " " . $hdr . " -d \"" . $data . "\" " . $url;

        if (strpos(php_uname('s'),'Windows') !== false):
            pclose(popen("start /B " . $cmd, "r"));

        else:
            $cmd .= " > /dev/null 2>&1 &";
            exec($cmd, $output, $exit);

        endif;

        return true;
    }

    private static function appName()
    {
        $appName = strrchr(base_path(), '/');

        if ($appName === false)
            $appName = strrchr(base_path(), '\\');

        $appName = substr($appName, 1);

        return $appName;
    }
}
