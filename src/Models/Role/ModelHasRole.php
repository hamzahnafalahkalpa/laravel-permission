<?php

namespace Hanafalah\LaravelPermission\Models\Role;

use Hanafalah\LaravelHasProps\Concerns\HasCurrent;
use Hanafalah\LaravelSupport\Models\BaseModel;

class ModelHasRole extends BaseModel
{
    use HasCurrent;

    public $current_conditions = [
        'model_type',
        'model_id'
    ];
    protected $list = ['id', 'model_type', 'model_id', 'current', 'role_id'];

    public function role()
    {
        return $this->belongsToModel('Role');
    }
    public function model()
    {
        return $this->morphTo();
    }
}
