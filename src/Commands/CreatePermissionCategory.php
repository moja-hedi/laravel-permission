<?php

namespace MojaHedi\Permission\Commands;

use MojaHedi\Permission\Models\Permission;
use Illuminate\Console\Command;
use MojaHedi\Permission\Contracts\PermissionCategory as PermissionContract;

class CreatePermissionCategory extends Command
{
    protected $signature = 'permission:create-permission-category
                {name : The name of the permission category}
                {guard? : The name of the guard}
                {slug? : The slug of the permission category}';

    protected $description = 'Create a permission category';

    public function handle()
    {



        $permissionClass = app(PermissionContract::class);

        $permissionClass->getPermissions();

        $permissionCategory = $permissionClass::findOrCreate($this->argument('name'), $this->argument('guard'), $this->argument('slug'));

        $this->info("Permission category `{$permissionCategory->name}` created");
    }
}
