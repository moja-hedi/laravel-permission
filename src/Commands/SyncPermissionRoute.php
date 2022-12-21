<?php

namespace MojaHedi\Permission\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SyncPermissionRoute extends Command
{
    protected $signature = 'permission:sync';

    protected $description = 'sync permissions route';

    protected Router $router;


    /**
     * Create a new route command instance.
     *
     * @param \Illuminate\Routing\Router $router
     * @return void
     */

    public function __construct(Router $router)
    {
        parent::__construct();

        $this->router = $router;
    }


    public function handle()
    {
        $routes = $this->getRoutes();
        $this->setRoutes($routes);
        $this->comment('Done');

    }

    /**
     * Compile the routes into a displayable format.
     *
     * @return array
     */
    protected function getRoutes(): array
    {
        $all_routes = $this->router->getRoutes();
        return collect($all_routes)->map(function ($route) {
            return $this->getRouteInformation($route);
        })->filter()->all();

    }

    protected function setRoutes($routes = null)
    {

        $model = config('permission.models.permission');
        $model = new $model;
        foreach ($routes as $route) {
            $permission = $model->query()->where('name', $route['name'])->first();
            if ($permission) {
                if ($permission->slug) {
                    unset($route['slug']);
                }
                $permission->update($route);
            } else {
                $model->query()->create($route);
            }
        }

    }

    function clean($string)
    {
        $string = str_replace(['-', '/'], '_', $string); // Replaces all spaces with hyphens.
        return preg_replace('/[{}]/', '', $string); // Removes special chars.
    }

    /**
     * Get the route information for a given route.
     *
     * @param \Illuminate\Routing\Route $route
     * @return array
     */
    protected function getRouteInformation(\Illuminate\Routing\Route $route)
    {

        $action = ltrim($route->getActionName(), '\\');
        $func = explode('@', $action);
        if (!isset($func[1])) {
            $func = $func[0];
        } else {
            $func = $func[1];
        }
        $method = $route->methods()[0];
        $name = $this->clean($route->uri()) . "_" . $method . "_" . $func;

        return [
            'method' => $method,
            'route' => $route->uri(),
            'name' => $name,
            'slug' => $route->getName(),
        ];

    }

}
