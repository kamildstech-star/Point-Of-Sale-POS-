<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiFoundationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // A minimal legacy-schema fixture, never the configured business DB.
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        Schema::create('roles', function (Blueprint $table) {
            $table->id(); $table->string('role'); $table->text('permission')->nullable();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email')->nullable();
            $table->string('password')->nullable(); $table->unsignedBigInteger('role_id')->nullable();
            $table->rememberToken(); $table->timestamps();
        });
        DB::table('roles')->insert([
            ['id' => 1, 'role' => 'Super Admin', 'permission' => '["13"]'],
            ['id' => 2, 'role' => 'Admin', 'permission' => '["1","2"]'],
        ]);
        User::create(['name' => 'Staff Member', 'email' => 'staff@example.test', 'password' => Hash::make('test-password'), 'role_id' => 1]);
    }

    public function test_login_me_and_logout_use_a_session_and_safe_resource(): void
    {
        $this->getJson('/api/v1/me')->assertUnauthorized()->assertJsonPath('message', 'Unauthenticated.');
        $this->postJson('/api/v1/auth/login', ['email' => 'staff@example.test', 'password' => 'test-password'])
            ->assertOk()->assertJsonPath('data.permissions.0', 'dashboard.view')
            ->assertJsonMissingPath('data.password')->assertJsonMissingPath('data.remember_token');
        $this->assertAuthenticated('web');
        $this->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.name', 'Staff Member');
        $token = session()->token();
        $this->postJson('/api/v1/auth/logout')->assertNoContent();
        $this->assertGuest('web');
        $this->assertNotSame($token, session()->token());
        $this->getJson('/api/v1/me')->assertUnauthorized();
    }

    public function test_login_rotates_the_session_id(): void
    {
        $this->withSession(['_token' => 'before-login']);
        $old = session()->getId();
        $this->postJson('/api/v1/auth/login', ['email' => 'staff@example.test', 'password' => 'test-password'])->assertOk();
        $this->assertNotSame($old, session()->getId());
        $this->assertNotSame('before-login', session()->token());
    }

    public function test_validation_invalid_credentials_and_customer_accounts_are_rejected(): void
    {
        $this->postJson('/api/v1/auth/login', [])->assertUnprocessable()->assertJsonValidationErrors(['email', 'password']);
        $this->postJson('/api/v1/auth/login', ['email' => 'staff@example.test', 'password' => 'wrong'])->assertUnprocessable();
        User::create(['name' => 'Customer', 'email' => 'customer@example.test', 'password' => 'test-password', 'role_id' => null]);
        $this->postJson('/api/v1/auth/login', ['email' => 'customer@example.test', 'password' => 'test-password'])->assertUnprocessable();
        $this->assertGuest();
    }

    public function test_customer_sessions_cannot_access_the_staff_api(): void
    {
        $customer = User::create(['name' => 'Customer', 'email' => 'customer@example.test', 'password' => 'test-password', 'role_id' => null]);
        $this->actingAs($customer)->getJson('/api/v1/me')->assertForbidden();
    }

    public function test_permissions_are_not_inferred_from_role_name_or_id(): void
    {
        DB::table('users')->update(['role_id' => 2]);
        $this->actingAs(User::first())->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.permissions', []);
        DB::table('roles')->where('id', 2)->update(['permission' => 'invalid-json']);
        Auth::forgetGuards();
        $this->actingAs(User::first())->getJson('/api/v1/me')->assertOk()->assertJsonPath('data.permissions', []);
    }

    public function test_tenant_placeholder_never_trusts_supplied_tenant_ids(): void
    {
        $this->actingAs(User::first())->getJson('/api/v1/tenants')->assertOk()
            ->assertExactJson(['data' => [], 'meta' => ['active_tenant' => null, 'onboarding_available' => false]]);
        $this->postJson('/api/v1/tenant/switch', ['tenant_id' => 999])->assertStatus(409);
        $this->postJson('/api/v1/tenants/999/activate')->assertStatus(409);
        $this->assertNull(session('tenant_id'));
    }

    public function test_login_attempts_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', ['email' => 'staff@example.test', 'password' => 'wrong'])->assertUnprocessable();
        }
        $this->postJson('/api/v1/auth/login', ['email' => 'staff@example.test', 'password' => 'wrong'])
            ->assertStatus(429)->assertHeader('Retry-After');
    }

    public function test_csrf_is_enforced_for_mutations_even_without_origin(): void
    {
        // Laravel bypasses CSRF during tests by default. Enable the actual middleware.
        $this->app['env'] = 'local';
        $this->postJson('/api/v1/auth/login', ['email' => 'staff@example.test', 'password' => 'test-password'])->assertStatus(419);
        $this->get('/sanctum/csrf-cookie')->assertNoContent()->assertCookie('XSRF-TOKEN');
        $this->withSession(['_token' => 'known-csrf-token'])->postJson('/api/v1/auth/login',
            ['email' => 'staff@example.test', 'password' => 'test-password'], ['X-CSRF-TOKEN' => 'known-csrf-token'])->assertOk();
        $this->postJson('/api/v1/auth/logout')->assertStatus(419);
        $this->withSession(['_token' => 'logout-token'])->postJson('/api/v1/auth/logout', [], ['X-CSRF-TOKEN' => 'logout-token'])->assertNoContent();
    }

    public function test_cors_allows_only_configured_origins_with_credentials(): void
    {
        $headers = ['Origin' => 'http://127.0.0.1:5173', 'Access-Control-Request-Method' => 'POST', 'Access-Control-Request-Headers' => 'Content-Type,X-XSRF-TOKEN'];
        $this->options('/api/v1/auth/login', [], $headers)->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:5173')->assertHeader('Access-Control-Allow-Credentials', 'true');
        $headers['Origin'] = 'https://untrusted.example';
        $this->options('/api/v1/auth/login', [], $headers)->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    public function test_api_errors_are_json_even_without_an_accept_header(): void
    {
        $this->get('/api/v1/does-not-exist')->assertNotFound()->assertExactJson(['message' => 'Resource not found.']);
        $this->get('/api/v1/me')->assertUnauthorized()->assertExactJson(['message' => 'Unauthenticated.']);
        $this->assertArrayNotHasKey('password', User::first()->toArray());
    }

    public function test_bearer_tokens_are_not_accepted_and_canonical_me_route_works(): void
    {
        $this->withHeader('Authorization', 'Bearer 1|invented-token')->getJson('/api/v1/auth/me')->assertUnauthorized();
        $this->actingAs(User::first(), 'web')->getJson('/api/v1/auth/me')->assertOk()->assertJsonPath('data.id', 1);
    }

    public function test_browser_origin_login_enforces_csrf_and_keeps_a_single_session_pipeline(): void
    {
        $this->app['env'] = 'local';
        $headers = ['Origin' => 'http://127.0.0.1:5173'];
        $this->postJson('/api/v1/auth/login', ['email' => 'staff@example.test', 'password' => 'test-password'], $headers)->assertStatus(419);
        $this->withSession(['_token' => 'browser-token'])->postJson('/api/v1/auth/login',
            ['email' => 'staff@example.test', 'password' => 'test-password'], $headers + ['X-CSRF-TOKEN' => 'browser-token'])
            ->assertOk()->assertHeader('Access-Control-Allow-Origin', 'http://127.0.0.1:5173');
        $this->getJson('/api/v1/me', $headers)->assertOk();
        $route = app('router')->getRoutes()->match(\Illuminate\Http\Request::create('/api/v1/me'));
        $middleware = app('router')->gatherRouteMiddleware($route);
        $this->assertNotContains(\Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class, $middleware);
        $this->assertContains(\Illuminate\Session\Middleware\StartSession::class, $middleware);
    }
}
