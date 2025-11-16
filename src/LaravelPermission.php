<?php

namespace Hanafalah\LaravelPermission;

use Hanafalah\LaravelPermission\Contracts\LaravelPermission as ContractsLaravelPermission;
use Hanafalah\LaravelPermission\Enums\Permission\Type;
use Hanafalah\LaravelPermission\Supports\BaseLaravelPermission;
use Illuminate\Support\Str;

class LaravelPermission extends BaseLaravelPermission implements ContractsLaravelPermission {
    protected $__for_api = true;

    public function setForApi(bool $validate): void{
        $this->__for_api = $validate;
    }

    public function getForApi(): bool{
        return $this->__for_api;
    }

    public function scanRoles(string $path): array{
        return $this->fileScans($path,'role');
    }
    
    public function scanPermissions(string $path): array{
        return $this->fileScans($path,'permission');
    }

    private function fileScans(string $path, ?string $type = 'permission'): array{
        $lists = array_map(function ($data) use ($path,$type){
            $file_name = $data;
            $data      = include($path . '/' . $data);
            try {
                if ($type == 'permission'){
                    $this->recursivePermissions($data);            
                }else{
                    $this->recursiveRoles($file_name,$data);            
                }
            } catch (\Throwable $th) {
                throw new \Exception("Error Processing File : ".$file_name." - ".$th->getMessage()." line ".$th->getLine().': '.$data, 1);
            }
            return $data;
        }, array_filter(scandir($path), function ($data) {
            return strpos($data, '.php') !== false;
        }));

        $lists = array_values($lists);
        return $lists;  
    }

    private function recursiveRoles(string $file_name, mixed $permissions){
        $role = Str::replace('.php','',$file_name);
        $role = Str::snake($role);
        $role = Str::replace('_',' ',$role);
        $role = Str::title($role);
        $role = $this->RoleModel()->firstOrCreate(['name' => $role]);
        try {
            $role->syncPermissions($permissions);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    private function recursivePermissions(mixed &$permission,string $alias = '',?string $original_alias = null){
        $is_show_acl = isset($permission['show_in_acl']);
        $permission['main_alias'] = $permission['alias'];
        if ($permission['type'] !== Type::PERMISSION->value){
            $is_menu = $permission['type'] == Type::MENU->value;
            $is_module = $permission['type'] == Type::MODULE->value;
            $permission['alias'] .= (($is_menu || $is_module) && $is_show_acl) ? '.index' : '.';
            if ($alias != '') $permission['alias'] = $alias.$permission['alias'];
            if ($is_menu && $is_show_acl){
                $permission['directory'] = Str::replace('.','/',$permission['alias']);
                $directory = &$permission['directory'];
                $directory = Str::replace('/index','',$directory);
                $directory = Str::replace('/show','',$directory);
                $directory = Str::replace('//','/',$directory);
            }
            $permission['alias'] = Str::replace('..','.',$permission['alias']);
            if ($this->isPermissionHasChild($permission)){
                $permission_alias = $permission['alias'];                
                $permission_alias = Str::replaceLast('.index','',$permission_alias);
                // $permission_alias = Str::replaceLast('.show','',$permission_alias);
                foreach ($permission['childs'] as &$child_permission) {
                    $this->recursivePermissions($child_permission, $permission_alias.'.');
                }
            }
            $permission['guard_name'] = $this->__for_api ? 'api' : 'web';
        }else{
            $alias = Str::replaceLast('.index','',$alias);
            // $alias = Str::replaceLast('.show','',$alias);
            $alias = $alias.$permission['alias'];
            $alias = Str::replace('..','.',$alias);
            $permission['alias'] = $alias;
            if ($this->isPermissionHasChild($permission)){
                foreach ($permission['childs'] as &$child_permission) {
                    $this->recursivePermissions($child_permission, $permission['alias'].'.');
                }
            }
            return $permission;
        }
    }

    private function isPermissionHasChild(array $permission): bool{
        return isset($permission['childs']) && count($permission['childs']) > 0;
    }
}
