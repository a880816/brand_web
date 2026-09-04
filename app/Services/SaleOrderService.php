<?php

namespace App\Services;

use App\Models\{Brand, Material, PlantSoldUnit, PlantSpecimen, SaleOrder, SaleOrderItem, User};
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleOrderService
{
    public function create(Brand $brand, User $creator, array $lines, bool $freeShipping = false): array
    {
        $rawToken = Str::random(64);
        $order = DB::transaction(function () use ($brand, $creator, $lines, $freeShipping, $rawToken) {
            $order = new SaleOrder(['brand_id'=>$brand->id,'created_by'=>$creator->id,'status'=>'awaiting_recipient','free_shipping'=>$freeShipping,'bank_snapshot'=>$brand->bankSnapshot(),'subtotal'=>0,'shipping_fee'=>0,'total'=>0,'recipient_link_expires_at'=>now()->addHours(config('commerce.recipient_link_ttl_hours'))]);
            $order->reference=(string)Str::uuid();$order->recipient_token_hash=hash('sha256',$rawToken);$order->save();
            $subtotal=0;
            foreach($lines as $index=>$line){$item=$this->resolveAndReserve($brand,(string)$line['url'],(int)$line['quantity'],$index);$subtotal+=$item['line_total'];$order->items()->create($item);}
            $order->update(['subtotal'=>$subtotal,'total'=>$subtotal]);return $order;
        },3);
        return [$order,$rawToken];
    }

    public function regenerateRecipientLink(SaleOrder $order): string
    {
        abort_if(in_array($order->status,['paid','voided'],true),422,'此成交單不可再產生收件連結。');
        $token=Str::random(64);$order->recipient_token_hash=hash('sha256',$token);$order->recipient_link_expires_at=now()->addHours(config('commerce.recipient_link_ttl_hours'));$order->save();return $token;
    }

    public function saveRecipient(SaleOrder $order,array $data,bool $requireValidLink=true): SaleOrder
    {
        return DB::transaction(function()use($order,$data,$requireValidLink){$locked=SaleOrder::lockForUpdate()->findOrFail($order->id);if($requireValidLink)abort_unless($locked->recipientLinkIsValid(),410);else abort_if(in_array($locked->status,['paid','voided'],true),422,'此成交單不可修改。');$method=$data['shipping_method'];$details=match($method){'seven_eleven','family_mart'=>['store_no'=>$data['store_no'],'store_name'=>$data['store_name']],'postal'=>['address'=>$data['postal_address']],default=>[]};$fee=(float)config('commerce.shipping_fees.'.$method,0);$locked->update(['customer_name'=>$data['customer_name'],'phone'=>$data['phone'],'social_platform'=>$data['social_platform'],'social_account'=>$data['social_account'],'shipping_method'=>$method,'shipping_details'=>$details,'shipping_fee'=>$fee,'total'=>(float)$locked->subtotal+($locked->free_shipping?0:$fee),'remittance_last_five'=>$data['remittance_last_five']??null,'notes'=>$data['notes']??null,'status'=>'awaiting_payment']);return $locked;});
    }

    public function markPaid(SaleOrder $order): SaleOrder
    {
        return DB::transaction(function()use($order){$locked=SaleOrder::whereKey($order->id)->lockForUpdate()->with('items')->firstOrFail();abort_unless($locked->status==='awaiting_payment'&&filled($locked->customer_name)&&filled($locked->shipping_method),422,'請先完成客戶與收件資料。');foreach($locked->items as $item){if($item->product_type==='specimen'){$product=PlantSpecimen::lockForUpdate()->findOrFail($item->product_id);abort_if($product->reserved_quantity<$item->quantity||$product->stock_on_hand<$item->quantity,422,'植株庫存狀態不一致。');$product->stock_on_hand-=$item->quantity;$product->reserved_quantity-=$item->quantity;for($i=0;$i<$item->quantity;$i++){$product->sold_sequence++;$isNoPick=(bool)data_get($item->snapshot,'is_no_pick');$soldCode=$isNoPick?$product->sequence.'-'.$product->sold_sequence:($product->custom_name?:$product->sequence);PlantSoldUnit::create(['brand_id'=>$locked->brand_id,'plant_variety_id'=>$product->plant_variety_id,'plant_specimen_id'=>$product->id,'sale_order_item_id'=>$item->id,'unit_sequence'=>$product->sold_sequence,'sold_code'=>$soldCode,'full_tag_name_snapshot'=>$isNoPick?trim(data_get($item->snapshot,'scientific_name').' '.data_get($item->snapshot,'variety_code').' '.$soldCode):$product->full_tag_name,'variety_name_snapshot'=>data_get($item->snapshot,'variety_name'),'price_snapshot'=>$item->unit_price,'sold_at'=>now()]);}$product->save();}else{$product=Material::lockForUpdate()->findOrFail($item->product_id);abort_if($product->reserved_quantity<$item->quantity||$product->stock_on_hand<$item->quantity,422,'資材庫存狀態不一致。');$product->decrement('stock_on_hand',$item->quantity);$product->decrement('reserved_quantity',$item->quantity);}$item->update(['fulfilled_quantity'=>$item->quantity]);}$locked->paid_at=now();$locked->status='paid';$locked->recipient_token_hash=null;$locked->save();return $locked;},3);
    }

    public function void(SaleOrder $order): SaleOrder
    {
        return DB::transaction(function()use($order){$locked=SaleOrder::whereKey($order->id)->lockForUpdate()->with('items.soldUnits')->firstOrFail();abort_if($locked->status==='voided',422,'成交單已作廢。');foreach($locked->items as $item){if($item->product_type==='specimen'){$product=PlantSpecimen::withTrashed()->lockForUpdate()->findOrFail($item->product_id);}else{$product=Material::withTrashed()->lockForUpdate()->findOrFail($item->product_id);}if($locked->status==='paid'){$product->increment('stock_on_hand',$item->quantity);$item->soldUnits()->forceDelete();$item->update(['fulfilled_quantity'=>0]);}else{$product->reserved_quantity=max(0,$product->reserved_quantity-$item->quantity);$product->save();}}$locked->status='voided';$locked->voided_at=now();$locked->recipient_token_hash=null;$locked->save();return $locked;},3);
    }

    private function resolveAndReserve(Brand $brand,string $url,int $quantity,int $index):array
    {
        if($quantity<1)throw ValidationException::withMessages(["lines.$index.quantity"=>'數量至少為 1。']);$path=parse_url($url,PHP_URL_PATH);if(!is_string($path))throw ValidationException::withMessages(["lines.$index.url"=>'商品連結格式不正確。']);
        if(preg_match('#^/shop/plants/[^/]+/specimens/(\d+)$#',$path,$matches)){$product=PlantSpecimen::where('brand_id',$brand->id)->where('status','published')->lockForUpdate()->with('variety')->find($matches[1]);if(!$product)throw ValidationException::withMessages(["lines.$index.url"=>'找不到此品牌的販售中實株。']);if($product->stock_on_hand===1&&$quantity!==1)throw ValidationException::withMessages(["lines.$index.quantity"=>'可挑選實株的數量只能為 1。']);if($product->availableQuantity()<$quantity)throw ValidationException::withMessages(["lines.$index.quantity"=>'實株庫存不足或已被其他成交單保留。']);$isNoPick=$product->stock_on_hand>1;$product->increment('reserved_quantity',$quantity);return ['brand_id'=>$brand->id,'product_type'=>'specimen','product_id'=>$product->id,'product_code'=>$product->full_tag_name,'product_name'=>$product->variety->name.'・'.($isNoPick?'不挑株':($product->custom_name?:'實株 '.$product->sequence)),'unit_price'=>$product->price,'quantity'=>$quantity,'line_total'=>(float)$product->price*$quantity,'snapshot'=>['is_no_pick'=>$isNoPick,'scientific_name'=>$product->variety->scientific_name,'variety_code'=>$product->variety->variety_code,'variety_name'=>$product->variety->name]];}
        if(preg_match('#^/shop/materials/[^/]+$#',$path)){$slug=basename($path);$product=Material::where('brand_id',$brand->id)->where('slug',$slug)->where('status','published')->lockForUpdate()->first();if(!$product)throw ValidationException::withMessages(["lines.$index.url"=>'找不到此品牌的販售中資材。']);if($product->availableQuantity()<$quantity)throw ValidationException::withMessages(["lines.$index.quantity"=>'資材庫存不足。']);$product->increment('reserved_quantity',$quantity);return ['brand_id'=>$brand->id,'product_type'=>'material','product_id'=>$product->id,'product_code'=>$product->product_code,'product_name'=>$product->name,'unit_price'=>$product->price,'quantity'=>$quantity,'line_total'=>(float)$product->price*$quantity,'snapshot'=>[]];}
        throw ValidationException::withMessages(["lines.$index.url"=>'請貼上實株明細或資材商品連結。']);
    }
}
