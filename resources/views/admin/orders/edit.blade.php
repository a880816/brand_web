@extends('layouts.admin')
@section('title','成交單明細')
@section('content')
<div class="admin-heading"><div><p class="admin-kicker">ORDER</p><h1>成交單明細</h1><small>{{ $order->reference }}</small></div><a href="{{ route('admin.orders.index') }}">返回列表</a></div>
@if(session('recipient_url'))<div class="notice"><strong>收件資料連結（僅本次顯示）</strong><div class="copy-row" x-data="{copied:false}"><input readonly value="{{ session('recipient_url') }}"><button type="button" class="admin-button" @click="navigator.clipboard.writeText($el.previousElementSibling.value);copied=true" x-text="copied?'已複製':'複製連結'"></button></div></div>@endif
@if(session('duplicate_last_five'))<div class="notice error" role="alert"><strong>提醒：</strong>其他待核對成交單已有相同匯款末五碼，請人工確認。</div>@endif
<section class="admin-panel">
    <div class="panel-head"><div><h2>商品明細</h2><p>建立成交單時已保留庫存。</p></div><span class="status {{ $order->status }}">{{ ['awaiting_recipient'=>'等待收件資料','awaiting_payment'=>'等待匯款','paid'=>'已成交','voided'=>'已作廢'][$order->status] }}</span></div>
    <table class="admin-table"><thead><tr><th>商品</th><th>單價</th><th>數量</th><th>小計</th></tr></thead><tbody>@foreach($order->items as $item)<tr><td data-label="商品"><strong>{{ $item->product_name }}</strong><small>{{ $item->product_code }}</small></td><td data-label="單價">NT$ {{ number_format((float)$item->unit_price) }}</td><td data-label="數量">{{ $item->quantity }}</td><td data-label="小計">NT$ {{ number_format((float)$item->line_total) }}</td></tr>@endforeach</tbody></table>
    <dl class="order-total"><dt>商品小計</dt><dd>NT$ {{ number_format((float)$order->subtotal) }}</dd><dt>運費</dt><dd>@if($order->free_shipping)<s>NT$ {{ number_format((float)$order->shipping_fee) }}</s> 免運費@else NT$ {{ number_format((float)$order->shipping_fee) }}@endif</dd><dt>應付總額</dt><dd>NT$ {{ number_format((float)$order->total) }}</dd></dl>
</section>
@if(!in_array($order->status, ['paid','voided']))
<form method="post" action="{{ route('admin.orders.update', $order) }}" class="admin-panel admin-form" x-data="{method:{{ Js::from(old('shipping_method', $order->shipping_method ?: 'seven_eleven')) }}}">
    @csrf @method('PUT')<h2>客戶與收件資料</h2>
    <div class="form-grid">
        <label>姓名<input name="customer_name" value="{{ old('customer_name', $order->customer_name) }}" required></label><label>手機<input name="phone" value="{{ old('phone', $order->phone) }}" required></label>
        <label>社群平台<select name="social_platform" required>@foreach(['facebook'=>'Facebook','instagram'=>'Instagram','line'=>'LINE','other'=>'其他'] as $value=>$label)<option value="{{ $value }}" @selected(old('social_platform', $order->social_platform) === $value)>{{ $label }}</option>@endforeach</select></label><label>社群帳號<input name="social_account" value="{{ old('social_account', $order->social_account) }}" required></label>
        <label>收件方式<select name="shipping_method" x-model="method" required><option value="seven_eleven">7-11 店到店</option><option value="family_mart">全家店到店</option><option value="postal">郵寄</option><option value="self_pickup">自取</option></select></label><label>自訂運費（留空使用預設）<input type="number" name="shipping_fee_override" min="0" step="1" value="{{ old('shipping_fee_override') }}"></label>
        <template x-if="['seven_eleven','family_mart'].includes(method)"><div class="form-grid nested"><label>店號<input name="store_no" value="{{ old('store_no', data_get($order->shipping_details,'store_no')) }}"></label><label>門市名稱<input name="store_name" value="{{ old('store_name', data_get($order->shipping_details,'store_name')) }}"></label></div></template>
        <template x-if="method==='postal'"><div class="form-grid nested"><label>郵遞區號<input name="postal_code" value="{{ old('postal_code', data_get($order->shipping_details,'postal_code')) }}"></label><label>縣市<input name="postal_city" value="{{ old('postal_city', data_get($order->shipping_details,'city')) }}"></label><label>鄉鎮市區<input name="postal_district" value="{{ old('postal_district', data_get($order->shipping_details,'district')) }}"></label><label>地址<input name="postal_address" value="{{ old('postal_address', data_get($order->shipping_details,'address')) }}"></label></div></template>
        <label>匯款末五碼（選填）<input name="remittance_last_five" value="{{ old('remittance_last_five', $order->remittance_last_five) }}" maxlength="5" inputmode="numeric"></label>
    </div>
    <label>備註<textarea name="notes">{{ old('notes', $order->notes) }}</textarea></label><button class="admin-button">儲存資料</button>
</form>
<div class="publish-actions admin-panel"><form method="post" action="{{ route('admin.orders.recipient-link', $order) }}">@csrf<button class="admin-button secondary">重新產生收件連結</button></form><form method="post" action="{{ route('admin.orders.paid', $order) }}" onsubmit="return confirm('確認已收到款項並將商品標示售出？')">@csrf<button class="admin-button">確認收款並成交</button></form><form method="post" action="{{ route('admin.orders.void', $order) }}" onsubmit="return confirm('作廢並釋出庫存？')">@csrf<button class="admin-button danger">作廢</button></form></div>
@endif
<div class="danger-zone"><form method="post" action="{{ route('admin.orders.destroy', $order) }}" onsubmit="return confirm('刪除成交單並恢復庫存？')">@csrf @method('DELETE')<button class="admin-button danger">刪除成交單</button></form></div>
@endsection
