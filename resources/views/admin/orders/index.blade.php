@extends('layouts.admin')
@section('title', '成交單')
@section('content')
    <div class="admin-heading">
        <div>
            <p class="admin-kicker">ORDERS</p>
            <h1>成交單</h1>
        </div>
        <a class="admin-button" href="{{ route('admin.orders.create') }}">新增成交單</a>
    </div>
    <section class="admin-panel table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>編號</th>
                    <th>客戶</th>
                    <th>商品</th>
                    <th>金額</th>
                    <th>狀態</th>
                    <th>建立時間</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($orders as $order)
                    <tr>
                        <td data-label="編號">
                            <small>{{ Str::limit($order->reference, 13) }}</small>
                        </td>
                        <td data-label="客戶">{{ $order->customer_name ?: '等待填寫' }}</td>
                        <td data-label="商品">{{ $order->items_count }} 項</td>
                        <td data-label="金額">NT$ {{ number_format((float) $order->total) }}</td>
                        <td data-label="狀態">
                            <span
                                class="status {{ $order->status }}">{{ ['awaiting_recipient' => '等待收件資料', 'awaiting_payment' => '等待匯款', 'paid' => '已成交', 'voided' => '已作廢'][$order->status] }}</span>
                        </td>
                        <td data-label="建立時間">{{ $order->created_at->format('Y/m/d H:i') }}</td>
                        <td data-label="操作" class="table-actions">
                            <a href="{{ route('admin.orders.edit', $order) }}">處理</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="admin-empty">尚無成交單。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>{{ $orders->links() }}
    </section>
@endsection
