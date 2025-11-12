<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Foreign keys
            $table->foreignUuid('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');

            // Basic information
            $table->string('business_name');
            $table->string('contact_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();

            // Address information
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->default('Ghana');

            // Business details
            $table->string('business_registration_number')->nullable();
            $table->string('tax_id')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('bank_account_name')->nullable();

            // Status and approval workflow
            $table->enum('status', ['pending', 'approved', 'rejected', 'suspended', 'active'])->default('pending');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('suspension_reason')->nullable();

            // Commission and financial settings
            $table->decimal('commission_rate', 5, 2)->default(15.00); // Percentage
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');

            // Metadata
            $table->json('settings')->nullable(); // Vendor-specific settings
            $table->json('metadata')->nullable(); // Additional data

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'status']);
            $table->index(['user_id']);
            $table->index(['email']);
            $table->index(['status']);
            $table->index(['approved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
