<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\Jar;
use App\Services\JarBalanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class JarAdjustmentController extends Controller
{
    protected $balanceService;

    public function __construct(JarBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * @group Jar Adjustments
     * Apply manual adjustment to a jar's balance
     *
     * @urlParam id integer required The ID of the jar. Example: 5
     * @bodyParam amount numeric required Amount to adjust (positive = add, negative = subtract). Example: 160.00
     * @bodyParam description string Optional reason for the adjustment. Example: Compensar diferencia de ingreso
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Ajuste aplicado correctamente",
     *   "data": {
     *     "jar_id": 5,
     *     "jar_name": "Ahorro",
     *     "adjustment": 160.00,
     *     "previous_adjustment": 0.00,
     *     "balance": {
     *       "asignado": 840.00,
     *       "gastado": 300.00,
     *       "ajuste": 160.00,
     *       "balance": 700.00
     *     }
     *   }
     * }
     */
    public function adjust(Request $request, int $id)
    {
        try {
            $user = $request->user();

            // Validar que el cántaro existe y pertenece al usuario
            $jar = Jar::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$jar) {
                return response()->json([
                    'success' => false,
                    'message' => __('Jar not found or does not belong to you')
                ], 404);
            }

            // Validar request
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric',
                'description' => 'nullable|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => __('Validation errors'),
                    'errors' => $validator->errors()
                ], 422);
            }

            $amount = (float) $request->input('amount');
            $description = $request->input('description');

            // Validar que no se intente restar más de lo disponible
            $currentBalance = $this->balanceService->getAvailableBalance($jar);
            $newBalance = $currentBalance + $amount;

            if ($newBalance < 0) {
                return response()->json([
                    'success' => false,
                    'message' => __('The adjustment would result in a negative balance'),
                    'details' => [
                        'current_balance' => $currentBalance,
                        'requested_adjustment' => $amount,
                        'would_result_in' => $newBalance
                    ]
                ], 400);
            }

            // Guardar ajuste anterior para historial
            $previousAdjustment = $jar->adjustment ?? 0;

            // Usar el servicio para aplicar el ajuste
            $adjustmentRecord = $this->balanceService->adjustBalance(
                $jar,
                $amount,
                $description
            );

            // Recargar el jar para obtener el nuevo valor
            $jar->refresh();

            // Obtener breakdown completo del balance
            $balance = $this->balanceService->getDetailedBalance($jar);

            return response()->json([
                'success' => true,
                'message' => __('Adjustment applied successfully'),
                'data' => [
                    'jar_id' => $jar->id,
                    'jar_name' => $jar->name,
                    'adjustment' => $jar->adjustment,
                    'previous_adjustment' => $previousAdjustment,
                    'balance' => [
                        'asignado' => $balance['allocated_amount'],
                        'gastado' => $balance['spent_amount'],
                        'ajuste' => $balance['adjustment'],
                        'balance' => $balance['available_balance'],
                        'porcentaje_utilizado' => round(
                            $balance['allocated_amount'] > 0
                                ? ($balance['spent_amount'] / $balance['allocated_amount']) * 100
                                : 0,
                            2
                        )
                    ],
                    'adjustment_record_id' => $adjustmentRecord->id
                ]
            ], 200);

        } catch (\Exception $ex) {
            Log::error('Error in adjust jar: ' . $ex->getMessage());
            Log::error($ex->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => __('An error occurred while adjusting the jar'),
                'error' => app()->environment('local') ? $ex->getMessage() : null
            ], 500);
        }
    }

    /**
     * @group Jar Adjustments
     * Reset adjustment for a jar (set to 0)
     *
     * @urlParam id integer required The ID of the jar. Example: 5
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Ajuste reseteado correctamente",
     *   "data": {
     *     "jar_id": 5,
     *     "jar_name": "Ahorro",
     *     "adjustment": 0,
     *     "previous_adjustment": 160.00,
     *     "balance": {
     *       "asignado": 840.00,
     *       "gastado": 300.00,
     *       "ajuste": 0,
     *       "balance": 540.00
     *     }
     *   }
     * }
     */
    public function resetAdjustment(Request $request, int $id)
    {
        try {
            $user = $request->user();

            $jar = Jar::where('id', $id)
                ->where('user_id', $user->id)
                ->first();

            if (!$jar) {
                return response()->json([
                    'success' => false,
                    'message' => __('Jar not found or does not belong to you')
                ], 404);
            }

            $previousAdjustment = $jar->adjustment ?? 0;

            // Calcular el monto necesario para resetear (inverso del ajuste actual)
            $resetAmount = -$previousAdjustment;

            // Aplicar el ajuste de reseteo
            if ($resetAmount != 0) {
                $this->balanceService->adjustBalance(
                    $jar,
                    $resetAmount,
                    'Reseteo de ajuste manual'
                );
            }

            $jar->refresh();
            $balance = $this->balanceService->getDetailedBalance($jar);

            return response()->json([
                'success' => true,
                'message' => __('Adjustment reset successfully'),
                'data' => [
                    'jar_id' => $jar->id,
                    'jar_name' => $jar->name,
                    'adjustment' => 0,
                    'previous_adjustment' => $previousAdjustment,
                    'balance' => [
                        'asignado' => $balance['allocated_amount'],
                        'gastado' => $balance['spent_amount'],
                        'ajuste' => $balance['adjustment'],
                        'balance' => $balance['available_balance']
                    ]
                ]
            ], 200);

        } catch (\Exception $ex) {
            Log::error('Error in reset jar adjustment: ' . $ex->getMessage());
            Log::error($ex->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => __('An error occurred while resetting the adjustment'),
                'error' => app()->environment('local') ? $ex->getMessage() : null
            ], 500);
        }
    }
}
