<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\FamilyGroup;
use App\Models\Entities\FamilyGroupMember;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * OWF-369: Fase 1 de "Grupo Familiar y Contabilidad Empresarial" — un usuario puede
 * pertenecer a varios grupos familiares a la vez (confirmado con el usuario). Sirve
 * de candado de negocio para AccountController::share() — solo se puede compartir
 * una cuenta con alguien que ya sea miembro activo de un grupo familiar en común.
 */
class FamilyGroupController extends Controller
{
    /**
     * @group FamilyGroup
     * Grupos familiares donde el usuario autenticado es miembro (activo o invitado).
     */
    public function all(Request $request)
    {
        $userId = $request->user()->id;

        $groups = FamilyGroup::whereHas('members', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        })->with(['members.user'])->get();

        return response()->json(['status' => 'OK', 'code' => 200, 'data' => $groups], 200);
    }

    /**
     * @group FamilyGroup
     * Detalle de un grupo, con sus miembros. Requiere ser miembro.
     */
    public function find(Request $request, $id)
    {
        $group = FamilyGroup::with(['members.user'])->find($id);
        if (!isset($group->id)) {
            return response()->json(['status' => 'FAILED', 'code' => 404, 'message' => 'Grupo familiar no encontrado.'], 404);
        }
        if ($request->user()->cannot('view', $group)) {
            return response()->json(['status' => 'FAILED', 'code' => 403, 'message' => __('Forbidden') . '.'], 403);
        }

        return response()->json(['status' => 'OK', 'code' => 200, 'data' => $group], 200);
    }

    /**
     * @group FamilyGroup
     * Crea un grupo familiar nuevo. El creador queda como admin, activo.
     * @bodyParam name string required
     */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'FAILED', 'code' => 400, 'message' => __('Incorrect Params'),
                'data' => $validator->errors()->getMessages(),
            ], 400);
        }

        $userId = $request->user()->id;

        $group = FamilyGroup::create([
            'name' => $request->input('name'),
            'owner_user_id' => $userId,
        ]);

        FamilyGroupMember::create([
            'family_group_id' => $group->id,
            'user_id' => $userId,
            'role' => 'admin',
            'status' => 'active',
        ]);

        return response()->json([
            'status' => 'OK', 'code' => 201, 'message' => 'Grupo familiar creado correctamente.',
            'data' => $group->load('members.user'),
        ], 201);
    }

    /**
     * @group FamilyGroup
     * Invita a un usuario existente por correo. Solo un admin del grupo puede invitar.
     * @bodyParam email string required
     */
    public function invite(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'FAILED', 'code' => 400, 'message' => __('Incorrect Params'),
                'data' => $validator->errors()->getMessages(),
            ], 400);
        }

        $group = FamilyGroup::find($id);
        if (!isset($group->id)) {
            return response()->json(['status' => 'FAILED', 'code' => 404, 'message' => 'Grupo familiar no encontrado.'], 404);
        }
        if ($request->user()->cannot('update', $group)) {
            return response()->json(['status' => 'FAILED', 'code' => 403, 'message' => __('Forbidden') . '.'], 403);
        }

        $invitee = User::where('email', $request->input('email'))->first();
        if (!$invitee) {
            return response()->json([
                'status' => 'FAILED', 'code' => 422,
                'message' => 'No existe ningún usuario registrado con ese correo.',
            ], 422);
        }

        if ($invitee->id === $request->user()->id) {
            return response()->json(['status' => 'FAILED', 'code' => 422, 'message' => 'No podés invitarte a vos mismo.'], 422);
        }

        $existing = FamilyGroupMember::where('family_group_id', $group->id)
            ->where('user_id', $invitee->id)->first();
        if ($existing) {
            return response()->json([
                'status' => 'FAILED', 'code' => 422,
                'message' => $existing->status === 'active' ? 'Ese usuario ya es miembro del grupo.' : 'Ya hay una invitación pendiente para ese correo.',
            ], 422);
        }

        $member = FamilyGroupMember::create([
            'family_group_id' => $group->id,
            'user_id' => $invitee->id,
            'role' => 'member',
            'status' => 'invited',
            'invited_by_user_id' => $request->user()->id,
        ]);

        return response()->json([
            'status' => 'OK', 'code' => 201, 'message' => 'Invitación enviada.',
            'data' => $member->load('user'),
        ], 201);
    }

    /**
     * @group FamilyGroup
     * Acepta una invitación pendiente (solo el propio invitado).
     */
    public function accept(Request $request, $id)
    {
        return $this->respondToInvite($request, $id, 'active');
    }

    /**
     * @group FamilyGroup
     * Rechaza una invitación pendiente (solo el propio invitado) — borra la fila.
     */
    public function decline(Request $request, $id)
    {
        $member = FamilyGroupMember::where('family_group_id', $id)
            ->where('user_id', $request->user()->id)
            ->where('status', 'invited')
            ->first();

        if (!$member) {
            return response()->json(['status' => 'FAILED', 'code' => 404, 'message' => 'No tenés una invitación pendiente para este grupo.'], 404);
        }

        $member->delete();

        return response()->json(['status' => 'OK', 'code' => 200, 'message' => 'Invitación rechazada.'], 200);
    }

    private function respondToInvite(Request $request, $groupId, string $newStatus)
    {
        $member = FamilyGroupMember::where('family_group_id', $groupId)
            ->where('user_id', $request->user()->id)
            ->where('status', 'invited')
            ->first();

        if (!$member) {
            return response()->json(['status' => 'FAILED', 'code' => 404, 'message' => 'No tenés una invitación pendiente para este grupo.'], 404);
        }

        $member->status = $newStatus;
        $member->save();

        return response()->json([
            'status' => 'OK', 'code' => 200, 'message' => 'Ahora sos parte del grupo familiar.',
            'data' => $member->load('user'),
        ], 200);
    }

    /**
     * @group FamilyGroup
     * Salir del grupo (uno mismo) o remover a otro miembro (solo admin).
     * @urlParam id integer required ID del grupo.
     * @urlParam userId integer required ID del usuario a quitar.
     */
    public function removeMember(Request $request, $id, $userId)
    {
        $group = FamilyGroup::find($id);
        if (!isset($group->id)) {
            return response()->json(['status' => 'FAILED', 'code' => 404, 'message' => 'Grupo familiar no encontrado.'], 404);
        }

        $authUserId = $request->user()->id;
        $isSelf = ((int) $userId) === $authUserId;

        if (!$isSelf && $request->user()->cannot('update', $group)) {
            return response()->json(['status' => 'FAILED', 'code' => 403, 'message' => __('Forbidden') . '.'], 403);
        }

        $member = FamilyGroupMember::where('family_group_id', $group->id)
            ->where('user_id', (int) $userId)->first();
        if (!$member) {
            return response()->json(['status' => 'FAILED', 'code' => 404, 'message' => 'Ese usuario no es miembro del grupo.'], 404);
        }

        if ($member->role === 'admin' && $group->owner_user_id === $member->user_id && !$isSelf) {
            return response()->json(['status' => 'FAILED', 'code' => 422, 'message' => 'No se puede remover al dueño del grupo.'], 422);
        }

        $member->delete();

        return response()->json(['status' => 'OK', 'code' => 200, 'message' => $isSelf ? 'Saliste del grupo familiar.' : 'Miembro removido correctamente.'], 200);
    }
}
