<?php

namespace App\Http\Controllers\API\Purchase;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Others\Supplier;
use App\Model\Others\SupplierDetail;
use App\Model\Purchase\Purchase;
use App\Model\Purchase\PurchaseDetail;
use App\Model\Stocks\Stock;
use App\Model\Stocks\StockDetail;

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
    public function index()
    {
        $purchases = Purchase::join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->orderBy('suppliers.name', "asc")
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
    public function store(Request $request)
    {
        $supplierInfo = json_decode($request->supplierInfo);
        $discount = 0;

        $user_id = auth('api')->user()->id;
        $purchase = Purchase::create([
            'pur_inv_no'   => '124',
            'user_id'      => $user_id,
            'supplier_id'  => 1,
            'totalQty'     => $supplierInfo->totalQuantity,
            'subTotal'     => $supplierInfo->grandTotal,
            'discount'     => $discount,
            'grandTotal'   => $supplierInfo->grandTotal - $discount,
        ]);

        if ($supplierInfo->cashPurchase) {
            $purchase->name     = $supplierInfo->name;
            $purchase->mobile   = $supplierInfo->mobile;
            $purchase->address  = $supplierInfo->address;
            $purchase->save();
        } else {
            $purchase->supplier_id  = Supplier::where('name', '=', $supplierInfo->name)->first()->id;
            $purchase->save();
        }

        $invoice_number = "pur-inv-" . strval($purchase->id + 100000);
        $purchase->pur_inv_no  = $invoice_number;
        $purchase->save();

        SupplierDetail::create([
            'supplier_id'  => Supplier::where('name', '=', $supplierInfo->name)->first()->id,
            'credit'       => $supplierInfo->grandTotal - $discount,
            'description'  => "Purchase",
            'source_id'    => $invoice_number,
        ]);

        foreach (json_decode($request->shopItems) as $item) {
            PurchaseDetail::create([
                'pur_inv_no'    => $invoice_number,
                'quantity'      => $item->quantity,
                'product_code'  => $item->product_code,
                'price'         => $item->price,
            ]);

            StockDetail::create([
                'product_code' => $item->product_code,
                'description'  => 'Purchase',
                'source_id'    => $invoice_number,
                'credit'       => $item->quantity,
            ]);

            $stock = Stock::where('product_code', '=', $item->product_code)->first();
            $stock->quantity += $item->quantity;
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
    public function destroy($pur_inv_no)
    {
        // stock reduce, delete stockdetail, purchasedetail and purchase
        $porducts = PurchaseDetail::where('pur_inv_no', '=', $pur_inv_no)->get();

        foreach ($porducts as $porduct) {
            $stock = Stock::where('product_code', '=', $porduct->product_code)->first();
            $stock->quantity -= $porduct->quantity;
            $stock->save();
        }

        StockDetail::where('source_id', '=', $pur_inv_no)->delete();
        PurchaseDetail::where('pur_inv_no', '=', $pur_inv_no)->delete();
        SupplierDetail::where('source_id', '=', $pur_inv_no)->delete();
        Purchase::where('pur_inv_no', '=', $pur_inv_no)->delete();
    }
}
