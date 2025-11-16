<?php

namespace Hanafalah\LaravelPermission\Schemas;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Hanafalah\LaravelPermission\Contracts\Schemas\{
    Permission as ContractsPermission
};
use Hanafalah\LaravelPermission\Contracts\Data\PermissionData;
use Hanafalah\LaravelSupport\Supports\PackageManagement;

class Permission extends PackageManagement implements ContractsPermission
{
    protected string $__entity = 'Permission';
    public $permission_model;
    protected mixed $__order_by_created_at = ['ordering','asc']; //asc, desc, false

    public function prepareStorePermission(?array $attributes = null): array{        
        $attributes ??= request()->all();
        $permissions = [];
        foreach ($attributes as $attribute) {
            $permissions[] = $this->addPermission($this->requestDTO(PermissionData::class,$attribute));
        }
        return $permissions;
    }

    public function prepareViewPermissionList(?array $attributes = null): Collection{
        $attributes ??= request()->all();
        $permission = $this->permission()->whereNull('parent_id')->with('recursiveChilds')->get();
        return $this->permission_model = $permission;
    }

    public function prepareShowPermission(?Model $model = null, ?array $attributes = null): Model{
        $attributes ??= request()->all();

        if (isset($model) && $model instanceof Collection) {
            foreach ($model as $key => $new_model) {
                $model[$key] = $this->prepareShowPermission($new_model, $attributes);
            }
        } else {
            $model ??= $this->getPermission();
            if (!isset($model)) {
                $id = $attributes['id'] ?? null;
                if (!isset($id)) throw new \Exception("Data tidak dapat diproses");

                $model = $this->permission()->with($this->showUsingRelation())->findOrFail($id);
            } else {
                $model->load($this->showUsingRelation());
            }
        }
        return $this->permission_model = $model;
    }

    private function addPermission(PermissionData $permission_dto){
        $permission = $this->usingEntity()->firstOrCreate([
            'parent_id' => $permission_dto->parent_id ?? null,
            'alias'     => $permission_dto->alias
        ], [
            'name'      => $permission_dto->name,
            'type'      => $permission_dto->type
        ]);
        $permission_dto->props['is_restricted'] ??= false;
        $this->fillingProps($permission, $permission_dto->props);
        $permission->save();
        $permission->refresh();
        if (isset($permission_dto->childs) && count($permission_dto->childs) > 0) {
            foreach ($permission_dto->childs as $child) {
                $child->parent_id = $permission->getKey();
                $this->addPermission($child);
            }
        }
        return $permission;
    }

    public function permission(mixed $conditionals = null): Builder{
        return $this->generalSchemaModel($conditionals)->when(isset(request()->role_id),function($query){
            $query->checkAccess(request()->role_id);
        });
    }
}
