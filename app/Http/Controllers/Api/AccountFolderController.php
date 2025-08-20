<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\AccountFolder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class AccountFolderController extends Controller
{
    /**
     * @group AccountFolder
     * Create a new account folder
     * @bodyParam name string required The name of the folder. Example: My Folder
     * @bodyParam parent_id integer nullable Parent folder ID. Example: 1
     */
    public function store(Request $request)
    {
        // Ensure user is authenticated
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 401,
                'message' => __('Unauthenticated'),
            ], 401);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'parent_id' => 'nullable|exists:account_folders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 400,
                'message' => __('Incorrect Params'),
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $user = $request->user();
            $data = [
                'name' => $request->input('name'),
                'user_id' => $user->id,
                'parent_id' => $request->input('parent_id'),
            ];
            $folder = AccountFolder::create($data);

            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'data' => [
                    'id' => $folder->id,
                    'parent_id' => $folder->parent_id,
                ],
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            return response()->json([
                'status' => 'FAILED',
                'code' => 500,
                'message' => __('An error has occurred'),
            ], 500);
        }
    }
}
