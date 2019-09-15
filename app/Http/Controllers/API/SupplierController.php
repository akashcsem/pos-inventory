<?php

namespace App\Http\Controllers\API;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Model\Others\Supplier;

class SupplierController extends Controller
{
   public function index()
   {
      return Supplier::latest()->paginate(5);
   }

   public function searchSupplier($search)
   {
      return Supplier::where('name', 'like', '%' . $search . '%')
         ->orWhere('email', 'like', '%' . $search . '%')
         ->orWhere('mobile', 'like', '%' . $search . '%')
         ->orWhere('company_name', 'like', '%' . $search . '%')
         ->orWhere('address', 'like', '%' . $search . '%')
         ->paginate(5);
   }

   public function supplier_list()
   {
      return Supplier::pluck('name')->toArray();
   }

   public function store(Request $request)
   {
      $this->validate($request, [
         'name'         => 'required|string|max:255|min:2',
         'email'        => 'required|string|max:255|min:2',
         'mobile'       => 'required|string|max:255|min:2',
         'address'      => 'required|string|max:255|min:2',
      ]);
      // insert data
      return Supplier::create([
         'name'            => $request['name'],
         'email'           => $request['email'],
         'mobile'          => $request['mobile'],
         'address'         => $request['address'],
         'company_name'    => $request['company_name'],
         'city'            => $request['city'],
         'state'           => $request['state'],
         'zip'             => $request['zip'],
         'comments'        => $request['comments'],
         'user_id'         => auth('api')->user()->id,
         'opening_balance' => $request['opening_balance'],
      ]);
   }


   public function show($id)
   {
      // display single item detail
   }

   public function update(Request $request, Supplier $supplier)
   {
      $this->validate($request, [
         'name'         => 'required|string|max:255|min:2',
         'email'        => 'required|string|max:255|min:2',
         'mobile'       => 'required|string|max:255|min:2',
         'address'      => 'required|string|max:255|min:2',
      ]);
      $supplier->update($request->all());
      return ['message' => 'Supplier updated successfully'];
   }

   public function destroy(Supplier $supplier)
   {
      $supplier->delete();
      return ['message' => 'Supplier deleted successfully'];
   }
}
