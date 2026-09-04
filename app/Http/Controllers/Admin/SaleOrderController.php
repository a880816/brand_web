<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SaleOrder;
use App\Services\{AuditService, SaleOrderService};
use App\Support\BrandContext;
use Illuminate\Http\Request;

class SaleOrderController extends Controller
{
    public function index(BrandContext $context){$this->authorize('viewAny',SaleOrder::class);$orders=SaleOrder::where('brand_id',$context->id())->withCount('items')->latest()->paginate(30);return view('admin.orders.index',compact('orders'));}
    public function create(){$this->authorize('create',SaleOrder::class);return view('admin.orders.create');}
    public function store(Request $request,BrandContext $context,SaleOrderService $service,AuditService $audit){$this->authorize('create',SaleOrder::class);$data=$request->validate(['lines'=>'required|array|min:1|max:30','lines.*.url'=>'required|url:http,https|max:2000','lines.*.quantity'=>'required|integer|min:1|max:10000','free_shipping'=>'nullable|boolean']);[$order,$token]=$service->create($context->brand(),$request->user(),$data['lines'],$request->boolean('free_shipping'));$audit->record('sale_orders.created',$order,[],$order->load('items')->toArray());return redirect()->route('admin.orders.edit',$order)->with('status','成交單已建立並保留庫存。')->with('recipient_url',route('order-recipient.edit',[$order->reference,$token]));}
    public function edit(SaleOrder $order,BrandContext $context){$order=$this->tenant($order,$context);return view('admin.orders.edit',compact('order'));}
    public function update(Request $request,SaleOrder $order,BrandContext $context,SaleOrderService $service,AuditService $audit){$order=$this->tenant($order,$context);$this->authorize('update',$order);$data=$request->validate(['customer_name'=>'required|string|max:100','phone'=>'required|string|max:40','social_platform'=>'required|in:facebook,instagram,line,other','social_account'=>'required|string|max:255','shipping_method'=>'required|in:seven_eleven,family_mart,postal,self_pickup','store_no'=>'required_if:shipping_method,seven_eleven,family_mart|nullable|string|max:30','store_name'=>'required_if:shipping_method,seven_eleven,family_mart|nullable|string|max:100','postal_address'=>'required_if:shipping_method,postal|nullable|string|max:500','remittance_last_five'=>'nullable|regex:/^[0-9]{5}$/','notes'=>'nullable|string|max:2000']);$before=$order->toArray();$service->saveRecipient($order,$data,false);$audit->record('sale_orders.recipient_updated',$order,$before,$order->fresh()->toArray());return back()->with('status','收件與匯款資料已更新。');}
    public function regenerate(SaleOrder $order,BrandContext $context,SaleOrderService $service,AuditService $audit){$order=$this->tenant($order,$context);$this->authorize('update',$order);$token=$service->regenerateRecipientLink($order);$audit->record('sale_orders.recipient_link_regenerated',$order);return back()->with('status','已產生新的收件連結，舊連結立即失效。')->with('recipient_url',route('order-recipient.edit',[$order->reference,$token]));}
    public function paid(SaleOrder $order,BrandContext $context,SaleOrderService $service,AuditService $audit){$order=$this->tenant($order,$context);$this->authorize('update',$order);$before=$order->toArray();$service->markPaid($order);$audit->record('sale_orders.paid',$order,$before,$order->fresh()->toArray());return back()->with('status','已確認收款，商品已標示售出並扣除庫存。');}
    public function void(SaleOrder $order,BrandContext $context,SaleOrderService $service,AuditService $audit){$order=$this->tenant($order,$context);$this->authorize('update',$order);$before=$order->toArray();$service->void($order);$audit->record('sale_orders.voided',$order,$before,$order->fresh()->toArray());return back()->with('status','成交單已作廢，庫存已恢復。');}
    public function destroy(SaleOrder $order,BrandContext $context,SaleOrderService $service,AuditService $audit){$order=$this->tenant($order,$context);$this->authorize('delete',$order);if($order->status!=='voided')$service->void($order);$audit->record('sale_orders.deleted',$order,$order->fresh()->toArray());$order->delete();return redirect()->route('admin.orders.index')->with('status','成交單已刪除，庫存已恢復。');}
    private function tenant(SaleOrder $order,BrandContext $context):SaleOrder{abort_unless($order->brand_id===$context->id(),404);$this->authorize('view',$order);return $order->load('items');}
}
