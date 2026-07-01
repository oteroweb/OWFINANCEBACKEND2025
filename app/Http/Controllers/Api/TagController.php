<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    /**
     * @group Tag
     * Get
     *
     * index — returns system tags + authenticated user's tags
     * Accepts optional ?type=system|user to filter
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();
            $type = $request->query('type');

            $query = Tag::query()->where('active', 1);

            if ($type === 'system') {
                $query->where('type', 'system');
            } elseif ($type === 'user') {
                $query->where('type', 'user')->where('user_id', $user->id);
            } else {
                // Default: system tags + user's own tags
                $query->forUser($user->id);
            }

            $tags = $query->orderBy('type')->orderBy('name')->get();

            return response()->json([
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Tags obtained correctly'),
                'data'    => $tags,
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            return response()->json([
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ], 500);
        }
    }

    /**
     * @group Tag
     * Post
     *
     * save — creates a user tag (type='user')
     */
    public function save(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:100',
            'slug'  => [
                'required',
                'string',
                'max:100',
                // slug must be unique per user (system slugs are globally unique via DB constraint)
                function ($attribute, $value, $fail) use ($user) {
                    $exists = Tag::where('slug', $value)
                        ->where(function ($q) use ($user) {
                            $q->where('type', 'system')
                              ->orWhere('user_id', $user->id);
                        })
                        ->whereNull('deleted_at')
                        ->exists();
                    if ($exists) {
                        $fail(__('The slug already exists for this user or as a system tag.'));
                    }
                },
            ],
            'color' => 'nullable|string|max:50',
            'icon'  => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => 'FAILED',
                'code'    => 400,
                'message' => __('Incorrect Params'),
                'data'    => $validator->errors()->getMessages(),
            ], 400);
        }

        try {
            $tag = Tag::create([
                'slug'    => $request->input('slug'),
                'name'    => $request->input('name'),
                'color'   => $request->input('color'),
                'icon'    => $request->input('icon'),
                'type'    => 'user',
                'user_id' => $user->id,
                'active'  => 1,
            ]);

            return response()->json([
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Tag saved correctly'),
                'data'    => $tag,
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            return response()->json([
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ], 500);
        }
    }

    /**
     * @group Tag
     * Delete
     * @urlParam id integer required The ID of the tag. Example: 1
     *
     * delete — only user tags belonging to the authenticated user can be deleted
     */
    public function delete(Request $request, $id)
    {
        try {
            $user = $request->user();

            $tag = Tag::where('id', $id)
                ->where('type', 'user')
                ->where('user_id', $user->id)
                ->first();

            if (!$tag) {
                return response()->json([
                    'status'  => 'FAILED',
                    'code'    => 404,
                    'message' => __('Tag not found or you do not have permission to delete it'),
                ], 200);
            }

            $tag->active = 0;
            $tag->save();
            $tag->delete();

            return response()->json([
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Tag deleted successfully'),
                'data'    => true,
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            return response()->json([
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ], 500);
        }
    }
}
