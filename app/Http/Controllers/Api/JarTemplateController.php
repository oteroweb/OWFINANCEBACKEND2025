<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\JarTemplate;
use App\Models\Entities\JarTemplateJar;
use App\Models\Entities\Jar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JarTemplateController extends Controller
{
    /**
     * @group Jar Templates
     * List available templates
     */
    public function index(Request $request)
    {
        $templates = JarTemplate::with('jars')->where('active', 1)->get();
        return response()->json(['status'=>'OK','code'=>200,'data'=>$templates]);
    }

    /**
     * @group Jar Templates
     * Apply a template to the current user
     * @bodyParam template_slug string required The slug of template to apply.
     */
    public function apply(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['status'=>'FAILED','code'=>401,'message'=>__('Unauthenticated')], 401);
        }
        $validator = Validator::make($request->all(), [
            'template_slug'   => 'required|string|exists:jar_templates,slug',
            'with_categories' => 'sometimes|boolean',
        ]);
        if ($validator->fails()) {
            return response()->json(['status'=>'FAILED','code'=>400,'message'=>__('Incorrect Params'),'data'=>$validator->errors()->getMessages()], 400);
        }

        $userId = $request->user()->id;
        $template = JarTemplate::where('slug', $request->input('template_slug'))
            ->with([
                'jars' => function ($q) { $q->orderBy('sort_order'); },
                'jars.categories',
                'jars.baseCategories',
                'jars.categoryTemplates',
                'jars.baseCategoryTemplates',
            ])
            ->firstOrFail();

        // Validate percent sum
        $percentSumNew = 0;
        foreach ($template->jars as $tj) {
            if ($tj->type === 'percent') { $percentSumNew += (float)($tj->percent ?? 0); }
        }
        $withCategories = (bool) $request->boolean('with_categories', false);
        $created = [];
        foreach ($template->jars as $tj) {
            $jar = Jar::create([
                'user_id' => $userId,
                'name' => $tj->name,
                'type' => $tj->type,
                'percent' => $tj->type === 'percent' ? ($tj->percent ?? 0) : null,
                'fixed_amount' => $tj->type === 'fixed' ? ($tj->fixed_amount ?? 0) : null,
                'base_scope' => $tj->base_scope ?? 'all_income',
                'color' => $tj->color,
                'sort_order' => $tj->sort_order ?? 0,
                'active' => 1,
            ]);
            // Link categories
            if ($withCategories) {
                $createFromTpls = function($tpls) use ($userId) {
                    $ids = [];
                    $createdBySlug = [];
                    foreach ($tpls as $tpl) {
                        $parentId = null;
                        if (!empty($tpl->parent_slug) && isset($createdBySlug[$tpl->parent_slug])) {
                            $parentId = $createdBySlug[$tpl->parent_slug];
                        }
                        $cat = \App\Models\Entities\Category::firstOrCreate([
                            'user_id' => $userId,
                            'name'    => $tpl->slug,
                        ], [
                            'parent_id' => $parentId,
                            'active'    => 1,
                        ]);
                        $createdBySlug[$tpl->slug] = $cat->id;
                        $ids[] = $cat->id;
                    }
                    return $ids;
                };
                $tpls = $tj->categoryTemplates ?? collect();
                if ($tpls->isNotEmpty()) {
                    $ids = $createFromTpls($tpls);
                    if (!empty($ids)) { $jar->categories()->sync($ids); }
                }
                if ($jar->base_scope === 'categories') {
                    $tpls = $tj->baseCategoryTemplates ?? collect();
                    if ($tpls->isNotEmpty()) {
                        $ids = $createFromTpls($tpls);
                        if (!empty($ids)) { $jar->baseCategories()->sync($ids); }
                    }
                }
            } else {
                if (method_exists($tj, 'categories')) {
                    $catIds = $tj->categories()->pluck('categories.id')->toArray();
                    if (!empty($catIds)) { $jar->categories()->sync($catIds); }
                }
                if (method_exists($tj, 'baseCategories') && $jar->base_scope === 'categories') {
                    $baseCatIds = $tj->baseCategories()->pluck('categories.id')->toArray();
                    if (!empty($baseCatIds)) { $jar->baseCategories()->sync($baseCatIds); }
                }
            }
            $created[] = $jar;
        }

        return response()->json(['status'=>'OK','code'=>201,'message'=>__('Template applied'),'data'=>$created], 201);
    }
}
