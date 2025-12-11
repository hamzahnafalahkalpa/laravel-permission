<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Hanafalah\LaravelSupport\Concerns\NowYouSeeMe;
use Hanafalah\LaravelPermission\Models\Role\ModelHasRole;
use Hanafalah\LaravelPermission\Models\Role\Role;

return new class extends Migration
{
    use NowYouSeeMe;

    private $__table;

    public function __construct()
    {
        $this->__table = app(config('database.models.ModelHasRole', ModelHasRole::class));
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $table_name = $this->__table->getTableName();
        if (!$this->isTableExists()) {
            Schema::create($table_name, function (Blueprint $table) {
                $role = app(config('database.models.Role', Role::class));

                $table->id();
                $table->foreignIdFor($role::class)
                    ->nullable(false)
                    ->index()->constrained()->cascadeOnDelete()
                    ->cascadeOnUpdate();

                $table->string('model_type', 50)->nullable(false);
                $table->string('model_id', 36)->nullable(false);
                $table->unsignedTinyInteger('current')->default(1)->nullable(false);
                $table->timestamps();

                $table->index(['model_type', 'model_id'], 'model_role');
                $table->index(['model_type', 'model_id', $role->getForeignKey()], 'model_with_role');
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
