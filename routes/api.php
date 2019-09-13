<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::apiResources([
    'user'       => 'API\UserController',
    'customer'   => 'API\CustomerController',
    'supplier'   => 'API\SupplierController',
    'product'    => 'API\ProductController',
    'category'   => 'API\CategoryController',
    'user'       => 'API\UserController',
    'customer'   => 'API\CustomerController',
    'supplier'   => 'API\SupplierController',
    'purchase'   => 'API\PurchaseController',
    'product'    => 'API\ProductController',
    'point'      => 'API\PointController',
    'sale'       => 'API\SaleController',
    'saledetail' => 'API\SaleDetailController',
    'unit'       => 'API\UnitController',
    'collection' => 'API\CollectionController',
]);

Route::get('category-list', 'API\CategoryController@category_list');
Route::get('supplier-list', 'API\SupplierController@supplier_list');
Route::get('/supplier/search/{search}', 'API\SupplierController@searchSupplier');

Route::get('unit-list', 'API\UnitController@unit_list');

// product
Route::get('product-list', 'API\ProductController@product_list');
Route::get('/product/single/{product_code}', 'API\ProductController@getProduct');
Route::get('/product/select/{select}', 'API\ProductController@selectedProduct');
Route::get('/product/{perPage}/filter/{order}/order/{orderField}/as', 'API\ProductController@filterProduct');
Route::get('/product/search/{search}', 'API\ProductController@searchProduct');
Route::get('/customer/search/{search}', 'API\CustomerController@customer_search');



Route::get('/customer/search/{search}', 'API\CustomerController@customer_search');
Route::get('customer-list', 'API\CustomerController@customer_list');

Route::post('/purchase/products/{supplier}', 'API\PurchaseController@storeProducts');
// purchase return
Route::get('/purchase/returnable/products/{supplier}', 'API\PurchaseController@returnableProducts');
Route::post('/purchase/return/products/', 'API\PurchaseController@returnProducts');
Route::get('/purchase/return/invoices/', 'API\PurchaseController@returnInvoices');
Route::get('/purchase/return/delete/{id}', 'API\PurchaseController@returnDelete');


Route::post('/sale/products/', 'API\SaleController@storeProducts');
// sales return
Route::get('/sale/returnable/products/{customer}', 'API\SaleController@returnableProducts');
Route::post('/sale/return/products/', 'API\SaleController@returnProducts');
Route::get('/sale/return/invoices/', 'API\SaleController@returnInvoices');
Route::get('/sale/return/delete/{id}', 'API\SaleController@returnDelete');

// stock
Route::get('/current/stock', 'API\StockController@index');
Route::get('/stock/details/{product_code}', 'API\StockController@show');
