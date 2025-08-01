<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Entities\Item;

use App\Models\Entities\Repositories\ItemRepository;

class ItemController extends Controller
{
    protected $itemRepo;

    public function __construct(ItemRepository $itemRepo)
    {
        $this->itemRepo = $itemRepo;
    }

    public function all()
    {
        $items = $this->itemRepo->all()->map(function($item) {
            $arr = $item->toArray();
            $arr['category_id'] = $item->item_category_id;
            $arr['price'] = $item->last_price;
            return $arr;
        });
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Items retrieved successfully'),
            'data' => $items,
        ], 200);
    }

    public function allActive()
    {
        $items = $this->itemRepo->all()->where('active', 1)->map(function($item) {
            $arr = $item->toArray();
            $arr['category_id'] = $item->item_category_id;
            $arr['price'] = $item->last_price;
            return $arr;
        });
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Active items retrieved successfully'),
            'data' => $items,
        ], 200);
    }

    public function withTrashed()
    {
        $items = Item::withTrashed()->get()->map(function($item) {
            $arr = $item->toArray();
            $arr['category_id'] = $item->item_category_id;
            $arr['price'] = $item->last_price;
            return $arr;
        });
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Items with trashed retrieved successfully'),
            'data' => $items,
        ], 200);
    }

    public function find($id)
    {
        $item = $this->itemRepo->all()->where('id', $id)->first();
        if (!$item) {
            abort(404);
        }
        $arr = $item->toArray();
        $arr['category_id'] = $item->item_category_id;
        $arr['price'] = $item->last_price;
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Item retrieved successfully'),
            'data' => $arr,
        ], 200);
    }

    public function save(Request $request)
    {
        $data = $request->all();
        if (isset($data['category_id'])) {
            $data['item_category_id'] = $data['category_id'];
        }
        if (isset($data['price'])) {
            $data['last_price'] = $data['price'];
        }
        $item = Item::create($data);
        $arr = $item->toArray();
        $arr['category_id'] = $item->item_category_id;
        $arr['price'] = $item->last_price;
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Item saved correctly'),
            'data' => $arr,
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $item = $this->itemRepo->all()->where('id', $id)->first();
        if (!$item) {
            abort(404);
        }
        $data = $request->all();
        if (isset($data['category_id'])) {
            $data['item_category_id'] = $data['category_id'];
        }
        if (isset($data['price'])) {
            $data['last_price'] = $data['price'];
        }
        $item->update($data);
        $arr = $item->toArray();
        $arr['category_id'] = $item->item_category_id;
        $arr['price'] = $item->last_price;
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Item updated successfully'),
            'data' => $arr,
        ], 200);
    }

    public function delete($id)
    {
        $item = $this->itemRepo->all()->where('id', $id)->first();
        if (!$item) {
            abort(404);
        }
        $item->delete();
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Item deleted successfully'),
            'data' => null,
        ], 200);
    }

    public function change_status($id)
    {
        $item = $this->itemRepo->all()->where('id', $id)->first();
        if (!$item) {
            abort(404);
        }
        $item->active = !$item->active;
        $item->save();
        $arr = $item->toArray();
        $arr['category_id'] = $item->item_category_id;
        $arr['price'] = $item->last_price;
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Status Item updated'),
            'data' => $arr,
        ], 200);
    }
}
