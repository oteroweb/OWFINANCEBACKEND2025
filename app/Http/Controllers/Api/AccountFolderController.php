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
     * List folders of current user
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'FAILED', 'code' => 401, 'message' => __('Unauthenticated')], 401);
        }
        $folders = AccountFolder::where('user_id', $user->id)
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get(['id','name','parent_id']);
        return response()->json(['status' => 'OK', 'code' => 200, 'data' => $folders], 200);
    }

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
            // Validate parent belongs to current user when provided
            if (!empty($data['parent_id'])) {
                $parent = AccountFolder::where('id', $data['parent_id'])->where('user_id', $user->id)->first();
                if (!$parent) {
                    return response()->json([
                        'status' => 'FAILED',
                        'code' => 400,
                        'message' => __('Incorrect Params'),
                        'errors' => ['parent_id' => [__('Invalid parent for this user')]],
                    ], 400);
                }
            }
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

    /**
     * @group AccountFolder
     * Rename a folder
     */
    public function rename(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'FAILED', 'code' => 401, 'message' => __('Unauthenticated')], 401);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'code' => 400, 'message' => __('Incorrect Params'), 'errors' => $validator->errors()], 400);
        }
        $folder = AccountFolder::where('id', $id)->where('user_id', $user->id)->first();
        if (!$folder) {
            return response()->json(['status' => 'FAILED', 'code' => 404, 'message' => __('Folder not found')], 404);
        }
        $folder->update(['name' => $request->input('name')]);
        return response()->json(['status' => 'OK', 'code' => 200, 'data' => ['id' => (int)$id, 'name' => $folder->name]], 200);
    }

    /**
     * @group AccountFolder
     * Delete a folder (cascades to children by FK)
     */
    public function destroy(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'FAILED', 'code' => 401, 'message' => __('Unauthenticated')], 401);
        }
        $folder = AccountFolder::where('id', $id)->where('user_id', $user->id)->first();
        if (!$folder) {
            return response()->json(['status' => 'FAILED', 'code' => 404, 'message' => __('Folder not found')], 404);
        }
        $folder->delete();
        return response()->json(['status' => 'OK', 'code' => 200], 200);
    }

    /**
     * @group AccountFolder
     * Move a folder within the tree (validates cycles)
     * @bodyParam parent_id integer|null New parent folder ID
     */
    public function move(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'FAILED', 'code' => 401, 'message' => __('Unauthenticated')], 401);
        }
        $validator = Validator::make($request->all(), [
            'parent_id' => 'nullable|exists:account_folders,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'code' => 400, 'message' => __('Incorrect Params'), 'errors' => $validator->errors()], 400);
        }
        $folder = AccountFolder::where('id', $id)->where('user_id', $user->id)->first();
        if (!$folder) {
            return response()->json(['status' => 'FAILED', 'code' => 404, 'message' => __('Folder not found')], 404);
        }
        $newParentId = $request->input('parent_id');
        // Parent must be user's folder (if provided)
        if (!empty($newParentId)) {
            $parent = AccountFolder::where('id', $newParentId)->where('user_id', $user->id)->first();
            if (!$parent) {
                return response()->json(['status' => 'FAILED', 'code' => 400, 'message' => __('Invalid parent for this user')], 400);
            }
            // Prevent cycles: new parent cannot be itself or any descendant of folder
            if ((int)$newParentId === (int)$folder->id) {
                return response()->json(['status' => 'FAILED', 'code' => 400, 'message' => __('A folder cannot be its own parent')], 400);
            }
            // Collect descendants
            $desc = self::collectDescendants($folder);
            if (in_array((int)$newParentId, $desc, true)) {
                return response()->json(['status' => 'FAILED', 'code' => 400, 'message' => __('Cannot move a folder inside its descendant')], 400);
            }
        }
        $folder->update(['parent_id' => $newParentId]);
        return response()->json(['status' => 'OK', 'code' => 200, 'data' => ['id' => (int)$folder->id, 'parent_id' => $folder->parent_id]], 200);
    }

    private static function collectDescendants(AccountFolder $folder): array
    {
        $ids = [];
        $stack = [$folder->id];
        while (!empty($stack)) {
            $current = array_pop($stack);
            $children = AccountFolder::where('parent_id', $current)->pluck('id')->all();
            foreach ($children as $cid) {
                $ids[] = (int)$cid;
                $stack[] = $cid;
            }
        }
        return $ids;
    }

    /**
     * @group AccountFolder
     * Get folders-only tree for current user
     */
    public function tree(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'FAILED', 'code' => 401, 'message' => __('Unauthenticated')], 401);
        }
        $folders = AccountFolder::where('user_id', $user->id)->get(['id','name as label','parent_id']);
        $map = [];
        foreach ($folders as $f) {
            $map[$f->id] = ['id' => $f->id, 'label' => $f->label, 'type' => 'folder', 'children' => []];
        }
        $roots = [];
        foreach ($folders as $f) {
            if ($f->parent_id && isset($map[$f->parent_id])) {
                $map[$f->parent_id]['children'][] =& $map[$f->id];
            } else {
                $roots[] =& $map[$f->id];
            }
        }
        return response()->json(['status' => 'OK', 'code' => 200, 'data' => ['nodes' => $roots]], 200);
    }
}
