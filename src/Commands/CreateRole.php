<?php

namespace MojaHedi\Permission\Commands;

use Illuminate\Console\Command;
use MojaHedi\Permission\Contracts\Permission as PermissionContract;
use MojaHedi\Permission\Contracts\PermissionCategory as PermissionCategoryContract;
use MojaHedi\Permission\Contracts\Role as RoleContract;

class CreateRole extends Command
{
    protected $signature = 'permission:create-role
        {name : The name of the role}
        {guard? : The name of the guard}
        {permissions? : A list of permissions to assign to the role, separated by | }
        {permissionCategory? : A list of permissions category to assign to the role, separated by | }
        ';

    protected $description = 'Create a role';

    public function handle()
    {
        $roleClass = app(RoleContract::class);

        $role = $roleClass::findOrCreate($this->argument('name'), $this->argument('guard'));

        $role->givePermissionTo($this->makePermissions($this->argument('permissions')));
       // $role->givePermissionCategoryTo($this->makePermissionCategory($this->argument('permissionCategory')));

        $this->info("Role `{$role->name}` created");
    }

    /**
     * @param array|null|string $string
     */
    protected function makePermissions($string = null)
    {
        if (empty($string)) {
            return;
        }

        $permissionClass = app(PermissionContract::class);

        $permissions = explode('|', $string);

        $models = [];

        foreach ($permissions as $permission) {
            $models[] = $permissionClass::findOrCreate(trim($permission), $this->argument('guard'));
        }

        return collect($models);
    }

    /**
     * @param array|null|string $string
     */
    protected function makePermissionCategory($string = null)
    {
        if (empty($string)) {
            return;
        }

        $permissionClass = app(PermissionCategoryContract::class);

        $permissions = explode('|', $string);

        $models = [];

        foreach ($permissions as $permission) {
            $models[] = $permissionClass::findOrCreate(trim($permission), $this->argument('guard'));
        }

        return collect($models);
    }
}
