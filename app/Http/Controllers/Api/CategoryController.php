<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repositories\CategoryRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Services\CategoryTreeInitializer;

class CategoryController extends Controller
{
    private $categoryRepo;

    public function __construct(CategoryRepo $categoryRepo)
    {
        $this->categoryRepo = $categoryRepo;
    }

    /**
     * @group Category
     * Get all categories
     */
    public function all()
    {
        try {
            $categories = $this->categoryRepo->all();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Category Obtained Correctly'),
                'data'    => $categories,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * @group Category
     * Get all active categories
     */
    public function allActive()
    {
        try {
            $categories = $this->categoryRepo->allActive();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $categories,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * @group Category
     * Get
     * @urlParam id integer required The ID of the category. Example: 1
     */
    public function find($id)
    {
        try {
            $category = $this->categoryRepo->find($id);
            if (isset($category->id)) {
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Category Obtained Correctly'),
                    'data'    => $category,
                ];
                return response()->json($response, 200);
            }
            $response = [
                'status'  => 'FAILED',
                'code'    => 404,
                'message' => __('Not Data with this Category') . '.',
            ];
            return response()->json($response, 404);
        } catch (\Exception $ex) {
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * @group Category
     * Save
     */
    public function save(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
            'parent_id' => 'nullable|exists:categories,id',
        ], $this->custom_message());

        if ($validator->fails()) {
            $response = [
                'status'  => 'FAILED',
                'code'    => 400,
                'message' => __('Incorrect Params'),
                'data'    => $validator->errors()->getMessages(),
            ];
            return response()->json($response, 400);
        }
        try {
            $data = $request->only(['name', 'active', 'date', 'parent_id']);
            // Scope parent_id to current user if provided
            if (!empty($data['parent_id'])) {
                $parent = \App\Models\Entities\Category::where('id', $data['parent_id'])
                    ->where(function($q) use ($user) { $q->whereNull('user_id')->orWhere('user_id', optional($user)->id); })
                    ->first();
                if (!$parent) {
                    return response()->json([
                        'status' => 'FAILED',
                        'code' => 400,
                        'message' => __('Incorrect Params'),
                        'errors' => ['parent_id' => [__('Invalid parent for this user')]],
                    ], 400);
                }
            }
            if ($user) {
                $data['user_id'] = $user->id;
            }
            $category = $this->categoryRepo->store($data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Category saved correctly'),
                'data'    => $category,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * @group Category
     * Update
     * @urlParam id integer required The ID of the category. Example: 1
     */
    public function update(Request $request, $id)
    {
        $category = $this->categoryRepo->find($id);
        if (isset($category->id)) {
            $data = $request->only(['name', 'active', 'date', 'parent_id']);
            // If changing parent, ensure same user scope
            if (!empty($data['parent_id'])) {
                $user = $request->user();
                $parent = \App\Models\Entities\Category::where('id', $data['parent_id'])
                    ->where(function($q) use ($user) { $q->whereNull('user_id')->orWhere('user_id', optional($user)->id); })
                    ->first();
                if (!$parent) {
                    return response()->json([
                        'status' => 'FAILED',
                        'code' => 400,
                        'message' => __('Incorrect Params'),
                        'errors' => ['parent_id' => [__('Invalid parent for this user')]],
                    ], 400);
                }
            }
            $category = $this->categoryRepo->update($category, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Category updated'),
                'data'    => $category,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 404,
            'message' => __('Category does not exist') . '.',
        ];
        return response()->json($response, 404);
    }

    /**
     * @group Category
     * Delete
     * @urlParam id integer required The ID of the category. Example: 1
     */
    public function delete($id)
    {
        try {
            $category = $this->categoryRepo->find($id);
            if ($category) {
                $this->categoryRepo->delete($category);
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Category Deleted Successfully'),
                ];
                return response()->json($response, 200);
            } else {
                $response = [
                    'status'  => 'FAILED',
                    'code'    => 404,
                    'message' => __('Category not Found'),
                ];
                return response()->json($response, 404);
            }
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * @group Category
     * Patch
     * @urlParam id integer required The ID of the category. Example: 1
     */
    public function change_status($id)
    {
        $category = $this->categoryRepo->find($id);
        if (isset($category->active)) {
            $data = ['active' => !$category->active];
            $this->categoryRepo->update($category, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Status Category updated'),
                'data'    => $category,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 404,
            'message' => __('Category does not exist') . '.',
        ];
        return response()->json($response, 404);
    }

    /**
     * @group Category
     * Get categories with trashed
     */
    public function withTrashed()
    {
        try {
            $categories = $this->categoryRepo->withTrashed();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $categories,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }

    /**
     * @group Category
     * Reset categories: remove all personal categories and reseed defaults for current user
     */
    public function reset(Request $request, CategoryTreeInitializer $initializer)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'FAILED', 'code' => 401, 'message' => __('Unauthenticated')], 401);
        }
        try {
            $initializer->resetForUser($user->id);
            // Return freshly built tree
            return $this->tree($request);
        } catch (\Throwable $e) {
            Log::error($e);
            return response()->json(['status' => 'FAILED', 'code' => 500, 'message' => __('An error has occurred')], 500);
        }
    }

    public function custom_message()
    {
        return [
            'name.required' => __('The name is required'),
        ];
    }

    /**
     * @group Category
     * Get category tree for current user
     */
    public function tree(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 401,
                'message' => __('Unauthenticated'),
            ], 401);
        }
        // Load user categories (including globals with null user_id)
        $cats = \App\Models\Entities\Category::where(function($q) use ($user) {
                $q->whereNull('user_id')->orWhere('user_id', $user->id);
            })
            ->orderBy('name')
            ->get(['id','name as label','parent_id']);
        $map = [];
        foreach ($cats as $c) {
            $map[$c->id] = ['id' => $c->id, 'label' => $c->label, 'type' => 'category', 'children' => []];
        }
        $forest = [];
        foreach ($cats as $c) {
            if ($c->parent_id && isset($map[$c->parent_id])) {
                $map[$c->parent_id]['children'][] =& $map[$c->id];
            } else {
                $forest[] =& $map[$c->id];
            }
        }
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => ['nodes' => $forest],
        ], 200);
    }

    /**
     * @group Category
     * Move a category within the tree (validates cycles)
     * @urlParam id integer required The ID of the category
     * @bodyParam parent_id integer|null New parent category ID
     */
    public function move(Request $request, $id)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['status' => 'FAILED', 'code' => 401, 'message' => __('Unauthenticated')], 401);
        }
        $validator = Validator::make($request->all(), [
            'parent_id' => 'nullable|exists:categories,id',
        ]);
        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'code' => 400, 'message' => __('Incorrect Params'), 'errors' => $validator->errors()], 400);
        }
        $category = \App\Models\Entities\Category::where('id', $id)
            ->where(function($q) use ($user) { $q->whereNull('user_id')->orWhere('user_id', $user->id); })
            ->first();
        if (!$category) {
            return response()->json(['status' => 'FAILED', 'code' => 404, 'message' => __('Category not found')], 404);
        }
        $newParentId = $request->input('parent_id');
        if (!empty($newParentId)) {
            $parent = \App\Models\Entities\Category::where('id', $newParentId)
                ->where(function($q) use ($user) { $q->whereNull('user_id')->orWhere('user_id', $user->id); })
                ->first();
            if (!$parent) {
                return response()->json(['status' => 'FAILED', 'code' => 400, 'message' => __('Invalid parent for this user')], 400);
            }
            if ((int)$newParentId === (int)$category->id) {
                return response()->json(['status' => 'FAILED', 'code' => 400, 'message' => __('A category cannot be its own parent')], 400);
            }
            // Prevent cycles
            $desc = self::collectCategoryDescendants($category);
            if (in_array((int)$newParentId, $desc, true)) {
                return response()->json(['status' => 'FAILED', 'code' => 400, 'message' => __('Cannot move a category inside its descendant')], 400);
            }
        }
        $category->update(['parent_id' => $newParentId]);
        return response()->json(['status' => 'OK', 'code' => 200, 'data' => ['id' => (int)$category->id, 'parent_id' => $category->parent_id]], 200);
    }

    private static function collectCategoryDescendants(\App\Models\Entities\Category $category): array
    {
        $ids = [];
        $stack = [$category->id];
        while (!empty($stack)) {
            $current = array_pop($stack);
            $children = \App\Models\Entities\Category::where('parent_id', $current)->pluck('id')->all();
            foreach ($children as $cid) {
                $ids[] = (int)$cid;
                $stack[] = $cid;
            }
        }
        return $ids;
    }
}
