<?php

namespace App\Http\Controllers\API\Purchase;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Others\Supplier;
use App\Model\Others\SupplierDetail;
use App\Model\Purchase\Purchase;
use App\Model\Purchase\PurchaseReturn;
use App\Model\Purchase\PurchaseReturnDetail;
use App\Model\Stocks\Stock;
use App\Model\Stocks\StockDetail;
use DB;

class PurchaseReturnController extends Controller
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
        $purchases = PurchaseReturn::join('suppliers', 'suppliers.id', '=', 'purchase_returns.supplier_id')
            ->orderBy('suppliers.name', "asc")
            ->with('supplier')
            ->with('purchaseReturnItems')
            ->with('user');

        return response()->JSON($purchases->paginate(3));
    }
    // get returnable purchases product list
    public function returnableProducts($supplier)
    {
        $purchases = Purchase::join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            // ->where('suppliers.name', '=', $supplier)
            ->with('purchaseItems');

        return response()->JSON($purchases->get());
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
        $supplier    = DB::table('suppliers')->where('name', $returnInfo->supplier)->first();

        $discount = 0;
        $purchaseReturn    =  PurchaseReturn::create([
            'pur_rtn_no'   => '124',
            'user_id'      => $user_id,
            'supplier_id'  => $supplier->id,
            'totalQty'     => $returnInfo->totalQty,
            'discount'     => $discount,
            'grandTotal'   => $returnInfo->grandTotal,
        ]);

        $invoice_number = "pur-rtn-" . strval($purchaseReturn->id + 100000);
        $purchaseReturn->pur_rtn_no  = $invoice_number;
        $purchaseReturn->save();

        SupplierDetail::create([
            'customer_id'  => Supplier::where('name', '=', $returnInfo->name)->first()->id,
            'debit'        => $returnInfo->grandTotal - $discount,
            'description'  => "Purchase Return",
            'source_id'    => $invoice_number,
        ]);

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
    public function destroy($pur_rtn_no)
    {
        $porducts = PurchaseReturnDetail::where('pur_rtn_no', '=', $pur_rtn_no)->get();

        foreach ($porducts as $porduct) {
            $stock = Stock::where('product_code', '=', $porduct->product_code)->first();
            $stock->quantity += $porduct->quantity;
            $stock->save();
        }

        StockDetail::where('source_id', '=', $pur_rtn_no)->delete();
        PurchaseReturnDetail::where('pur_rtn_no', '=', $pur_rtn_no)->delete();
        SupplierDetail::where('source_id', '=', $pur_rtn_no)->delete();
        PurchaseReturn::where('pur_rtn_no', '=', $pur_rtn_no)->delete();
    }
}
