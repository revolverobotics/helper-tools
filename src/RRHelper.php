<?php

namespace Revolve\Microservice;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

use Log;

class RRHelper
{
    public static function debugLog($resource, $info)
    {
        if (env('APP_DEBUG', false)) {
            Log::info("[".$resource."] ".$info);
        }
    }

    /*
        Binds a singleton to the app to create your own
        log trail, or a user-defined stack trace, if you will.
    */
    public static function logTrail($input)
    {
        try {
            $currentString = \App::make('logTrail');
        } catch (\Exception $e) {
            \App::singleton('logTrail', 'stdClass');

            $currentString = \App::make('logTrail');
            $currentString->log = [];
        }

        if (is_array($input) || is_string($input)) {
            array_push($currentString->log, $input);
        } else {
            Log::info($input);
        }
    }

    public static function arrayifyData(&$object, string $plural)
    {
        // echo gettype($object);

        // if (gettype($object) == 'object') {
        //     echo get_class($object);
        // }

        if ($object instanceof LengthAwarePaginator) {
            // Returned from magicSearch
            $staticObject = $object->toArray();

            $staticObject[$plural] = $staticObject['data'];

            unset($staticObject['data']);
            //
        } else {
            $staticObject = [];

            if ($object instanceof Model) {
                // Returned from PUT/PATCH
                $staticObject[$plural] = [$object->toArray()];
            } elseif ($object instanceof Collection) {
                // Returned from ->get()
                $staticObject[$plural] = $object->toArray();
                // Reset the key indices in case we filtered some elements out.
                $staticObject[$plural] = array_values($staticObject[$plural]);
            }

            $staticObject['total'] = count($staticObject[$plural]);
        }

        $object = $staticObject;

        return $object;
    }
}
