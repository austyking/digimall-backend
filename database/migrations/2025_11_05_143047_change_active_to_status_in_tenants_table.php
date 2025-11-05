<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add new status column
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('status')->default('active')->after('logo_url');
        });

        // Migrate data: active = true -> 'active', active = false -> 'inactive'
        DB::table('tenants')
            ->where('active', true)
            ->update(['status' => 'active']);

        DB::table('tenants')
            ->where('active', false)
            ->update(['status' => 'inactive']);

        // Drop old active column
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add back active column
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('active')->default(true)->after('logo_url');
        });

        // Migrate data back: 'active' -> true, 'inactive' -> false
        DB::table('tenants')
            ->where('status', 'active')
            ->update(['active' => true]);

        DB::table('tenants')
            ->where('status', 'inactive')
            ->update(['active' => false]);

        // Drop status column
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
