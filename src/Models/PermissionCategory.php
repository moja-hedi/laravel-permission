<?php

namespace MojaHedi\Permission\Models;

use MojaHedi\Permission\Contracts\PermissionCategory as PermissionCategoryContract;
use MojaHedi\Permission\Exceptions\PermissionAlreadyExists;
use MojaHedi\Permission\Exceptions\PermissionCategoryDoesNotExist;
use MojaHedi\Permission\Guard;
use MojaHedi\Permission\PermissionRegistrar;
use MojaHedi\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class PermissionCategory extends Model implements PermissionCategoryContract
{
    use HasRoles;

    protected $guarded = ['id'];


    public function __construct(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? config('auth.defaults.guard');

        parent::__construct($attributes);
    }

    public function getTable()
    {
        return config('permission.table_names.permissions_category', parent::getTable());
    }

    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] = $attributes['guard_name'] ?? Guard::getDefaultName(static::class);

        $permission = static::getPermission(['name' => $attributes['name'], 'guard_name' => $attributes['guard_name']]);

        if ($permission) {
            throw PermissionAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * A permission can be applied to roles.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.role'),
            config('permission.table_names.role_has_permissions_category'),
            'permission_category_id',
            'role_id'
        );
    }


    /**
     * A role may be given various permissions.
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            config('permission.models.permission'),
            config('permission.table_names.permissions_has_category'),
            'permission_category_id',
            'permission_id');
    }


    /**
     * @param string $name
     * @param $guardName
     * @return PermissionCategoryContract
     */
    public static function findByName(string $name, $guardName = null): PermissionCategoryContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);
        $permission = static::getPermission(['name' => $name, 'guard_name' => $guardName]);
        if (!$permission) {
            throw PermissionCategoryDoesNotExist::create($name, $guardName);
        }

        return $permission;
    }

    /**
     * Find a permission by its id (and optionally guardName).
     *
     * @param int $id
     * @param string|null $guardName
     *
     * @return \MojaHedi\Permission\Contracts\Permission
     * @throws \MojaHedi\Permission\Exceptions\PermissionCategoryDoesNotExist
     *
     */
    public static function findById(int $id, $guardName = null): PermissionCategoryContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);
        $permission = static::getPermission(['id' => $id, 'guard_name' => $guardName]);

        if (!$permission) {
            throw PermissionCategoryDoesNotExist::withId($id, $guardName);
        }

        return $permission;
    }

    /**
     * @param string $name
     * @param $guardName
     * @param string|null $slug
     * @return PermissionCategoryContract
     */
    public static function findOrCreate(string $name, $guardName = null, string $slug = null): PermissionCategoryContract
    {
        $guardName = $guardName ?? Guard::getDefaultName(static::class);
        $permission = static::getPermission(['name' => $name, 'guard_name' => $guardName, 'slug' => $slug]);

        if (!$permission) {
            return static::query()->create(['name' => $name, 'guard_name' => $guardName, 'slug' => $slug]);
        }

        return $permission;
    }

    /**
     * @param array $params
     * @param bool $onlyOne
     * @return Collection
     */
    protected static function getPermissions(array $params = [], bool $onlyOne = false): Collection
    {
        return app(PermissionRegistrar::class)
            ->setPermissionCategoryClass(static::class)
            ->getPermissionsCategory($params, $onlyOne);
    }

    /**
     * @param array $params
     * @return PermissionCategoryContract|null
     */
    protected static function getPermission(array $params = []): ?PermissionCategoryContract
    {
        return static::getPermissions($params, true)->first();
    }
}
