@extends('layouts.admin')
@section('title', '新增成交單')
@section('content')
    <div class="admin-heading">
        <div>
            <p class="admin-kicker">NEW ORDER</p>
            <h1>新增成交單</h1>
        </div>
        <a href="{{ route('admin.orders.index') }}">返回列表</a>
    </div>
    <form method="post" action="{{ route('admin.orders.store') }}" class="admin-panel admin-form" x-data="{ lines: {{ Js::from(array_values(old('lines', [['url' => '', 'quantity' => 1]]))) }} }">
        @csrf
        <p>貼上客人傳來的實株明細或資材商品網址，系統會驗證品牌與庫存並立即保留。</p>
        <template x-for="(line,index) in lines" :key="index">
            <div class="order-line">
                <label>商品連結<input type="url" :name="`lines[${index}][url]`" x-model="line.url" required
                        placeholder="http://brand-a.localhost:8085/shop/...">
                </label>
                <label>數量<input type="number" min="1" :name="`lines[${index}][quantity]`" x-model="line.quantity"
                        required>
                </label>
                <button type="button" class="admin-button danger" @click="lines.splice(index,1)"
                    x-show="lines.length>1">移除</button>
            </div>
        </template>
        <button type="button" class="admin-button secondary" @click="lines.push({url:'',quantity:1})">增加商品</button>
        <label class="check">
            <input type="checkbox" name="free_shipping" value="1" @checked(old('free_shipping'))>此成交單免運</label>
        <button class="admin-button">建立並保留庫存</button>
    </form>
@endsection
