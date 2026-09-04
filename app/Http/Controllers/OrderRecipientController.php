<?php

namespace App\Http\Controllers;

use App\Models\SaleOrder;
use App\Services\SaleOrderService;
use App\Support\BrandContext;
use Illuminate\Http\Request;

class OrderRecipientController extends Controller
{
    public function edit(string $reference,string $token,BrandContext $context){$order=$this->order($reference,$token,$context);return view('site.orders.recipient',compact('order','token'));}
    public function update(Request $request,string $reference,string $token,BrandContext $context,SaleOrderService $service){$order=$this->order($reference,$token,$context);$data=$request->validate(['customer_name'=>'required|string|max:100','phone'=>'required|string|max:40','social_platform'=>'required|in:facebook,instagram,line,other','social_account'=>'required|string|max:255','shipping_method'=>'required|in:seven_eleven,family_mart,postal,self_pickup','store_no'=>'required_if:shipping_method,seven_eleven,family_mart|nullable|string|max:30','store_name'=>'required_if:shipping_method,seven_eleven,family_mart|nullable|string|max:100','postal_address'=>'required_if:shipping_method,postal|nullable|string|max:500','remittance_last_five'=>'nullable|regex:/^[0-9]{5}$/','notes'=>'nullable|string|max:2000']);$order=$service->saveRecipient($order,$data);return view('site.orders.received',compact('order'));}
    private function order(string $reference,string $token,BrandContext $context):SaleOrder{$order=SaleOrder::where('brand_id',$context->id())->where('reference',$reference)->with('items')->firstOrFail();abort_unless(hash_equals((string)$order->recipient_token_hash,hash('sha256',$token))&&$order->recipientLinkIsValid(),410);return $order;}
}
