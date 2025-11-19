<?php

namespace Hanafalah\LaravelPermission\Schemas;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Hanafalah\LaravelPermission\Contracts\Schemas\Role as ContractsRole;
use Hanafalah\LaravelPermission\Data\RoleData;
use Hanafalah\LaravelSupport\Supports\PackageManagement;

class Role extends PackageManagement implements ContractsRole
{
    protected string $__entity = 'Role';
    public $role_model;

    public function showRole(?Model $model = null): array{
        $id ??= $model->id ?? request()->id;
        request()->merge([
            'role_id' => $id
        ]);
        return parent::generalShow($model);
    }

    public function prepareStoreRole(RoleData $role_dto): Model{
        $add   = [
            'name' => $role_dto->name,
            'parent_id' => $role_dto->parent_id
        ];
        if (isset($role_dto->id)){
            $guard = ['id' => $role_dto->id];
            $create = [$guard,$add];
        }else{
            $create = [$add];
        }
        $role  = $this->usingEntity()->updateOrCreate(...$create);
        $role->permission_ids = $role_dto->permission_ids ?? [];
        $this->fillingProps($role,$role_dto->props);

        if (isset($role_dto->permission_ids) || isset($role_dto->permissions)) {
            $permissions = $role_dto->permission_ids ?? $role_dto->permissions;
            $permissions = $this->mustArray($permissions);
            if (count($permissions) > 0) {
                $role->syncPermissions($permissions, true);
                $role->setAttribute('permissions_ids', $permissions);
            } else {
                $role->flushPermissions();
                $role->setAttribute('permissions_ids', []);
            }
            $role->save();
        }

        return $this->role_model = $role;
    }

    public function role(mixed $conditionals = null): Builder{
        return $this->RoleModel()->withParameters()
                    ->conditionals($this->mergeCondition($conditionals ?? []))
                    ->when(isset(request()->parent_id), function ($query) {
                        $query->where('parent_id', request()->parent_id);
                    })
                    ->orderBy('name', 'asc');
    }
}
