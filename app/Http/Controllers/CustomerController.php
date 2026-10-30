<?php
namespace App\Http\Controllers;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
class CustomerController extends Controller { public function __construct(){ $this->middleware('permission:customer-manage'); } public function index(): View { $customers=Customer::where('outlet_id',auth()->user()->outlet_id)->latest()->paginate(30); return view('customers.index',compact('customers')); } public function store(Request $request): RedirectResponse { $data=$request->validate(['name'=>['required','string','max:150'],'phone'=>['nullable','string','max:30'],'email'=>['nullable','email','max:150']]); Customer::create($data+['outlet_id'=>$request->user()->outlet_id]); return redirect()->route('customers.index'); } }
