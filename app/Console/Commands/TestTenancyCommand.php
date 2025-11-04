<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantConfigService;
use Illuminate\Console\Command;
use Stancl\Tenancy\Facades\Tenancy;

class TestTenancyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tenancy:test {domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test tenancy resolution by domain';

    /**
     * Execute the console command.
     */
    public function handle(TenantConfigService $configService): int
    {
        $domain = $this->argument('domain');

        $this->info("Testing tenancy for domain: {$domain}");
        $this->newLine();

        // Find tenant by domain
        $tenant = Tenant::query()
            ->whereHas('domains', fn ($query) => $query->where('domain', $domain))
            ->first();

        if (! $tenant) {
            $this->error("No tenant found for domain: {$domain}");

            return self::FAILURE;
        }

        $this->info("✅ Tenant found: {$tenant->name} ({$tenant->id})");
        $this->newLine();

        // Initialize tenancy
        Tenancy::initialize($tenant);

        $this->info('✅ Tenancy initialized');
        $this->info('Current tenant: '.tenant('id'));
        $this->newLine();

        // Get branding config
        $branding = $configService->getBrandingConfig();
        $this->info('🎨 Branding Configuration:');
        $this->table(
            ['Key', 'Value'],
            collect($branding)->map(fn ($value, $key) => [$key, $value])
        );
        $this->newLine();

        // Get API config
        $apiConfig = $configService->getApiConfig();
        $this->info('⚙️  API Configuration:');
        $this->line(json_encode($apiConfig, JSON_PRETTY_PRINT));
        $this->newLine();

        // Test settings
        $this->info('🔧 Testing settings...');
        $hirePurchaseEnabled = $configService->isFeatureEnabled('hire_purchase');
        $this->line('Hire Purchase Enabled: '.($hirePurchaseEnabled ? 'Yes' : 'No'));

        // End tenancy
        Tenancy::end();
        $this->info('✅ Tenancy ended');

        return self::SUCCESS;
    }
}
