<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Alters the model_id column in permission pivot tables to support UUID primary keys
     * This is necessary because our User model uses UUIDs instead of auto-increment integers
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        // Alter model_has_permissions table
        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames) {
            // Drop the existing foreign key and primary key first
            $table->dropForeign(['permission_id']);
            $table->dropPrimary(['permission_id', $columnNames['model_morph_key'], 'model_type']);

            // Change model_id from unsignedBigInteger to uuid
            $table->uuid($columnNames['model_morph_key'])->change();

            // Recreate the primary key
            $table->primary(['permission_id', $columnNames['model_morph_key'], 'model_type'],
                'model_has_permissions_permission_model_type_primary');

            // Recreate the foreign key
            $table->foreign('permission_id')
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
        });

        // Alter model_has_roles table
        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames) {
            // Drop the existing foreign key and primary key first
            $table->dropForeign(['role_id']);
            $table->dropPrimary(['role_id', $columnNames['model_morph_key'], 'model_type']);

            // Change model_id from unsignedBigInteger to uuid
            $table->uuid($columnNames['model_morph_key'])->change();

            // Recreate the primary key
            $table->primary(['role_id', $columnNames['model_morph_key'], 'model_type'],
                'model_has_roles_role_model_type_primary');

            // Recreate the foreign key
            $table->foreign('role_id')
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');

        // Revert model_has_permissions table
        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($columnNames) {
            $table->dropPrimary(['permission_id', $columnNames['model_morph_key'], 'model_type']);
            $table->unsignedBigInteger($columnNames['model_morph_key'])->change();
            $table->primary(['permission_id', $columnNames['model_morph_key'], 'model_type'],
                'model_has_permissions_permission_model_type_primary');
        });

        // Revert model_has_roles table
        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($columnNames) {
            $table->dropPrimary(['role_id', $columnNames['model_morph_key'], 'model_type']);
            $table->unsignedBigInteger($columnNames['model_morph_key'])->change();
            $table->primary(['role_id', $columnNames['model_morph_key'], 'model_type'],
                'model_has_roles_role_model_type_primary');
        });
    }
};
