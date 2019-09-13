<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Others\SupplierDetail;
use App\Model\Purchase\Purchase;
use App\Model\Purchase\PurchaseDetail;
use App\Model\Purchase\PurchaseReturnDetail;
use App\Model\Purchase\PurchaseReturn;
use App\Model\Stocks\StockDetail;
use App\Model\Stocks\Stock;
use DB;

class PurchaseController extends Controller
{
  public function __construct()
  {
    $this->middleware('auth:api');
  }
  /**
   * Display a listing of the resource.
   *
   * @return \Illuminate\Http\Response
   */
  public function index($data = null)
  {
    $order = 'asc';

    $purchases = Purchase::join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
      ->orderBy('suppliers.name', $order)
      ->with('supplier')
      ->with('purchaseItems')
      ->with('user');

    return response()->JSON($purchases->paginate(3));
  }


  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function storeProducts(Request $request, $supplier)
  {
    $totalQuantity = 0;
    $totalAmount   = 0;

    foreach (json_decode($request->shopItems) as $item) {
      $totalQuantity += $item->quantity;
      $totalAmount   += $item->quantity * $item->price;

      PurchaseDetail::create([
        'pur_inv_no'   => '124',
        'quantity'     => $item->quantity,
        'product_code' => $item->product_code,
        'price'        => $item->price,
      ]);

      StockDetail::create([
        'product_code' => $item->product_code,
        'description'  => 'Purchase',
        'source_id'    => '124',
        'credit'       => $item->quantity,
      ]);
      $stock = Stock::where('product_code', '=', $item->product_code)->first();
      $stock->quantity += $item->quantity;
      $stock->save();
    }



    $user_id = auth('api')->user()->id;
    $supp    = DB::table('suppliers')->where('name', $supplier)->first();

    $discount = 0;

    Purchase::create([
      'pur_inv_no'   => '124',
      'user_id'      => $user_id,
      'supplier_id'  => $supp->id,
      'totalQty'     => $totalQuantity,
      'subTotal'     => $totalAmount,
      'discount'     => $discount,
      'grandTotal'   => $totalAmount - $discount,
    ]);



    // set formated purchase invoice number
    $lastRecord = DB::table('purchases')->where('pur_inv_no', '124')->first();

    $invoice_number = "pur-inv-" . strval($lastRecord->id + 100000);

    SupplierDetail::create([
      'supplier_id'  => $supp->id,
      'description'  => 'Purchae',
      'source_id'    => $invoice_number,
      'debit'        => $totalAmount - $discount,
    ]);
    DB::table('purchases')
      ->where('pur_inv_no', '124')
      ->update(['pur_inv_no' => $invoice_number]);

    DB::table('purchase_details')
      ->where('pur_inv_no', '124')
      ->update(['pur_inv_no' => $invoice_number]);

    DB::table('stock_details')
      ->where('source_id', '124')
      ->update(['source_id' => $invoice_number]);
    return "success";
  }

  /**
   * Display the specified resource.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function show($id)
  {
    //
  }

  /**
   * Update the specified resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function update(Request $request, Purchase $product)
  {
    // $this->validate($request, [
    //   'name'          => 'required',
    //   'category_id'   => 'required',
    //   'unit_id'       => 'required',
    // ]);

    // $product->update($request->all());
    return "success";
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy(Purchase $product)
  {
    // $product->delete();
    return "success";
  }



  // return product


  public function returnInvoices()
  {

    // return "success";
    $order = 'asc';
    // if ($data) {
    //   return $data;
    // }

    $purchases = PurchaseReturn::join('suppliers', 'suppliers.id', '=', 'purchase_returns.supplier_id')
      ->orderBy('suppliers.name', $order)
      ->with('supplier')
      ->with('purchaseReturnItems')
      ->with('user');

    return response()->JSON($purchases->paginate(3));
  }


  // get returnable purchases product list
  public function returnableProducts($supplier)
  {
    $purchases = Purchase::join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
      ->where('suppliers.name', '=', $supplier)
      ->with('purchaseItems');

    return response()->JSON($purchases->get());
  }


  // create purchase return
  public function returnProducts(Request $request)
  {

    $returnInfo  = json_decode($request->returnInfo);
    $returnItems = json_decode($request->returnItems);
    $user_id     = auth('api')->user()->id;
    $supp        = DB::table('suppliers')->where('name', $returnInfo->supplier)->first();

    $discount = 0;
    $purchaseReturn  = PurchaseReturn::create([
      'pur_rtn_no'   => '124',
      'user_id'      => $user_id,
      'supplier_id'  => $supp->id,
      'totalQty'     => $returnInfo->totalQty,
      'discount'     => $discount,
      'grandTotal'   => $returnInfo->grandTotal,
    ]);



    // set formated purchase invoice number
    // $lastRecord = DB::table('purchases')->where('pur_rtn_no', '124')->first();

    $invoice_number = "pur-rtn-" . strval($purchaseReturn->id + 100000);

    SupplierDetail::create([
      'supplier_id'  => $supp->id,
      'description'  => 'Purchae',
      'source_id'    => $invoice_number,
      'credit'       => $returnInfo->grandTotal,
    ]);

    DB::table('purchase_returns')
      ->where('pur_rtn_no', '124')
      ->update(['pur_rtn_no' => $invoice_number]);


    foreach ($returnItems as $item) {
      PurchaseReturnDetail::create([
        'pur_rtn_no'   => $invoice_number,
        'quantity'     => $item->productQuantity,
        'product_code' => $item->productCode,
        'price'        => $item->productPrice,
      ]);

      StockDetail::create([
        'product_code' => $item->productCode,
        'description'  => 'Purchase Return',
        'source_id'    => $invoice_number,
        'debit'        => $item->productQuantity,
      ]);
      $stock = Stock::where('product_code', '=', $item->productCode)->first();
      $stock->quantity -= $item->productQuantity;
      $stock->save();
    }

    return "success";
  }

  public function returnDelete($id)
  {
    $purchaseReturn = PurchaseReturn::where('pur_rtn_no', '=', $id);
    $purchaseReturnDetail = PurchaseReturnDetail::where('pur_rtn_no', '=', $id);

    // return $purchaseReturn;
    $purchaseReturn->delete();
    $purchaseReturnDetail->delete();
    return "Deleted successful";
  }
}
