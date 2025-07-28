<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repositories\CategoryRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

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
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
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

    public function custom_message()
    {
        return [
            'name.required' => __('The name is required'),
        ];
    }
}
