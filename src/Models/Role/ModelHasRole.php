<?php

namespace Hanafalah\LaravelPermission\Models\Role;

use Hanafalah\LaravelHasProps\Concerns\HasCurrent;
use Hanafalah\LaravelSupport\Models\BaseModel;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class ModelHasRole extends BaseModel
{
    use HasUlids, HasCurrent;

    public $incrementing = false;
    protected $keyType = 'string';
    protected $primaryKey = 'id';
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
