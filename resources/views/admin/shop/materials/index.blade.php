@extends('layouts.admin')
@section('title', '資材')
@section('content')
    <div class="admin-heading">
        <div>
            <p class="admin-kicker">MATERIALS</p>
            <h1>資材</h1>
        </div>
        <a class="admin-button" href="{{ route('admin.materials.create') }}">新增資材</a>
    </div>
    <section class="admin-panel table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>商品</th>
                    <th>編號</th>
                    <th>售價</th>
                    <th>在庫</th>
                    <th>狀態</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                @forelse($materials as $material)
                    @php($label = $material->status === 'draft' ? '草稿' : ($material->status === 'inactive' ? '下架' : ($material->availableQuantity() > 0 ? '販售中' : '售完')))
                    <tr>
                        <td data-label="商品">
                            <strong>{{ $material->name }}</strong>
                        </td>
                        <td data-label="編號">{{ $material->product_code }}</td>
                        <td data-label="售價">NT$ {{ number_format((float) $material->price) }}</td>
                        <td data-label="在庫">{{ $material->availableQuantity() }} @if ($material->availableQuantity() <= $material->low_stock_threshold)
                                <span class="warning">低庫存</span>
                            @endif
                        </td>
                        <td data-label="狀態">
                            <span class="status {{ $material->status }}">{{ $label }}</span>
                        </td>
                        <td data-label="操作" class="table-actions">
                            <a href="{{ route('admin.materials.edit', $material) }}">編輯</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="admin-empty">尚無資材。</td>
                    </tr>
                @endforelse
            </tbody>
        </table>{{ $materials->links() }}
    </section>
@endsection
