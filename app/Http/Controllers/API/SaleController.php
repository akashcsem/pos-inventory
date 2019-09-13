<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Sale\Sale;
use App\Model\Sale\SaleDetail;
use App\Model\Sale\SaleReturn;
use App\Model\Sale\SaleReturnDetail;
use App\Model\Stocks\Stock;
use App\Model\Stocks\StockDetail;
use DB;


class SaleController extends Controller
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
    $table = '';
    if ($data) {
      return $data;
    }

    $sales = Sale::select('sales.*')->join('customers', 'customers.id', '=', 'sales.customer_id')
      ->orderBy('customers.name', $order)
      ->with('customer')
      ->with('saleItems')
      ->with('user');

    return response()->JSON($sales->paginate(3));
  }


  /**
   * Store a newly created resource in storage.
   *
   * @param  \Illuminate\Http\Request  $request
   * @return \Illuminate\Http\Response
   */
  public function storeProducts(Request $request)
  {
    $customerInfo = json_decode($request->customerInfo);

    $user_id = auth('api')->user()->id;

    if (!$customerInfo->cashSale) {

      $client = DB::table('customers')->where('name', $customerInfo->customerName)->first();

      $discount = 0;
      $sale = Sale::create([
        'sale_inv_no'   => '124',
        'user_id'      => $user_id,
        'customer_id'  => $client->id,
        'totalQty'     => $customerInfo->totalQuantity,
        'subTotal'     => $customerInfo->grandTotal,
        'discount'     => $discount,
        'grandTotal'   => $customerInfo->grandTotal - $discount,
      ]);
    } else {
      $sale = Sale::create([
        'sale_inv_no'  => '124',
        'user_id'      => $user_id,
        'customer_id'  => 1,
        'name'         => $customerInfo->customerName,
        'mobile'       => $customerInfo->customerMobile,
        'address'      => $customerInfo->customerAddress,
        'totalQty'     => $customerInfo->totalQuantity,
        'grandTotal'   => $customerInfo->grandTotal,
      ]);
    }


    $invoice_number = "sale-inv-" . strval($sale->id + 100000);

    DB::table('sales')
      ->where('sale_inv_no', '124')
      ->update(['sale_inv_no' => $invoice_number]);

    foreach (json_decode($request->shopItems) as $item) {
      SaleDetail::create([
        'sale_inv_no'  => $invoice_number,
        'quantity'     => $item->quantity,
        'product_code' => $item->product_code,
        'price'        => $item->price,
      ]);

      StockDetail::create([
        'product_code' => $item->product_code,
        'description'  => 'Sale',
        'source_id'    => $invoice_number,
        'debit'        => $item->quantity,
      ]);
      $stock = Stock::where('product_code', '=', $item->product_code)->first();
      $stock->quantity -= $item->quantity;
      $stock->save();
    }

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
  public function update(Request $request, Sale $product)
  {
    $this->validate($request, [
      'name'          => 'required',
      'category_id'   => 'required',
      'unit_id'       => 'required',
    ]);

    $product->update($request->all());
    return "success";
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param  int  $id
   * @return \Illuminate\Http\Response
   */
  public function destroy(Sale $product)
  {
    $product->delete();
    return "success";
  }


  // return

  public function returnInvoices()
  {
    $order = 'asc';

    $sale = SaleReturn::join('customers', 'customers.id', '=', 'sale_returns.customer_id')
      ->orderBy('customers.name', $order)
      ->with('customer')
      ->with('SaleReturnItems')
      ->with('user');

    return response()->JSON($sale->paginate(3));
  }
  // get returnable sales product list
  public function returnableProducts($customer)
  {
    $sales = Sale::join('customers', 'customers.id', '=', 'sales.customer_id')
      ->where('customers.name', '=', $customer)
      ->with('saleItems');

    return response()->JSON($sales->get());
  }

  // create sale return
  public function returnProducts(Request $request)
  {
    $returnInfo  = json_decode($request->returnInfo);
    $returnItems = json_decode($request->returnItems);
    $user_id     = auth('api')->user()->id;
    $customer    = DB::table('customers')->where('name', $returnInfo->customer)->first();

    $discount = 0;
    $saleReturn      = SaleReturn::create([
      'sale_rtn_no'  => '124',
      'user_id'      => $user_id,
      'customer_id'  => $customer->id,
      'totalQty'     => $returnInfo->totalQty,
      'discount'     => $discount,
      'grandTotal'   => $returnInfo->grandTotal,
    ]);

    $invoice_number = "sale-rtn-" . strval($saleReturn->id + 100000);

    DB::table('sale_returns')
      ->where('sale_rtn_no', '124')
      ->update(['sale_rtn_no' => $invoice_number]);


    foreach ($returnItems as $item) {
      SaleReturnDetail::create([
        'sale_rtn_no'  => $invoice_number,
        'quantity'     => $item->productQuantity,
        'product_code' => $item->productCode,
        'price'        => $item->productPrice,
      ]);

      StockDetail::create([
        'product_code' => $item->productCode,
        'description'  => 'Sale Return',
        'source_id'    => $invoice_number,
        'credit'       => $item->productQuantity,
      ]);
      $stock = Stock::where('product_code', '=', $item->productCode)->first();
      $stock->quantity += $item->productQuantity;
      $stock->save();
    }

    return "success";
  }

  public function returnDelete($id)
  {
    $saleReturn = SaleReturn::where('sale_rtn_no', '=', $id);
    $saleReturnDetail = SaleReturnDetail::where('sale_rtn_no', '=', $id);

    $saleReturn->delete();
    $saleReturnDetail->delete();
    return "Deleted successful";
  }
}
