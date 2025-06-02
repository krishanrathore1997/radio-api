<?php

namespace App\Http\Controllers;

use App\Http\Requests\BrandRequest;
use App\Http\Resources\BrandListResponse;
use App\Models\Brand;
use Illuminate\Http\Request;

class BrandController extends Controller
{
    public function list()
    {
        $brands = Brand::with('media')->get();

        return response()->json([
            'status' => true,
            'brands' => BrandListResponse::collection($brands)
        ]);
    }

  public function store(BrandRequest $request)
{
    $brand = new Brand();
    $brand->name = $request->name;
    $brand->location = $request->location;
    $brand->save(); // Save first to ensure an ID is available

    if ($request->hasFile('logo')) {

    if ($request->hasFile('logo')) {
        $brand->addMedia($request->file('logo'))->toMediaCollection('logo');
    }
    }

    return response()->json([
        'status' => true,
        'message' => 'Brand created successfully'
    ]);
}


    public function update(Request $request, $id)
    {
        $brand = Brand::findOrFail($id);
        $brand->name = $request->name ?? $brand->name;
        $brand->location = $request->location ?? $brand->location;

        if ($request->hasFile('logo')) {
            $brand->clearMediaCollection('logo');
            $brand->addMediaFromRequest('logo')->toMediaCollection('logo');
        }

        $brand->save();

        return response()->json([
            'status' => true,
            'message' => 'Brand updated successfully'
        ]);
    }

    public function delete($id)
    {
        $brand = Brand::findOrFail($id);
        $brand->clearMediaCollection('logo');
        $brand->delete();
        return response()->json([
            'status' => true,
            'message' => 'Brand deleted successfully'
        ]);
    }
}
