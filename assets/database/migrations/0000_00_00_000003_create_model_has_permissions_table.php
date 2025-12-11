<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Hanafalah\LaravelSupport\Concerns\NowYouSeeMe;
use Hanafalah\LaravelPermission\Models\Permission\ModelHasPermission;
use Hanafalah\LaravelPermission\Models\Permission\Permission;

return new class extends Migration
{
    use NowYouSeeMe;

    private $__table;

    public function __construct()
    {
        $this->__table = app(config('database.models.ModelHasPermission', ModelHasPermission::class));
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

                $table->id();
                $table->foreignIdFor($permission::class)
                    ->nullable(false)
                    ->index()->constrained()->cascadeOnDelete()
                    ->cascadeOnUpdate();

                $table->string('model_type', 50)->nullable(false);
                $table->string('model_id', 36)->nullable(false);
                $table->timestamps();

                $table->index(['model_type', 'model_id'], 'model_perm');
                $table->index(['model_type', 'model_id', $permission->getForeignKey()], 'model_with_perm');
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
