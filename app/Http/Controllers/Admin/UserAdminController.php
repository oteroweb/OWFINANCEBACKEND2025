<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entities\Account;
use App\Models\Entities\Jar;
use App\Models\Entities\Transaction;
use App\Models\Entities\UserCurrency;
use App\Models\Entities\UserSetting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class UserAdminController extends Controller
{
    // OWF-140 — POST /admin/users/:id/impersonate
    public function impersonate(Request $request, int $id): JsonResponse
    {
        $target = User::with('role', 'currency')->findOrFail($id);

        if ($target->role && $target->role->slug === 'admin') {
            return response()->json([
                'status'  => 'FAILED',
                'code'    => 403,
                'message' => 'No puedes impersonar a un administrador',
            ], 403);
        }

        if (!$target->active) {
            return response()->json([
                'status'  => 'FAILED',
                'code'    => 422,
                'message' => 'El usuario está inactivo',
            ], 422);
        }

        $token = $target->createToken('impersonate', ['impersonate'], now()->addMinutes(120));

        return response()->json([
            'status'  => 'OK',
            'code'    => 200,
            'message' => 'Impersonación iniciada',
            'data'    => [
                'token'      => $token->plainTextToken,
                'user'       => $target,
                'expires_at' => $token->accessToken->expires_at,
            ],
        ]);
    }

    // OWF-141 — GET /admin/users/:id/detail
    public function detail(Request $request, int $id): JsonResponse
    {
        $user = User::with(['role', 'currency'])->findOrFail($id);

        $settings = UserSetting::where('user_id', $id)->first();

        $accounts = $user->accounts()
            ->with(['currency', 'accountType'])
            ->get(['accounts.id', 'accounts.name', 'accounts.balance', 'accounts.currency_id', 'accounts.include_in_global_balance', 'accounts.active']);

        $jars = Jar::where('user_id', $id)
            ->get(['id', 'name', 'percent', 'type', 'active', 'amount']);

        $recentTx = Transaction::with(['category', 'transactionType'])
            ->where('user_id', $id)
            ->orderByDesc('date')
            ->limit(20)
            ->get(['id', 'name', 'amount', 'date', 'category_id', 'transaction_type_id']);

        $tokensCount = $user->tokens()->count();

        $currencies = UserCurrency::with('currency')
            ->where('user_id', $id)
            ->get();

        return response()->json([
            'status' => 'OK',
            'code'   => 200,
            'data'   => [
                'user'                => $user,
                'settings'            => $settings,
                'accounts'            => $accounts,
                'jars'                => $jars,
                'recent_transactions' => $recentTx,
                'security'            => [
                    'tokens_count' => $tokensCount,
                    'last_login'   => $user->updated_at,
                ],
                'currencies'          => $currencies,
            ],
        ]);
    }

    // OWF-142 — PUT /admin/users/:id/password
    public function changePassword(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->password = Hash::make($request->password);
        $user->save();

        // Revocar todos los tokens existentes para forzar re-login
        $user->tokens()->delete();

        return response()->json([
            'status'  => 'OK',
            'code'    => 200,
            'message' => 'Contraseña actualizada. Sesiones anteriores revocadas.',
        ]);
    }

    // OWF-143 — DELETE /admin/users/:id/tokens
    public function revokeTokens(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $count = $user->tokens()->count();
        $user->tokens()->delete();

        return response()->json([
            'status'  => 'OK',
            'code'    => 200,
            'message' => "{$count} token(s) revocado(s)",
            'data'    => ['revoked_count' => $count],
        ]);
    }

    // OWF-144 — POST /admin/users/:id/reset-password-email
    public function sendResetEmail(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $token = Password::createToken($user);
        $user->notify(new \App\Notifications\ResetPasswordNotification($token));

        return response()->json([
            'status'  => 'OK',
            'code'    => 200,
            'message' => "Email de restablecimiento enviado a {$user->email}",
        ]);
    }
}
