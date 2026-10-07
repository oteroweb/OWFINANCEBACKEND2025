<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\Business;
use App\Models\Entities\BusinessUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * OWF-370: Fase 2 de "Grupo Familiar y Contabilidad Empresarial" — empresas con
 * contabilidad separada. Una empresa da acceso a TODA su contabilidad con un rol
 * (owner|accountant|viewer); no es el grupo familiar con otro nombre: no es recíproco
 * y un contador no pertenece al grupo familiar.
 */
class BusinessController extends Controller
{
    private function fail(int $code, string $message, $data = null)
    {
        $body = ['status' => 'FAILED', 'code' => $code, 'message' => $message];
        if ($data !== null) $body['data'] = $data;
        return response()->json($body, $code);
    }

    private function forbidden()
    {
        return $this->fail(403, __('Forbidden') . '.');
    }

    private function payload(Business $b, int $userId): array
    {
        $b->loadMissing('members.user');
        $mine = $b->members->firstWhere('user_id', $userId);
        $data = $b->toArray();
        $data['my_role'] = $mine?->role;
        $data['my_status'] = $mine?->status;
        return $data;
    }

    /** Empresas donde el usuario es miembro (activo o con invitación pendiente). */
    public function all(Request $request)
    {
        $userId = $request->user()->id;
        $businesses = Business::whereHas('members', fn($q) => $q->where('user_id', $userId))
            ->with('members.user')->orderBy('name')->get()
            ->map(fn($b) => $this->payload($b, $userId));

        return response()->json(['status' => 'OK', 'code' => 200, 'data' => $businesses], 200);
    }

    public function find(Request $request, $id)
    {
        $b = Business::find($id);
        if (!$b) return $this->fail(404, 'Empresa no encontrada.');
        $isInvitee = $b->members()->where('user_id', $request->user()->id)->exists();
        if (!$isInvitee && $request->user()->cannot('view', $b)) return $this->forbidden();

        return response()->json(['status' => 'OK', 'code' => 200, 'data' => $this->payload($b, $request->user()->id)], 200);
    }

    /** Alta mínima: nombre (obligatorio), RIF, moneda, modo. El color se asigna solo (D-009). */
    public function save(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name'        => 'required|string|max:100',
            'tax_id'      => 'nullable|string|max:40',
            'currency_id' => 'nullable|exists:currencies,id',
            'mode'        => ['nullable', Rule::in(['lite', 'pro'])],
            'color'       => ['nullable', Rule::in(Business::PALETTE)],
        ]);
        if ($v->fails()) return $this->fail(400, __('Incorrect Params'), $v->errors()->getMessages());

        $user = $request->user();
        $b = Business::create([
            'owner_user_id' => $user->id,
            'name'          => trim($request->input('name')),
            'tax_id'        => $request->input('tax_id'),
            'currency_id'   => $request->input('currency_id') ?? $user->currency_id,
            'mode'          => $request->input('mode', 'lite'),
            'color'         => $request->input('color') ?? Business::nextColorFor($user->id),
        ]);
        BusinessUser::create(['business_id' => $b->id, 'user_id' => $user->id, 'role' => 'owner', 'status' => 'active']);
        $b->refresh(); // trae los defaults de DB (profile/onboarded_at null) al payload

        return response()->json([
            'status' => 'OK', 'code' => 201, 'message' => 'Empresa creada correctamente.',
            'data' => $this->payload($b, $user->id),
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $b = Business::find($id);
        if (!$b) return $this->fail(404, 'Empresa no encontrada.');
        if ($request->user()->cannot('manage', $b)) return $this->forbidden();

        $v = Validator::make($request->all(), [
            'name'        => 'sometimes|required|string|max:100',
            'tax_id'      => 'nullable|string|max:40',
            'currency_id' => 'nullable|exists:currencies,id',
            'mode'        => ['sometimes', Rule::in(['lite', 'pro'])],
            'color'       => ['sometimes', Rule::in(Business::PALETTE)],
            'active'      => 'sometimes|boolean',
        ]);
        if ($v->fails()) return $this->fail(400, __('Incorrect Params'), $v->errors()->getMessages());

        $b->update($request->only(['name', 'tax_id', 'currency_id', 'mode', 'color', 'active']));

        return response()->json(['status' => 'OK', 'code' => 200, 'data' => $this->payload($b->fresh(), $request->user()->id)], 200);
    }

    public function delete(Request $request, $id)
    {
        $b = Business::find($id);
        if (!$b) return $this->fail(404, 'Empresa no encontrada.');
        if ($request->user()->cannot('manage', $b)) return $this->forbidden();

        $b->delete();
        return response()->json(['status' => 'OK', 'code' => 200, 'message' => 'Empresa eliminada.'], 200);
    }

    /** D-011: guarda las respuestas del wizard de la empresa y la marca como configurada. */
    public function onboarding(Request $request, $id)
    {
        $b = Business::find($id);
        if (!$b) return $this->fail(404, 'Empresa no encontrada.');
        if ($request->user()->cannot('manage', $b)) return $this->forbidden();

        $v = Validator::make($request->all(), [
            'sector'   => 'nullable|string|max:40',
            'revenue'  => 'nullable|string|max:20',
            'staff'    => 'nullable|string|max:20',
            'seasonal' => 'nullable|boolean',
            'goal'     => 'nullable|string|max:500',
        ]);
        if ($v->fails()) return $this->fail(400, __('Incorrect Params'), $v->errors()->getMessages());

        $b->update([
            'profile'      => $request->only(['sector', 'revenue', 'staff', 'seasonal', 'goal']),
            'onboarded_at' => now(),
        ]);

        return response()->json(['status' => 'OK', 'code' => 200, 'data' => $this->payload($b->fresh(), $request->user()->id)], 200);
    }

    /** Solo el dueño da acceso. El invitado debe tener cuenta; queda 'invited' hasta aceptar. */
    public function invite(Request $request, $id)
    {
        $b = Business::find($id);
        if (!$b) return $this->fail(404, 'Empresa no encontrada.');
        if ($request->user()->cannot('manage', $b)) return $this->forbidden();

        $v = Validator::make($request->all(), [
            'email' => 'required|email',
            'role'  => ['required', Rule::in(Business::ROLES)],
        ]);
        if ($v->fails()) return $this->fail(400, __('Incorrect Params'), $v->errors()->getMessages());

        $invitee = User::where('email', $request->input('email'))->first();
        if (!$invitee) return $this->fail(422, 'No existe ningún usuario registrado con ese correo.');
        if ($invitee->id === $request->user()->id) return $this->fail(422, 'No podés invitarte a vos mismo.');

        $existing = BusinessUser::where('business_id', $b->id)->where('user_id', $invitee->id)->first();
        if ($existing) {
            return $this->fail(422, $existing->status === 'active'
                ? 'Esa persona ya tiene acceso a la empresa.'
                : 'Ya hay una invitación pendiente para ese correo.');
        }

        $m = BusinessUser::create([
            'business_id' => $b->id, 'user_id' => $invitee->id, 'role' => $request->input('role'),
            'status' => 'invited', 'invited_by' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'OK', 'code' => 201, 'message' => 'Invitación enviada.', 'data' => $m->load('user'),
        ], 201);
    }

    public function accept(Request $request, $id)
    {
        $m = BusinessUser::where('business_id', $id)->where('user_id', $request->user()->id)->where('status', 'invited')->first();
        if (!$m) return $this->fail(404, 'No tenés una invitación pendiente en esta empresa.');
        $m->update(['status' => 'active']);

        return response()->json(['status' => 'OK', 'code' => 200, 'message' => 'Ahora tenés acceso a la empresa.', 'data' => $m->load('user')], 200);
    }

    public function decline(Request $request, $id)
    {
        $m = BusinessUser::where('business_id', $id)->where('user_id', $request->user()->id)->where('status', 'invited')->first();
        if (!$m) return $this->fail(404, 'No tenés una invitación pendiente en esta empresa.');
        $m->delete();

        return response()->json(['status' => 'OK', 'code' => 200, 'message' => 'Invitación rechazada.'], 200);
    }

    /** Una empresa sin dueño no la puede administrar nadie. */
    private function isLastOwner(BusinessUser $m): bool
    {
        if ($m->role !== 'owner' || $m->status !== 'active') return false;
        return BusinessUser::where('business_id', $m->business_id)->where('role', 'owner')
            ->where('status', 'active')->where('id', '!=', $m->id)->doesntExist();
    }

    public function updateRole(Request $request, $id, $userId)
    {
        $b = Business::find($id);
        if (!$b) return $this->fail(404, 'Empresa no encontrada.');
        if ($request->user()->cannot('manage', $b)) return $this->forbidden();

        $v = Validator::make($request->all(), ['role' => ['required', Rule::in(Business::ROLES)]]);
        if ($v->fails()) return $this->fail(400, __('Incorrect Params'), $v->errors()->getMessages());

        $m = BusinessUser::where('business_id', $b->id)->where('user_id', $userId)->first();
        if (!$m) return $this->fail(404, 'Esa persona no tiene acceso a la empresa.');
        if ($request->input('role') !== 'owner' && $this->isLastOwner($m)) {
            return $this->fail(422, 'No se puede cambiar el rol del último dueño.');
        }

        $m->update(['role' => $request->input('role')]);
        return response()->json(['status' => 'OK', 'code' => 200, 'data' => $m->load('user')], 200);
    }

    /** El dueño revoca a cualquiera, o un miembro deja la empresa (dos textos distintos en la UI). */
    public function removeUser(Request $request, $id, $userId)
    {
        $b = Business::find($id);
        if (!$b) return $this->fail(404, 'Empresa no encontrada.');

        $isSelf = (int) $userId === $request->user()->id;
        if (!$isSelf && $request->user()->cannot('manage', $b)) return $this->forbidden();

        $m = BusinessUser::where('business_id', $b->id)->where('user_id', $userId)->first();
        if (!$m) return $this->fail(404, 'Esa persona no tiene acceso a la empresa.');
        if ($this->isLastOwner($m)) return $this->fail(422, 'No se puede quitar al último dueño.');

        $m->delete();
        return response()->json(['status' => 'OK', 'code' => 200, 'message' => $isSelf ? 'Dejaste la empresa.' : 'Acceso revocado.'], 200);
    }
}
