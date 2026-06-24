<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Entities\TransactionType;
use App\Models\Repositories\TransactionTypeRepo;
use Illuminate\Http\Request;

class TransactionTypeController extends Controller
{
    public function __construct(private TransactionTypeRepo $repo) {}

    /**
     * @group Admin — Transaction Types
     * GET /admin/transaction-types
     * List all transaction types (including soft-deleted).
     */
    public function index()
    {
        $types = TransactionType::withTrashed()->orderBy('id')->get();
        return response()->json([
            'status'  => 'OK',
            'code'    => 200,
            'message' => 'Transaction types retrieved.',
            'data'    => $types,
        ]);
    }

    /**
     * @group Admin — Transaction Types
     * POST /admin/transaction-types
     * Create a new transaction type.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:50',
            'slug'        => 'required|string|max:50|unique:transaction_types,slug',
            'description' => 'nullable|string|max:255',
            'active'      => 'sometimes|boolean',
        ]);

        $data['active'] = $request->boolean('active', true);
        $type = $this->repo->store($data);

        return response()->json([
            'status'  => 'OK',
            'code'    => 201,
            'message' => 'Transaction type created.',
            'data'    => $type,
        ], 201);
    }

    /**
     * @group Admin — Transaction Types
     * PUT /admin/transaction-types/{id}
     * Update an existing transaction type.
     */
    public function update(Request $request, int $id)
    {
        $type = $this->repo->find($id);
        if (! $type) {
            return response()->json([
                'status'  => 'ERROR',
                'code'    => 404,
                'message' => 'Transaction type not found.',
            ], 404);
        }

        $data = $request->validate([
            'name'        => 'sometimes|string|max:50',
            'slug'        => 'sometimes|string|max:50|unique:transaction_types,slug,' . $id,
            'description' => 'sometimes|nullable|string|max:255',
            'active'      => 'sometimes|boolean',
        ]);

        if ($request->exists('active')) {
            $data['active'] = $request->boolean('active');
        }

        $type = $this->repo->update($type, $data);

        return response()->json([
            'status'  => 'OK',
            'code'    => 200,
            'message' => 'Transaction type updated.',
            'data'    => $type,
        ]);
    }

    /**
     * @group Admin — Transaction Types
     * DELETE /admin/transaction-types/{id}
     * Soft-delete a transaction type.
     */
    public function destroy(int $id)
    {
        $type = $this->repo->find($id);
        if (! $type) {
            return response()->json([
                'status'  => 'ERROR',
                'code'    => 404,
                'message' => 'Transaction type not found.',
            ], 404);
        }

        $this->repo->update($type, ['active' => 0]);
        $type->delete();

        return response()->json([
            'status'  => 'OK',
            'code'    => 200,
            'message' => 'Transaction type deleted.',
            'data'    => $type,
        ]);
    }
}
