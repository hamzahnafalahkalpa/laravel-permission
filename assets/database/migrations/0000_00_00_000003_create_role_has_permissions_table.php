<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Hanafalah\LaravelSupport\Concerns\NowYouSeeMe;
use Hanafalah\LaravelPermission\Models\Role\RoleHasPermission;
use Hanafalah\LaravelPermission\Models\Permission\Permission;
use Hanafalah\LaravelPermission\Models\Role\Role;

return new class extends Migration
{
    use NowYouSeeMe;

    private $__table;

    public function __construct()
    {
        $this->__table = app(config('database.models.RoleHasPermission', RoleHasPermission::class));
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table_name = $this->__table->getTableName();
        if (!$this->isTableExists()) {
            Schema::create($table_name, function (Blueprint $table) {
                $permission = app(config('database.models.Permission', Permission::class));
                $role       = app(config('database.models.Role', Role::class));

                $table->id();
                $table->foreignIdFor($permission::class)
                    ->nullable(false)
                    ->index()->constrained()->cascadeOnDelete()
                    ->cascadeOnUpdate();

                $table->foreignIdFor($role::class)
                    ->nullable(false)
                    ->index()->constrained()->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->timestamps();

                $table->index([$role->getForeignKey(), $permission->getForeignKey()], 'role_permission');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists($this->__table->getTableName());
    }
};
