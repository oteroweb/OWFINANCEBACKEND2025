<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entities\ItemCategory;
use App\Models\Entities\Repositories\ItemCategoryRepository;

class ItemCategoryController extends Controller
{
    protected $repo;

    public function __construct(ItemCategoryRepository $repo)
    {
        $this->repo = $repo;
    }

    public function all()
    {
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $this->repo->all()
        ]);
    }
    public function allActive()
    {
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $this->repo->allActive()
        ]);
    }
    public function withTrashed()
    {
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $this->repo->withTrashed()
        ]);
    }
    public function find($id)
    {
        $itemCategory = $this->repo->find($id);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $itemCategory
        ]);
    }
    public function save(Request $request)
    {
        $data = $request->validate([
            'name' => 'required',
        ]);
        $data['active'] = 1;
        $itemCategory = $this->repo->create($data);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $itemCategory
        ]);
    }
    public function update(Request $request, $id)
    {
        $itemCategory = $this->repo->find($id);
        $data = $request->only(['name']);
        $itemCategory = $this->repo->update($itemCategory, $data);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => $itemCategory
        ]);
    }
    public function delete($id)
    {
        $itemCategory = $this->repo->find($id);
        $this->repo->delete($itemCategory);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => '',
            'data' => null
        ]);
    }
    public function change_status($id)
    {
        $itemCategory = $this->repo->find($id);
        $itemCategory = $this->repo->changeStatus($itemCategory);
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Status Item Category updated'),
            'data' => $itemCategory
        ]);
    }
}
