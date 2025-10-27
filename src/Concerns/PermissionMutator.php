<?php

namespace Hanafalah\LaravelPermission\Concerns;

use Illuminate\Database\Eloquent\Collection;

use Illuminate\Support\Str;
use Hanafalah\LaravelPermission\Models\Permission\Permission;

trait PermissionMutator
{
    public function readPermissions(array $permissions, bool $as_object = false): array
    {
        $new_permissions = [];
        $this->reduceArray($permissions, function ($carry, $permission) use (&$new_permissions, $as_object) {
            $collect = collect($this->readPermission($permission, $as_object))->flatten();
            if (!$collect->isEmpty()) {
                $new_permissions = $this->mergeArray($new_permissions, ...$collect);
            }
        });
        return $new_permissions;
    }

    public function readPermission(string $permission, bool $as_object = false): null|array|string|Collection|Permission
    {
        if (is_object($permission) && $permission instanceof Permission && $as_object) {
            return $permission;
        }
        $has_all = false;
        if (Str::endsWith($permission, '*')) {
            $permission = Str::replaceLast('*', '', $permission);
            $has_all = true;
        }
        $aliases = $this->readAliases($permission);
        if ($has_all) {
            $last_alias = end($aliases);
            array_pop($aliases);
            $builder = $this->PermissionModel()
                ->where(function ($query) use ($aliases, $last_alias) {
                    $query->orWhere(function ($query) use ($aliases) {
                        $query->alias($aliases);
                    })->orWhere(function ($query) use ($last_alias) {
                        $query->aliases($last_alias);
                    });
                })->get();
            return $builder->pluck('id')->toArray();
        } else {
            $builder = $this->PermissionModel()->alias($aliases)->firstOrFail();
            return [$builder->getKey()];
        }
    }

    private function readAliases(string $permission): array
    {
        $current_permission = $permission;
        $permissions = explode('.', $permission);
        $aliases = [];
        $last_alias = '';
        for ($i = 0; $i < count($permissions) - 1; $i++) {
            $permission = $permissions[$i];
            if ($permission == '') continue;
            $alias      = ($last_alias == '' ? $permission : implode('', [$last_alias, $permission])) . '.';
            $aliases[]  = $alias;
            $last_alias = $alias;
        }
        $aliases[] = $current_permission;
        $aliases = array_unique($aliases);
        return $aliases;
    }

    public function getPermission(string|object $permission): Collection|Permission
    {
        return $this->readPermission($permission, true);
    }

    public function hasPermission(object|string $permission): bool
    {
        return $this->readPermission($permission, true) !== null;
    }

    public function hasPermissions(array $permissions): bool
    {
        $exists = true;
        foreach ($permissions as $permission) {
            $exists = $this->hasPermission($permission);
            if (!$exists) {
                break;
            }
        }
        return $exists;
    }
}
