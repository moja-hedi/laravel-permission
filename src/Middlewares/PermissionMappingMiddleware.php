<?php

namespace MojaHedi\Permission\Middlewares;

use MojaHedi\Permission\Models\Permission;
use Closure;
use MojaHedi\Permission\Exceptions\UnauthorizedException;

class PermissionMappingMiddleware
{
    public function handle($request, Closure $next, $guard = null)
    {
        $authGuard = app('auth')->guard($guard);
        $method = $request->route()->methods[0];
        $uri = $request->route()->uri;
        if ($authGuard->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $model = config('permission.models.permission');
        $model = new $model;
        $permission = $model->query()->where([['route', $uri], ['method', $method]])->first();

        if ($permission and $permission->name) {
            if ($authGuard->user()->can($permission->name)) {
                return $next($request);
            }

        }

        throw UnauthorizedException::forPermissions([$uri, $method]);
    }
}
