<?php

namespace Hanafalah\LaravelPermission\Schemas;

use Illuminate\Database\Eloquent\Collection;
use Hanafalah\LaravelPermission\Contracts\Schemas\{
    Menu as ContractsMenu
};
use Illuminate\Database\Eloquent\Builder;

class Menu extends Permission implements ContractsMenu
{
    protected string $__entity = 'Menu';
    public $menu_model;
    protected mixed $__order_by_created_at = ['ordering']; //asc, desc, false

    public function prepareViewMenuList(): Collection{
        $attributes ??= request()->all();
        
        if (!isset($attributes['role_id'])) throw new \Exception('Role id not found');
        $menu = $this->menu()->whereHas('roleHasPermission',function($query) use ($attributes){
                                $query->where('role_id',$attributes['role_id']);
                            })->get();
        return $this->menu_model = $menu;
    }

    public function menu(mixed $conditionals = null): Builder{
        return $this->permission()->when(isset(request()->role_id), function ($query) {
            $query->checkAccess(request()->role_id);
        })->when(isset(request()->is_menu) && request()->is_menu,function($query){
            $query->with('recursiveMenus')->asMenu()->whereNull('parent_id');
        })->when(isset(request()->is_module) && request()->is_module,function($query){
            $query->with('recursiveModules')->asModule();
        })->when(isset(request()->is_permission) && request()->is_permission,function($query){
            $query->asPermission();
        })->orderBy('ordering','asc');
    }
}
