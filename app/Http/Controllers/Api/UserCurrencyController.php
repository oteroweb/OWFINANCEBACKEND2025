<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Entities\UserCurrency;
use App\Models\Entities\OfficialRate;

class UserCurrencyController extends Controller
{
    /**
     * OWF-337: última tasa oficial (BCV) automática, para prellenar el campo "Tasa oficial
     * (BCV) hoy" del formulario de transacciones — antes ese campo nunca se auto-completaba
     * al crear (solo se restauraba al editar una transacción ya guardada con esa tasa).
     * Fuente: official_rates, poblada por BcvRateFetcher (ver OWF-321).
     */
    public function officialLatest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency_id' => 'required|exists:currencies,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'data'=>$validator->errors()->getMessages()],400);
        }
        $official = OfficialRate::where('currency_id', $request->input('currency_id'))
            ->orderByDesc('fetched_at')
            ->first();
        if (!$official) {
            return response()->json(['status'=>'OK','code'=>200,'data'=>null]);
        }
        return response()->json(['status'=>'OK','code'=>200,'data'=>[
            'rate' => (float) $official->rate,
            'fetched_at' => $official->fetched_at,
            'source' => $official->source,
        ]]);
    }

    /**
     * OWF-346: historial de tasa paralela (propia del usuario) para el picker "Tasa
     * paralelo (actual)" en SmartTransactionModal.vue — antes solo se veía la más reciente
     * (is_current:true), sin forma de reusar una tasa de otro momento pese a que el
     * usuario acumula muchas filas históricas de user_currencies.
     */
    public function history(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency_id' => 'required|exists:currencies,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'data'=>$validator->errors()->getMessages()],400);
        }
        $rows = UserCurrency::where('user_id', $request->user()->id)
            ->where('currency_id', $request->input('currency_id'))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(30)
            ->get(['id', 'current_rate', 'is_official', 'is_current', 'updated_at']);
        return response()->json(['status'=>'OK','code'=>200,'data'=>$rows]);
    }

    /**
     * OWF-346: historial de tasa oficial (BCV) automática para el picker "Tasa oficial
     * (BCV) hoy" — mismo criterio que officialLatest() pero devuelve varias, no solo la
     * última. Fuente: official_rates, poblada por BcvRateFetcher (ver OWF-321).
     */
    public function officialHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'currency_id' => 'required|exists:currencies,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'data'=>$validator->errors()->getMessages()],400);
        }
        $rows = OfficialRate::where('currency_id', $request->input('currency_id'))
            ->orderByDesc('fetched_at')
            ->limit(30)
            ->get(['id', 'rate', 'fetched_at', 'source']);
        return response()->json(['status'=>'OK','code'=>200,'data'=>$rows]);
    }

    public function index(Request $request)
    {
        $auth = $request->user();
        $userId = $request->filled('user_id') ? $request->input('user_id') : ($auth->id ?? null);
        if ($auth && !$auth->isAdmin() && (int)$userId !== (int)$auth->id) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden')], 403);
        }

        $query = UserCurrency::with('currency');
        if ($userId) { $query->where('user_id', $userId); }
        if ($request->filled('currency_id')) { $query->where('currency_id', $request->input('currency_id')); }
        if ($request->filled('is_current')) { $query->where('is_current', filter_var($request->input('is_current'), FILTER_VALIDATE_BOOLEAN)); }
        $pagination = $query->paginate($request->input('per_page', 15));

        // Compact latest current rate per currency (prefer official, then latest)
        $compact = [];
        if ($userId) {
            $currentRates = UserCurrency::with('currency')
                ->where('user_id', $userId)
                ->where('is_current', true)
                ->orderByDesc('is_official')
                ->orderByDesc('updated_at')
                ->get()
                ->groupBy('currency_id')
                ->map(fn($g) => $g->first());
            $compact = collect($currentRates)->values()->map(function ($rec) {
                return [
                    'id' => $rec->id,
                    'currency' => $rec->currency,
                    'current_rate' => (float) ($rec->current_rate ?? 1.0),
                    'is_official' => (bool) ($rec->is_official ?? false),
                    'is_current' => (bool) ($rec->is_current ?? false),
                    'updated_at' => $rec->updated_at,
                ];
            })->all();
        }
        return response()->json([
            'status'=>'OK','code'=>200,
            'data'=>$pagination,
            'rates'=>$compact,
        ]);
    }

    public function store(Request $request)
    {
        $auth = $request->user();
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'currency_id' => 'required|exists:currencies,id',
            'current_rate' => 'required|numeric|min:0',
            'is_current' => 'nullable|boolean',
            'is_official' => 'nullable|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'data'=>$validator->errors()->getMessages()],400);
        }
        $data = $validator->validated();
        if ($auth && !$auth->isAdmin()) { $data['user_id'] = $auth->id; }
        $record = UserCurrency::firstOrCreate([
            'user_id' => $data['user_id'],
            'currency_id' => $data['currency_id'],
            'current_rate' => $data['current_rate'],
        ], [
            'is_current' => (bool)($data['is_current'] ?? false),
            'is_official' => (bool)($data['is_official'] ?? true),
        ]);
        if (array_key_exists('is_current', $data)) { $record->is_current = (bool)$data['is_current']; }
        if (array_key_exists('is_official', $data)) { $record->is_official = (bool)$data['is_official']; }
        // Bug real (reportado por el usuario, tasa paralela mostrando un valor viejo/al azar
        // en vez de la última guardada): a diferencia de UserRateService::applyFromPayment()
        // (usado al guardar una transacción), este endpoint nunca desmarcaba is_current en
        // los demás registros de la misma moneda — permitía que coexistieran varias filas
        // "actuales" a la vez, y cuál ganaba en el frontend (useUserRates.ts) dependía del
        // orden de retorno, no de cuál se guardó último. Mismo criterio que UserRateService.
        if ($record->is_current) {
            UserCurrency::where('user_id', $data['user_id'])
                ->where('currency_id', $data['currency_id'])
                ->where('id', '!=', $record->id)
                ->update(['is_current' => false]);
        }
        $record->save();
        return response()->json(['status'=>'OK','code'=>200,'message'=>__('Saved'),'data'=>$record]);
    }

    public function update(Request $request, $id)
    {
        $record = UserCurrency::findOrFail($id);
        $auth = $request->user();
        if ($auth && !$auth->isAdmin() && (int)$record->user_id !== (int)$auth->id) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden')], 403);
        }
        $validator = Validator::make($request->all(), [
            'current_rate' => 'sometimes|numeric|min:0',
            'is_current' => 'sometimes|boolean',
            'is_official' => 'sometimes|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'data'=>$validator->errors()->getMessages()],400);
        }
        $record->fill($validator->validated());
        $record->save();
        // Mismo fix que store(): si esta fila queda como "actual", ninguna otra fila de la
        // misma moneda debe seguir marcada is_current — evita que quede ambiguo cuál rige.
        if ($record->is_current) {
            UserCurrency::where('user_id', $record->user_id)
                ->where('currency_id', $record->currency_id)
                ->where('id', '!=', $record->id)
                ->update(['is_current' => false]);
        }
        return response()->json(['status'=>'OK','code'=>200,'message'=>__('Updated'),'data'=>$record]);
    }

    public function destroy(Request $request, $id)
    {
        $record = UserCurrency::findOrFail($id);
        $auth = $request->user();
        if ($auth && !$auth->isAdmin() && (int)$record->user_id !== (int)$auth->id) {
            return response()->json(['status'=>'FAILED','code'=>403,'message'=>__('Forbidden')], 403);
        }
        $record->delete();
        return response()->json(['status'=>'OK','code'=>200,'message'=>__('Deleted')]);
    }
}
