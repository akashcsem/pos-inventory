<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Sale\SaleDetail;
use DB;

class SaleDetailController extends Controller
{
  public function index()
  {
    // load page with initial data
    return SaleDetail::latest()->paginate(5);
  }
  public function saleDetail_list()
  {
    // load page with initial data
    return DB::table('sale_details')->paginate(255);
  }

  public function store(Request $request)
  {
    // dd($request->all());
    // validation
    $this->validate($request, [
      'name'               => 'required|string|max:255|min:2',
      "product_code"       => 'required',
      "quantity"           => 'required',
      "delivery_status"    => 'required',
      "delivery_date"      => 'required',
      "remarks"            => 'required',
      "tax"                => 'required',
      "discounts"          => 'required',
    ]);
    // insert data
    return SaleDetail::create([
      "product_code" => $request['product_code'],
      "quantity" => $request['quantity'],
      "delivery_status" => $request['delivery_status'],
      "delivery_date" => $request['delivery_date'],
      "remarks" => $request['remarks'],
      "tax" => $request['tax'],
      "discounts" => $request['discounts'],
    ]);
  }


  public function show($id)
  {
    // display single item detail
  }

  public function update(Request $request, $id)
  {
    // update data
    $saleDetail = SaleDetail::findOrFAil($id);
    $this->validate($request, [
      'name'               => 'required|string|max:255|min:2',
      "product_code"       => 'required',
      "quantity"           => 'required',
      "delivery_status"    => 'required',
      "delivery_date"      => 'required',
      "remarks"            => 'required',
      "tax"                => 'required',
      "discounts"          => 'required',
    ]);
    $saleDetail->update($request->all());
    return ['message' => 'Getting id is: ' . $id];
  }

  public function destroy($id)
  {
    //  delete
    // select id
    $saleDetail = SaleDetail::findOrFail($id);
    // delete user
    $saleDetail->delete();
    // redirect back
    return ['message' => 'SaleDetail deleted successfully'];
  }
}
