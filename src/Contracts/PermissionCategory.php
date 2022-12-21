<?php

namespace MojaHedi\Permission\Contracts;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;

interface PermissionCategory
{
    /**
     * A permission can be applied to roles.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function roles(): BelongsToMany;

    /**
     * Find a permission by its name.
     *
     * @param string $name
     * @param string|null $guardName
     *
     * @return Permission
     * @throws \MojaHedi\Permission\Exceptions\PermissionDoesNotExist
     *
     */
    public static function findByName(string $name, $guardName): self;

    /**
     * Find a permission by its id.
     *
     * @param int $id
     * @param string|null $guardName
     *
     * @return Permission
     * @throws \MojaHedi\Permission\Exceptions\PermissionDoesNotExist
     *
     */
    public static function findById(int $id, $guardName): self;

    /**
     * @param string $name
     * @param $guardName
     * @param string $slug
     * @return static
     */
    public static function findOrCreate(string $name, $guardName, string $slug): self;
}
