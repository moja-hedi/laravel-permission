<?php

namespace MojaHedi\Permission\Exceptions;

use InvalidArgumentException;

class PermissionCategoryDoesNotExist extends InvalidArgumentException
{
    public static function create(string $permissionCategoryName, string $guardName = '')
    {
        return new static("There is no permission category named `{$permissionCategoryName}` for guard `{$guardName}`.");
    }

    public static function withId(int $permissionCategoryId, string $guardName = '')
    {
        return new static("There is no [permission category] with id `{$permissionCategoryId}` for guard `{$guardName}`.");
    }
}
