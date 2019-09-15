<?php

namespace App\Http\Controllers\API\Sale;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Sale\Sale;
use App\Model\Sale\SaleDetail;
use App\Model\Sale\SaleReturn;
use App\Model\Sale\SaleReturnDetail;
use App\Model\Stocks\Stock;
use App\Model\Stocks\StockDetail;
use DB;

class SaleReturnController extends Controller
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
    public function index()
    {
        $sale = SaleReturn::join('customers', 'customers.id', '=', 'sale_returns.customer_id')
            ->orderBy('customers.name', 'asc')
            ->with('customer')
            ->with('SaleReturnItems')
            ->with('user');

        return response()->JSON($sale->paginate(3));
    }

    public function returnableProducts($customer)
    {
        $products = Sale::join('customers', 'customers.id', '=', 'sales.customer_id')
            ->where('customers.name', '=', $customer)
            ->with('saleItems');

        return response()->JSON($products->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
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
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($sale_rtn_no)
    {
        SaleReturn::where('sale_rtn_no', '=', $sale_rtn_no)->delete();
        SaleReturnDetail::where('sale_rtn_no', '=', $sale_rtn_no)->delete();

        // $saleReturn->delete();
        // $saleReturnDetail->delete();
    }
}
