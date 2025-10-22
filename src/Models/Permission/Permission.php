<?php

namespace Hanafalah\LaravelPermission\Models\Permission;

use Illuminate\Support\Facades\Route;
use Hanafalah\LaravelSupport\Models\BaseModel;
use Hanafalah\LaravelPermission\Enums\{
    Permission\Type
};
use Illuminate\Support\Str;
use Hanafalah\LaravelHasProps\Concerns\HasProps;
use Hanafalah\LaravelPermission\Resources\Permission\ViewPermission;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Permission extends BaseModel
{
    use HasProps, HasUlids;

    public $timestamps = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';
    protected $fillable = [
        'id', 'parent_id', 'name', 'alias', 'type',
        'guard_name', 'visibility'
    ];

    protected $casts = [
        'name' => 'string',
        'alias' => 'string',
        'type' => 'string',
        'guard_name' => 'string',
        'visibility' => 'boolean',
    ];

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope('visible', function ($query) {
            $query->where('visibility', true);
        });
        static::creating(function ($query) {
            if (!isset($query->guard_name)) $query->guard_name = "api";
            if (!isset($query->visibility)) $query->visibility = true;
            $route = Route::getRoutes()->getByName($query->alias);
            if (isset($route)) {
                $query->method = $route->methods[0] ?? '';
                $query->slug   = $route->uri;
                $query->prefix = $route->action['prefix'];
            }
        });
        static::created(function ($query) {
            // static::rootGenerator($query);
        });
        static::updating(function($query){
            if ($query->type == Type::PERMISSION->value) {
                $query->show_in_acl ??= false;
                $query->show_in_data ??= $query->show_in_acl ? false : true;
            }
        });
        static::updated(function ($query) {
            // static::rootGenerator($query);
        });
    }

    public function viewUsingRelation(): array{
        return [];
    }

    public function showUsingRelation(): array{
        return [];
    }

    public function getViewResource(){
        return ViewPermission::class;
    }

    public function getShowResource(){
        return ViewPermission::class;
    }

    protected static function rootGenerator($query, mixed $dirties = null)
    {
        $dirties ??= $query->getDirtyRoot();
        if (static::isHasRoot($query) && static::needRooting()) {
            if (($query->wasRecentlyCreated && !isset($query->root)) || $query->isDirty($dirties)) {
                if (isset($query->directory)) {
                    $query->directory = rtrim($query->alias, '.');
                    $query->directory = rtrim($query->alias, '.index');
                    $query->directory = rtrim($query->alias, '.show');
                    $directories      = explode('.', $query->directory);
                    foreach ($directories as &$directory) $directory = Str::kebab($directory);
                    $query->directory = implode('/', $directories);
                    $query->directory = Str::replace('/index', '', $query->directory);
                    $query->directory = Str::replace('/show', '', $query->directory);
                    $query->directory = Str::replace('api/', '/', $query->directory);
                    $query->directory = Str::replace('web/', '/', $query->directory);
                }
                if (isset($query->parent_id)) {
                    $parent = (new static)->find($query->parent_id);
                    $rootId = $parent->root . "." . $query->id;
                }
                $query->root = $rootId ?? $query->id;
                $query->saveQuietly();
            }
        }
    }

    public function scopeAlias($builder, mixed $alias, string $alias_name = 'alias')
    {
        return $builder->where(function ($query) use ($alias, $alias_name) {
            $aliases = $this->mustArray($alias);
            foreach ($aliases as $alias) $query->orWhere($alias_name, $alias);
        });
    }

    public function scopeAliases($builder, mixed $alias, string $alias_name = 'alias')
    {
        $builder->where(function ($query) use ($alias, $alias_name) {
            $aliases = $this->mustArray($alias);
            foreach ($aliases as $alias) {
                $query->orWhere(function ($query) use ($alias_name, $alias) {
                    $query->where($alias_name, $alias)
                        ->orWhereLike($alias_name, $alias . '%');
                });
            }
        });
    }

    public function scopeAsModule($builder)
    {
        return $builder->where("type", Type::MODULE->value);
    }

    public function scopeAsPermission($builder)
    {
        return $builder->where("type", Type::PERMISSION->value);
    }

    public function scopeAsMenu($builder)
    {
        return $builder->where("type", Type::MENU->value);
    }

    public function scopeShowInAcl($builder){
        return $builder->where("props->show_in_acl", true);
    }

    public function scopeShowInData($builder){
        return $builder->where("props->show_in_data", true);
    }

    public function scopeCheckAccess($builder, $model_id, $model_type = 'Role')
    {
        $builder->select('*');
        if ($model_type == 'Role') {
            $model = $this->RoleHasPermissionModel();
            $key   = "role_id = ?";
            $bindings = [$model_id];
        } else {
            $model = $this->ModelHasPermissionModel();
            $key   = "CAST(model_id AS CHAR) = '?' AND model_type = '?'";
            $bindings = [$model_id, $model_type];
        }
        $table_name = $model->getTableName();
        $builder->selectRaw("permissions.*,CASE WHEN EXISTS (SELECT permission_id FROM $table_name WHERE $key AND permission_id = permissions.id) THEN TRUE ELSE FALSE END as access", $bindings);
        return $builder;
    }

    //EIGER SECTION
    public function modelHasPermission(){return $this->hasOneModel('ModelHasPermission');}
    public function roleHasPermission(){return $this->hasOneModel('RoleHasPermission');}
    public function recursiveChilds(){
        return $this->hasManyModel('Permission', 'parent_id')->when(isset(request()->role_id), function ($query) {
            $query->checkAccess(request()->role_id);
        })->with('recursiveChilds');
    }
    public function recursiveModules(){
        return $this->hasManyModel('Permission', 'parent_id')->when(isset(request()->role_id), function ($query) {
            $query->checkAccess(request()->role_id);
        })->when(isset(request()->is_show_in_acl) && request()->is_show_in_acl, fn($q) => $q->showInAcl())
        ->asModule()->with('recursiveModules');
    }
    public function recursiveMenus(){
        return $this->hasManyModel('Permission', 'parent_id')
        ->where('type',Type::MENU->value)
        ->when(isset(request()->role_id), function ($query) {
            $query->checkAccess(request()->role_id)
                 ->whereHas('roleHasPermission');
        })->with('recursiveMenus');
    }
}
