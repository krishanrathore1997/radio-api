<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Resources\CategoryListResponse;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    //
    public function list()
    {
      $categories = Category::all();

      return response()->json([
        'status' => true,
        'category' => CategoryListResponse::collection($categories)
      ]);
    }

    public function store(CategoryRequest $category)
    {
      Category::create([
        'name' => $category->name
      ]);

        return response()->json([
        'status' => true,
        'message' => 'Category created successfully'
      ]);
    }
    public function delete($id)
    {
      $Category = Category::find($id)->delete();

        return response()->json([
        'status' => true,
        'message' => 'Category deleted successfully'
      ]);
    }
    public function update(Request $request, $id)
    {
      $Category = Category::find($id)->update($request->all());

        return response()->json([
        'status' => true,
        'message' => 'Category updated successfully'
      ]);
    }
}
