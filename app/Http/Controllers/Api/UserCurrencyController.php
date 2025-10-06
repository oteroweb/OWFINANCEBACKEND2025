<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Entities\UserCurrency;

class UserCurrencyController extends Controller
{
    public function index(Request $request)
    {
        $query = UserCurrency::query();
        if ($request->filled('user_id')) { $query->where('user_id', $request->input('user_id')); }
        if ($request->filled('currency_id')) { $query->where('currency_id', $request->input('currency_id')); }
        if ($request->filled('is_current')) { $query->where('is_current', filter_var($request->input('is_current'), FILTER_VALIDATE_BOOLEAN)); }
        return response()->json(['status'=>'OK','code'=>200,'data'=>$query->paginate($request->input('per_page', 15))]);
    }

    public function store(Request $request)
    {
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
        $record->save();
        return response()->json(['status'=>'OK','code'=>200,'message'=>__('Saved'),'data'=>$record]);
    }

    public function update(Request $request, $id)
    {
        $record = UserCurrency::findOrFail($id);
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
        // No desmarcamos otros registros; permitimos múltiples is_current=true si el usuario así lo define.
        return response()->json(['status'=>'OK','code'=>200,'message'=>__('Updated'),'data'=>$record]);
    }

    public function destroy($id)
    {
        $record = UserCurrency::findOrFail($id);
        $record->delete();
        return response()->json(['status'=>'OK','code'=>200,'message'=>__('Deleted')]);
    }
}
