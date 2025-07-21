<?php

namespace App\Http\Controllers;

use App\Models\Entities\Rate;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group Rates
 *
 * APIs for managing rates
 */
class RateController extends Controller
{
    /**
     * Display a listing of the rates.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return response()->json(Rate::all());
    }

    /**
     * Store a newly created rate in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $rate = Rate::create($request->only(['name', 'date', 'active']));
        return response()->json($rate, 201);
    }

    /**
     * Display the specified rate.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $rate = Rate::findOrFail($id);
        return response()->json($rate);
    }

    /**
     * Update the specified rate in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $rate = Rate::findOrFail($id);
        $rate->update($request->only(['name', 'date', 'active']));
        return response()->json($rate);
    }

    /**
     * Remove the specified rate from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $rate = Rate::findOrFail($id);
        $rate->delete();
        return response()->json(null, 204);
    }
}
