<?php

namespace App\Http\Controllers\API\Sale;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Others\Customer;
use App\Model\Others\CustomerDetail;
use App\Model\Sale\Sale;
use App\Model\Sale\SaleDetail;

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
    public function index()
    {
        $sales = Sale::select('sales.*')->join('customers', 'customers.id', '=', 'sales.customer_id')
            ->orderBy('customers.name', "asc")
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
        $customerInfo   = json_decode($request->customerInfo);
        $discount       = 0;

        $user_id        = auth('api')->user()->id;
        $customer_id    = Customer::where('name', '=', $customerInfo->customerName)->first()->id;
        $grand_total    = $customerInfo->grandTotal - $discount;

        $sale = Sale::create([
            'sale_inv_no'  => '124',
            'user_id'      => $user_id,
            'totalQty'     => $customerInfo->totalQuantity,
            'subTotal'     => $customerInfo->grandTotal,
            'discount'     => $discount,
            'grandTotal'   => $grand_total,
        ]);

        $invoice_number = "sale-inv-" . strval($sale->id + 100000);

        if ($customerInfo->cashSale) {
            $sale->sale_inv_no  = $invoice_number;
            $sale->name         = $customerInfo->customerName;
            $sale->mobile       = $customerInfo->customerMobile;
            $sale->address      = $customerInfo->customerAddress;
        } else {
            $sale->customer_id  = $customer_id;
            $sale->sale_inv_no  = $invoice_number;
        }
        $sale->save();

        (new UpdateData())->addToCustomerDetails(new CustomerDetail(), $customer_id, 'debit', $grand_total, 'Sale', $invoice_number);

        foreach (json_decode($request->shopItems) as $item) {
            SaleDetail::create([
                'sale_inv_no'  => $invoice_number,
                'quantity'     => $item->quantity,
                'product_code' => $item->product_code,
                'price'        => $item->price,
            ]);

            (new UpdateData())->addToStockDetails('debit', $item->product_code, 'Sale', $invoice_number, $item->quantity);
            (new UpdateData())->updateStock('-', $item->product_code, $item->quantity);
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
            (new UpdateData())->updateStock('+', $porduct->product_code, $porduct->quantity);
        }
        (new UpdateData())->deleteAll('sale', $sale_inv_no);
    }
}
