<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tenancy\TenantService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Resources\StaffResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegistrationController extends Controller
{
    public function store(RegisterRequest $request)
    {
        [$user, $tenant] = DB::transaction(function () use ($request) {
            $data = $request->validated();
            $user = User::create(['name' => $data['name'], 'email' => $data['email'], 'password' => $data['password'], 'role_id' => null]);
            $tenant = app(TenantService::class)->create($user, ['name' => $data['business_name'], 'slug' => $data['slug'], 'timezone' => $data['timezone'], 'currency_code' => $data['currency_code']]);
            return [$user, $tenant];
        });
        Auth::guard('web')->login($user);
        $request->session()->regenerate(); $request->session()->regenerateToken();
        $request->session()->put('active_tenant_id', $tenant);
        return (new StaffResource($user))->response()->setStatusCode(201);
    }
}
