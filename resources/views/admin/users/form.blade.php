@extends('layouts.admin')

@section('title', ($item->exists ? '編輯' : '新增') . '使用者')

@section('content')
    <div class="admin-heading">
        <h1>{{ $item->exists ? '編輯' : '新增' }}使用者</h1>
        <a href="{{ route('admin.users.index') }}">返回列表</a>
    </div>
    <form method="post" action="{{ $item->exists ? route('admin.users.update', $item) : route('admin.users.store') }}"
        class="admin-panel admin-form">
        @csrf
        @if ($item->exists)
            @method('PUT')
        @endif
        <div class="form-grid">
            <label>姓名<input name="name" value="{{ old('name', $item->name) }}" required>
            </label>
            <label>Email<input type="email" name="email" value="{{ old('email', $item->email) }}" required>
            </label>
            <label>角色<select name="role">
                    @foreach (\App\Models\User::ROLES as $role)
                        <option value="{{ $role }}" @selected(old('role', $item->role ?: 'brand_admin') === $role)>{{ $role }}</option>
                    @endforeach
                </select>
            </label>
            <label>狀態<select name="status">
                    <option value="active" @selected(old('status', $item->status ?: 'active') === 'active')>active</option>
                    <option value="disabled" @selected(old('status', $item->status) === 'disabled')>disabled</option>
                </select>
            </label>
            <label>{{ $item->exists ? '新密碼（留空不變）' : '密碼' }}<input type="password" name="password" @required(!$item->exists)>
            </label>
            <label>確認密碼<input type="password" name="password_confirmation" @required(!$item->exists)>
            </label>
        </div>
        <fieldset>
            <legend>可管理品牌（brand_admin）</legend>
            <div class="checks">
                @foreach ($brands as $option)
                    <label class="check">
                        <input type="checkbox" name="brand_ids[]" value="{{ $option->id }}" @checked(in_array($option->id, old('brand_ids', $item->exists ? $item->brands->pluck('id')->all() : [])))>
                        {{ $option->name }}</label>
                @endforeach
            </div>
        </fieldset>
        <button class="admin-button">儲存</button>
    </form>
@endsection
