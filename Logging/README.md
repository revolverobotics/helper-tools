# Middleware-based log handling
#### Middleware classes to provide nicely-formatted log output.
Returns request header data, request input data, and the resulting server response (json).

#### Sample Output
```Shell
[2016-03-18 19:36:36] testing.DEBUG: Returned 200 | PATCH /dashboard/user HTTP/1.1
Accept:          text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8
Accept-Charset:  ISO-8859-1,utf-8;q=0.7,*;q=0.7
Accept-Language: en-us,en;q=0.5
Host:            api.kubi.dev
User-Agent:      Symfony/2.X


request->all():
Array
(
    [email] => test-user@email.com
    [password] => testing
    [display_name] => Different User Name
    [company] => Different Company Name
    [user_token] => S5Gr9sjZjOqQvtaJhQJNwppWmNEmVom404XN4un0
    [re-auth] => yes
)

Response:
{
    "statusCode": 200,
    "user": {
        "id": "8b020203-4ce2-4485-9c7b-793c0926c8e9",
        "username": "",
        "email": "test-user@email.com",
        "title": null,
        "first_name": null,
        "middle_name": null,
        "last_name": null,
        "display_name": "Different User Name",
        "company": "Different Company Name",
        "verified": 1,
        "api_user": 0,
        "user_scope": "user",
        "service": null,
        "social_id": null,
        "social_avatar": null,
        "avatar": null
    },
    "message": "User account details updated.",
    "api-service-users": "debug",
    "url": "http:\/\/api.kubi.dev\/dashboard\/user",
    "SQL Queries": 0,
    "response_time": 1.6340479850769,
    "api-frontend": "debug"
}
```

#### Installation
The namespace should be as: `App\Submodules\ToolsLaravelLog`

Within `app/Http/Kernel.php`:
```php
<?php

namespace App\Http;

use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * @var array
     */
    protected $middleware = [
        \Illuminate\Foundation\Http\Middleware\CheckForMaintenanceMode::class,
        \App\Http\Middleware\NoSession::class,

	// Add LogInitialize class before most of your middleware:
        \App\Submodules\ToolsLaravelLog\LogInitialize::class,

        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \App\Http\Middleware\GetClientToken::class,

	// Add LogReport after the rest of your middleware:
        \App\Submodules\ToolsLaravelLog\LogReport::class,
    ];
```
