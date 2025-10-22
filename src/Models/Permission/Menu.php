<?php

namespace Hanafalah\LaravelPermission\Models\Permission;

use Hanafalah\LaravelPermission\Resources\Permission\ViewMenu;

class Menu extends Permission
{
    protected $table = 'permissions';

    protected $casts = [
        'name' => 'string',
        'alias' => 'string',
        'type' => 'string',
        'guard_name' => 'string',
        'visibility' => 'boolean'
    ];

    public function getForeignKey()
    {
        return 'permission_id';
    }

    public function getShowResource(){
        return ViewMenu::class;
    }

    public function getViewResource(){
        return ViewMenu::class;
    }
}
