<?php

namespace App\Http\Controllers\API\Sale;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Others\Customer;
use App\Model\Others\CustomerDetail;
use App\Model\Sale\Sale;
use App\Model\Sale\SaleDetail;
use App\Model\Stocks\Stock;
use App\Model\Stocks\StockDetail;

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

        return response()->JSON($sales->paginate(10));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $customerInfo = json_decode($request->customerInfo);
        $discount = 0;

        $user_id = auth('api')->user()->id;
        $sale = Sale::create([
            'sale_inv_no'  => '124',
            'user_id'      => $user_id,
            'totalQty'     => $customerInfo->totalQuantity,
            'subTotal'     => $customerInfo->grandTotal,
            'discount'     => $discount,
            'grandTotal'   => $customerInfo->grandTotal - $discount,
        ]);

        if ($customerInfo->cashSale) {
            $sale->name     = $customerInfo->customerName;
            $sale->mobile   = $customerInfo->customerMobile;
            $sale->address  = $customerInfo->customerAddress;
            $sale->save();
        } else {
            $sale->customer_id  = Customer::where('name', '=', $customerInfo->customerName)->first()->id;
            $sale->save();
        }

        $invoice_number = "sale-inv-" . strval($sale->id + 100000);
        $sale->sale_inv_no  = $invoice_number;
        $sale->save();

        CustomerDetail::create([
            'supplier_id'  => Customer::where('name', '=', $customerInfo->name)->first()->id,
            'debit'        => $customerInfo->grandTotal - $discount,
            'description'  => "Sale",
            'source_id'    => $invoice_number,
        ]);

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
        return "Success";
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
    public function destroy($sale_inv_no)
    {
        $porducts = SaleDetail::where('sale_inv_no', '=', $sale_inv_no)->get();

        foreach ($porducts as $porduct) {
            $stock = Stock::where('product_code', '=', $porduct->product_code)->first();
            $stock->quantity += $porduct->quantity;
            $stock->save();
        }

        StockDetail::where('source_id', '=', $sale_inv_no)->delete();
        SaleDetail::where('sale_inv_no', '=', $sale_inv_no)->delete();
        CustomerDetail::where('source_id', '=', $sale_inv_no)->delete();
        Sale::where('sale_inv_no', '=', $sale_inv_no)->delete();
    }
}
