<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Resources\StaffResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(LoginRequest $request): StaffResource
    {
        $credentials = $request->validated();
        $user = User::whereRaw('LOWER(email) = ?', [mb_strtolower($credentials['email'])])->first();
        // Match the existing staff-role boundary; customer rows cannot sign in.
        $validPassword = Hash::check($credentials['password'], $user?->password ?? '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.');
        if (! $validPassword || ! $user || ! $user->isStaff()) {
            throw ValidationException::withMessages(['email' => ['The provided credentials are incorrect.']]);
        }
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->regenerateToken();
        $request->session()->forget('active_tenant_id');
        if (\Illuminate\Support\Facades\Schema::hasTable('tenant_memberships')) {
            $memberships = \Illuminate\Support\Facades\DB::table('tenant_memberships')->where('user_id', $user->id)->where('status', 'active')->pluck('tenant_id');
            if ($memberships->count() === 1) $request->session()->put('active_tenant_id', $memberships->first());
        }
        return new StaffResource($user->load('role'));
    }

    public function me(Request $request): StaffResource
    {
        return new StaffResource($request->user()->load('role'));
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        Auth::guard('sanctum')->forgetUser();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->noContent();
    }
}
