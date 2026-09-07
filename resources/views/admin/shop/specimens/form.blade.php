@extends('layouts.admin')
@section('title', ($specimen->exists ? '編輯' : '新增') . '實株')
@section('content')
    <div class="admin-heading">
        <div>
            <p class="admin-kicker">SPECIMEN</p>
            <h1>{{ $variety->name }}：{{ $specimen->exists ? '編輯' : '新增' }}實株</h1>
        </div>
        <a href="{{ route('admin.plant-varieties.edit', $variety) }}">返回品種</a>
    </div>
    <form method="post"
        action="{{ $specimen->exists ? route('admin.plant-specimens.update', [$variety, $specimen]) : route('admin.plant-specimens.store', $variety) }}"
        class="admin-panel admin-form">
        @csrf
        @if ($specimen->exists)
            @method('PUT')
        @endif
        @if ($specimen->exists)
            <div class="notice">流水號 {{ $specimen->sequence }}・完整花牌：{{ $specimen->full_tag_name }}</div>
        @endif
        <div class="form-grid">
            <label>額外命名（選填）<input name="custom_name" value="{{ old('custom_name', $specimen->custom_name) }}">
                <small>不可為純數字；填寫後會取代流水號顯示於花牌</small>
            </label>
            <label>售價<input type="number" name="price" min="0" step="1"
                    value="{{ old('price', $specimen->price) }}" required>
            </label>
            <label>庫存<input type="number" name="stock_on_hand" min="0"
                    value="{{ old('stock_on_hand', $specimen->stock_on_hand ?? 1) }}" required>
                <small>大於 1 視為不挑株並共用照片</small>
            </label>
            <label>狀態<select name="status">
                    <option value="draft" @selected(old('status', $specimen->status ?: 'draft') === 'draft')>下架</option>
                    <option value="published" @selected(old('status', $specimen->status) === 'published')>販售中</option>
                </select>
            </label>
        </div>
        <label>植株描述
            <textarea name="description" rows="7">{{ old('description', $specimen->description) }}</textarea>
        </label>
        @php($specs = old('spec_keys') ? collect(old('spec_keys'))->map(fn($key, $i) => ['name' => $key, 'value' => old('spec_values')[$i] ?? ''])->all() : ($specimen->specifications ?: [['name' => '', 'value' => '']]))<fieldset class="admin-subpanel" x-data="{ specs: {{ Js::from($specs) }} }">
            <legend>規格</legend>
            <template x-for="(spec,index) in specs">
                <div class="repeat-row specs">
                    <label>規格名<input :name="`spec_keys[${index}]`" x-model="spec.name">
                    </label>
                    <label>內容<input :name="`spec_values[${index}]`" x-model="spec.value">
                    </label>
                    <button type="button" class="admin-button danger" @click="specs.splice(index,1)">移除</button>
                </div>
            </template>
            <button type="button" class="admin-button secondary" @click="specs.push({name:'',value:''})">新增規格</button>
        </fieldset>
        <button class="admin-button">儲存</button>
    </form>
    @if ($specimen->exists)
        <livewire:admin.media-manager owner-type="plant_specimen" :owner-id="$specimen->id" />
    @endif
@endsection
