<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Others\Unit;
use DB;

class UnitController extends Controller
{
  public function index()
  {
    return Unit::latest()->paginate(5);
  }
  public function unit_list()
  {
    return DB::table('units')->paginate(255);
  }

  public function store(Request $request)
  {
    $this->validate($request, [
      'name'         => 'required|string|max:15',
    ]);
    // insert data
    return Unit::create([
      'name'      => $request['name'],
    ]);
  }


  public function show($id)
  {
    // display single item detail
  }

  public function update(Request $request, Unit $unit)
  {
    $this->validate($request, [
      'name'      => 'required|string|max:255|min:2',
    ]);
    $unit->update($request->all());
    return ['message' => 'Unit updated successfully'];
  }

  public function destroy(Unit $unit)
  {
    $unit->delete();
    return ['message' => 'Unit deleted successfully'];
  }
}
