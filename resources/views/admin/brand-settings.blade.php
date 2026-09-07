@extends('layouts.admin')
@section('title', '品牌設定')
@section('content')
    <div class="admin-heading">
        <div>
            <p class="admin-kicker">BRAND SETTINGS</p>
            <h1>品牌設定</h1>
        </div>
    </div>
    <form method="post" action="{{ route('admin.settings.update') }}" class="admin-panel admin-form">
        @csrf
        @method('PUT')
        <h2>品牌識別</h2>
        <div class="form-grid">
            <label>品牌名稱<input name="name" value="{{ old('name', $brand->name) }}" required>
            </label>
            @if (auth()->user()->isSuperAdmin())
                <label>Slug<input name="slug" value="{{ old('slug', $brand->slug) }}" required>
                </label>
                <label>Domain<input name="domain" value="{{ old('domain', $brand->domain) }}">
                </label>
                <label>狀態<select name="status">
                        <option value="active" @selected($brand->status === 'active')>啟用</option>
                        <option value="inactive" @selected($brand->status === 'inactive')>停用</option>
                    </select>
                </label>
            @endif
        </div>
        <h2>品牌色票</h2>
        <div class="color-grid">
            @foreach (['primary_color' => '主色', 'secondary_color' => '輔色', 'accent_color' => '強調色', 'background_color' => '背景色', 'text_color' => '文字色'] as $field => $label)
                <label>{{ $label }}<span>
                        <input type="color" value="{{ $brand->$field }}" aria-label="{{ $label }}選色">
                        <input name="{{ $field }}" value="{{ old($field, $brand->$field) }}" required
                            pattern="#[0-9A-Fa-f]{6}">
                    </span>
                </label>
            @endforeach
        </div>
        <h2>導覽與社群</h2>
        <div class="form-grid">
            <label>首頁選單文字<input name="home_menu_label" value="{{ old('home_menu_label', $brand->home_menu_label) }}"
                    required>
            </label>
            <label>課程選單文字<input name="courses_menu_label"
                    value="{{ old('courses_menu_label', $brand->courses_menu_label) }}" required>
            </label>
            <label>商店選單文字<input name="shop_menu_label" value="{{ old('shop_menu_label', $brand->shop_menu_label) }}"
                    required>
            </label>
            <label>Facebook URL<input type="url" name="facebook_url"
                    value="{{ old('facebook_url', $brand->facebook_url) }}">
            </label>
            <label>Instagram URL<input type="url" name="instagram_url"
                    value="{{ old('instagram_url', $brand->instagram_url) }}">
            </label>
        </div>
        <h2>匯款帳戶</h2>
        <div class="form-grid">
            <label>銀行名稱<input name="bank_name" value="{{ old('bank_name', $brand->bank_name) }}">
            </label>
            <label>銀行代碼<input name="bank_code" value="{{ old('bank_code', $brand->bank_code) }}">
            </label>
            <label>分行<input name="bank_branch" value="{{ old('bank_branch', $brand->bank_branch) }}">
            </label>
            <label>戶名<input name="bank_account_name" value="{{ old('bank_account_name', $brand->bank_account_name) }}">
            </label>
            <label>帳號<input name="bank_account_number"
                    value="{{ old('bank_account_number', $brand->bank_account_number) }}">
            </label>
        </div>
        <label>匯款提醒
            <textarea name="remittance_notice" rows="4">{{ old('remittance_notice', $brand->remittance_notice) }}</textarea>
        </label>
        <h2>SEO</h2>
        <label>SEO 標題<input name="seo_title" value="{{ old('seo_title', $brand->seo_title) }}">
        </label>
        <label>SEO 說明
            <textarea name="seo_description">{{ old('seo_description', $brand->seo_description) }}</textarea>
        </label>
        <button class="admin-button">儲存品牌設定</button>
    </form>
    <livewire:admin.media-manager owner-type="brand" :owner-id="$brand->id" collection="logo" />
@endsection
