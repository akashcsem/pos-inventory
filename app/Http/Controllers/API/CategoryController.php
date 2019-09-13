<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Others\Category;

class CategoryController extends Controller
{
  public function index()
  {
    return Category::latest()->paginate(5);
  }
  public function category_list()
  {
    return Category::paginate(255);
  }

  public function store(Request $request)
  {
    // dd($request->all());
    // validation
    $this->validate($request, [
      'name'         => 'required|string|max:255|min:2',
    ]);
    // insert data
    return Category::create([
      'name'      => $request['name'],
    ]);
  }


  public function show($id)
  {
    // display single item detail
  }

  public function update(Request $request, $id)
  {
    // update data
    $category = Category::findOrFAil($id);
    $this->validate($request, [
      'name'      => 'required|string|max:255|min:2',
    ]);
    $category->update($request->all());
    return ['message' => 'Getting id is: ' . $id];
  }

  public function destroy(Category $category)
  {
    $category->delete();
    return ['message' => 'Category deleted successfully'];
  }
}
