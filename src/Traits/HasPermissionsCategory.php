<?php

namespace MojaHedi\Permission\Traits;

use MojaHedi\Permission\Contracts\PermissionCategory;
use MojaHedi\Permission\Exceptions\GuardDoesNotMatch;
use MojaHedi\Permission\Exceptions\PermissionCategoryDoesNotExist;
use MojaHedi\Permission\Exceptions\WildcardPermissionInvalidArgument;
use MojaHedi\Permission\Guard;
use MojaHedi\Permission\PermissionRegistrar;
use MojaHedi\Permission\WildcardPermission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;

trait HasPermissionsCategory
{
    /** @var string */
    private $permissionCategoryClass;


    public static function bootHasPermissionsCategory()
    {
        static::deleting(function ($model) {
            if (method_exists($model, 'isForceDeleting') && !$model->isForceDeleting()) {
                return;
            }

            $model->permissionsCategory()->detach();
        });
    }

    public function getPermissionCategoryClass()
    {
        if (!isset($this->permissionCategoryClass)) {
            $this->permissionCategoryClass = app(PermissionRegistrar::class)->getPermissionCategoryClass();
        }

        return $this->permissionCategoryClass;
    }


    /**
     * @param Builder $query
     * @param $permissions
     * @return Builder
     */
    public function scopePermissionCategoryCategory(Builder $query, $permissions): Builder
    {
        $permissions = $this->convertToPermissionCategoryModels($permissions);

        $rolesWithPermissions = array_unique(array_reduce($permissions, function ($result, $permission) {
            return array_merge($result, $permission->roles->all());
        }, []));

        return $query->where(function (Builder $query) use ($permissions, $rolesWithPermissions) {
            $query->whereHas('permissionsCategory', function (Builder $subQuery) use ($permissions) {
                $subQuery->whereIn(config('permission.table_names.permissions_category') . '.id', \array_column($permissions, 'id'));
            });
            if (count($rolesWithPermissions) > 0) {
                $query->orWhereHas('roles', function (Builder $subQuery) use ($rolesWithPermissions) {
                    $subQuery->whereIn(config('permission.table_names.roles') . '.id', \array_column($rolesWithPermissions, 'id'));
                });
            }
        });
    }

    /**
     * @param $permissions
     * @return array
     */
    protected function convertToPermissionCategoryModels($permissions): array
    {
        if ($permissions instanceof Collection) {
            $permissions = $permissions->all();
        }

        $permissions = is_array($permissions) ? $permissions : [$permissions];

        return array_map(function ($permission) {
            if ($permission instanceof PermissionCategory) {
                return $permission;
            }
            $method = is_string($permission) ? 'findByName' : 'findById';

            return $this->getPermissionCategoryClass()->{$method}($permission, $this->getDefaultGuardName());
        }, $permissions);
    }

    /**
     * @param $permission
     * @param $guardName
     * @return bool
     */
    public function hasPermissionCategoryTo($permission, $guardName = null): bool
    {
        if (config('permission.enable_wildcard_permission', false)) {
            return $this->hasWildcardPermissionCategory($permission, $guardName);
        }

        $permissionClass = $this->getPermissionCategoryClass();

        if (is_string($permission)) {
            $permission = $permissionClass->findByName(
                $permission,
                $guardName ?? $this->getDefaultGuardName()
            );
        }

        if (is_int($permission)) {
            $permission = $permissionClass->findById(
                $permission,
                $guardName ?? $this->getDefaultGuardName()
            );
        }

        if (!$permission instanceof PermissionCategory) {
            throw new PermissionCategoryDoesNotExist;
        }

        return $this->hasDirectPermissionCategory($permission) || $this->hasPermissionCategoryViaRole($permission);
    }

    /**
     * @param $permission
     * @param $guardName
     * @return bool
     */
    protected function hasWildcardPermissionCategory($permission, $guardName = null): bool
    {
        $guardName = $guardName ?? $this->getDefaultGuardName();

        if (is_int($permission)) {
            $permission = $this->getPermissionCategoryClass()->findById($permission, $guardName);
        }

        if ($permission instanceof PermissionCategory) {
            $permission = $permission->name;
        }

        if (!is_string($permission)) {
            throw WildcardPermissionInvalidArgument::create();
        }

        foreach ($this->getAllPermissionsCategory() as $userPermission) {
            if ($guardName !== $userPermission->guard_name) {
                continue;
            }

            $userPermission = new WildcardPermission($userPermission->name);

            if ($userPermission->implies($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $permission
     * @param $guardName
     * @return bool
     */
    public function checkPermissionCategoryTo($permission, $guardName = null): bool
    {
        try {
            return $this->hasPermissionCategoryTo($permission, $guardName);
        } catch (PermissionCategoryDoesNotExist $e) {
            return false;
        }
    }

    /**
     * @param ...$permissions
     * @return bool
     */
    public function hasAnyPermissionCategory(...$permissions): bool
    {
        $permissions = collect($permissions)->flatten();

        foreach ($permissions as $permission) {
            if ($this->checkPermissionCategoryTo($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param ...$permissions
     * @return bool
     */
    public function hasAllPermissionsCategory(...$permissions): bool
    {
        $permissions = collect($permissions)->flatten();

        foreach ($permissions as $permission) {
            if (!$this->hasPermissionCategoryTo($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine if the model has, via roles, the given permission.
     *
     * @param \MojaHedi\Permission\Contracts\Permission $permission
     *
     * @return bool
     */
    protected function hasPermissionCategoryViaRole(PermissionCategory $permission): bool
    {
        return $this->hasRole($permission->roles);
    }

    /**
     * @param $permission
     * @return bool
     */
    public function hasDirectPermissionCategory($permission): bool
    {
        $permissionClass = $this->getPermissionCategoryClass();

        if (is_string($permission)) {
            $permission = $permissionClass->findByName($permission, $this->getDefaultGuardName());
        }

        if (is_int($permission)) {
            $permission = $permissionClass->findById($permission, $this->getDefaultGuardName());
        }

        if (!$permission instanceof PermissionCategory) {
            throw new PermissionCategoryDoesNotExist;
        }

        return $this->permissionsCategory->contains('id', $permission->id);
    }

    /**
     * Return all the permissions the model has via roles.
     */
    public function getPermissionCategoryViaRoles(): Collection
    {
        return $this->loadMissing('roles', 'roles.permissions')
            ->roles->flatMap(function ($role) {
                return $role->permissionsCategory;
            })->sort()->values();
    }

    /**
     * Return all the permissions the model has, both directly and via roles.
     */


    /**
     * @param ...$permissions
     * @return $this
     */
    public function givePermissionCategoryTo(...$permissions)
    {
        $permissions = collect($permissions)
            ->flatten()
            ->map(function ($permission) {
                if (empty($permission)) {
                    return false;
                }

                return $this->getStoredPermissionCategory($permission);
            })
            ->filter(function ($permission) {
                return $permission instanceof PermissionCategory;
            })
            ->each(function ($permission) {
                $this->ensureModelSharesGuard($permission);
            })
            ->map->id
            ->all();

        $model = $this->getModel();

        if ($model->exists) {
            $this->permissionsCategory()->sync($permissions, false);
            $model->load('permissionsCategory');
        } else {
            $class = \get_class($model);

            $class::saved(
                function ($object) use ($permissions, $model) {
                    if ($model->getKey() != $object->getKey()) {
                        return;
                    }
                    $model->permissionsCategory()->sync($permissions, false);
                    $model->load('permissionsCategory');
                }
            );
        }


        return $this;
    }


    public function getPermissionCategoryNames(): Collection
    {
        return $this->permissionsCategory->pluck('name');
    }

    /**
     * @param string|int|array|\MojaHedi\Permission\Contracts\Permission|\Illuminate\Support\Collection $permissions
     *
     * @return \MojaHedi\Permission\Contracts\Permission|\MojaHedi\Permission\Contracts\Permission[]|\Illuminate\Support\Collection
     */
    protected function getStoredPermissionCategory($permissions)
    {
        $permissionCategoryClass = $this->getPermissionCategoryClass();

        if (is_numeric($permissions)) {
            return $permissionCategoryClass->findById($permissions, $this->getDefaultGuardName());
        }

        if (is_string($permissions)) {
            return $permissionCategoryClass->findByName($permissions, $this->getDefaultGuardName());
        }

        if (is_array($permissions)) {
            return $permissionCategoryClass
                ->whereIn('name', $permissions)
                ->whereIn('guard_name', $this->getGuardNames())
                ->get();
        }

        return $permissions;
    }


    /**
     * @param ...$permissions
     * @return bool
     */
    public function hasAllDirectPermissionsCategory(...$permissions): bool
    {
        $permissions = collect($permissions)->flatten();

        foreach ($permissions as $permission) {
            if (!$this->hasDirectPermissionCategory($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param ...$permissions
     * @return bool
     */
    public function hasAnyDirectPermissionCategory(...$permissions): bool
    {
        $permissions = collect($permissions)->flatten();

        foreach ($permissions as $permission) {
            if ($this->hasDirectPermissionCategory($permission)) {
                return true;
            }
        }

        return false;
    }
}
