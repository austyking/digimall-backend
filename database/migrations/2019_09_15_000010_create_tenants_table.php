<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTenantsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Custom fields for DigiMall
            $table->string('name'); // Association name (e.g., "GRNMA", "GMA")
            $table->string('subdomain')->unique(); // e.g., "grnmainfonet", "ghanamedassoc"
            $table->string('display_name'); // Full association name
            $table->string('logo_url')->nullable();
            $table->boolean('active')->default(true);
            $table->json('settings')->nullable(); // Store theme colors, features, payment gateways, etc.

            $table->timestamps();
            $table->json('data')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
}
