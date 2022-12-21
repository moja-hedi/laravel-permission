<?php

namespace MojaHedi\Permission\Exceptions;

use InvalidArgumentException;

class PermissionCategoryAlreadyExists extends InvalidArgumentException
{
    public static function create(string $permissionCategoryName, string $guardName)
    {
        return new static("A `{$permissionCategoryName}` permission category already exists for guard `{$guardName}`.");
    }
}
