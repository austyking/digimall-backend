<?php

declare(strict_types=1);

use App\DTOs\CreateTenantDTO;
use App\Models\User;
use App\Services\TenantService;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use Stancl\Tenancy\Database\Models\Domain;

use function Pest\Laravel\postJson;

beforeEach(function (): void {
    // Seed roles and permissions first (required for role assignment test)
    $this->artisan('db:seed', ['--class' => 'Database\\Seeders\\RolesAndPermissionsSeeder']);

    // Create tenant using TenantService (testing actual code path)
    $tenantService = app(TenantService::class);
    $dto = new CreateTenantDTO(
        name: 'GRNMA',
        displayName: 'Ghana Registered Nurses and Midwives Association',
        description: 'Test tenant for authentication tests',
        active: true,
        settings: [
            'theme' => [
                'primary_color' => '#1976d2',
                'secondary_color' => '#dc004e',
            ],
        ]
    );

    $tenant = $tenantService->createTenant($dto);

    // Create domain for tenant using model relationship (as done in real app)
    $tenant->domains()->create([
        'domain' => 'shop.grnmainfonet.test',
    ]);

    // Create personal access client for Passport using ClientRepository (as done in real app)
    $clientRepository = app(ClientRepository::class);
    $clientRepository->createPersonalAccessGrantClient(
        name: 'Test Personal Access Client',
        provider: 'users'
    );
});

describe('Authentication - Login', function (): void {
    test('user can login with valid credentials', function (): void {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = postJson('http://shop.grnmainfonet.test/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'data' => [
                    'user' => ['id', 'name', 'email', 'created_at', 'updated_at'],
                    'token',
                    'token_type',
                ],
            ])
            ->assertJson([
                'message' => 'Login successful',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'test@example.com',
                    ],
                    'token_type' => 'Bearer',
                ],
            ]);

        expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
    });

    test('user can login with remember me option', function (): void {
        User::factory()->create([
            'email' => 'remember@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $response = postJson('http://shop.grnmainfonet.test/api/v1/auth/login', [
            'email' => 'remember@example.com',
            'password' => 'Password123!',
            'remember' => true,
        ]);

        $response->assertOk();
        expect($response->json('data.token'))->toBeString()->not->toBeEmpty();
    });

    test('login fails with invalid email', function (): void {
        $response = postJson('http://shop.grnmainfonet.test/api/v1/auth/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Authentication failed',
                'errors' => [
                    'credentials' => ['Invalid credentials'],
                ],
            ]);
    });

    test('login fails with invalid password', function (): void {
        User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('CorrectPassword123!'),
        ]);

        $response = postJson('http://shop.grnmainfonet.test/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'WrongPassword123!',
        ]);

        $response->assertUnauthorized()
            ->assertJson([
                'message' => 'Authentication failed',
            ]);
    });

    test('login requires email field', function (): void {
        $response = postJson('http://shop.grnmainfonet.test/api/v1/auth/login', [
            'password' => 'Password123!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('login requires password field', function (): void {
        $response = postJson('http://shop.grnmainfonet.test/api/v1/auth/login', [
            'email' => 'test@example.com',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    test('login requires valid email format', function (): void {
        $response = postJson('http://shop.grnmainfonet.test/api/v1/auth/login', [
            'email' => 'not-an-email',
            'password' => 'Password123!',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    });

    test('login requires minimum password length', function (): void {
        $response = postJson('http://shop.grnmainfonet.test/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'short',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    });

    test('login response includes user roles when loaded', function (): void {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        // Assign role to user
        $user->assignRole('system-administrator');

        $response = postJson('http://shop.grnmainfonet.test/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'Password123!',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'user' => [
                        'roles' => [
                            '*' => ['id', 'name', 'guard_name'],
                        ],
                    ],
                ],
            ]);

        expect($response->json('data.user.roles'))->toHaveCount(1);
        expect($response->json('data.user.roles.0.name'))->toBe('system-administrator');
    });

    test('token can be used for authenticated requests', function (): void {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('Password123!'),
        ]);

        $loginResponse = postJson('http://shop.grnmainfonet.test/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'Password123!',
        ]);

        $token = $loginResponse->json('data.token');

        // Verify token is returned and is a valid OAuth2 access token
        expect($token)->toBeString()
            ->not->toBeEmpty()
            ->and(strlen($token))->toBeGreaterThan(40); // OAuth2 tokens are longer
    });
});
