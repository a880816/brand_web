@extends('layouts.site')
@section('title', '報名 ' . $course->name . '｜' . $brand->name)
@section('content')
    <section class="page-hero compact">
        <p class="eyebrow">REGISTRATION</p>
        <h1>課程報名</h1>
        <p>{{ $course->name }}・{{ $session->starts_at->format('Y/m/d H:i') }}・{{ $session->venue_name }}</p>
    </section>
    <section class="section registration-layout">
        <form method="post" action="{{ route('registrations.store', [$course->slug, $session]) }}" class="public-form">
            @csrf
            <input class="honeypot" name="company_website" tabindex="-1" autocomplete="off" aria-hidden="true">
            <h2>選擇方案</h2>
            <div class="plan-options">
                @foreach ($plans as $plan)
                    <label>
                        <input type="radio" name="course_plan_id" value="{{ $plan->id }}" @checked(old('course_plan_id') == $plan->id)
                            required>
                        <span>
                            <strong>{{ $plan->name }}</strong>
                            <small>{{ $plan->participants }} 人・NT$
                                {{ number_format((float) $plan->price) }}</small>
                        </span>
                    </label>
                @endforeach
            </div>
            <h2>主聯絡人</h2>
            <div class="form-grid">
                <label>姓名<input name="contact_name" value="{{ old('contact_name') }}" required autocomplete="name">
                </label>
                <label>手機<input name="phone" value="{{ old('phone') }}" required autocomplete="tel">
                </label>
                <label>Email<input type="email" name="email" value="{{ old('email') }}" required autocomplete="email">
                </label>
                <label>社群平台<select name="social_platform" required>
                        <option value="">請選擇</option>
                        <option value="facebook" @selected(old('social_platform') === 'facebook')>Facebook</option>
                        <option value="instagram" @selected(old('social_platform') === 'instagram')>Instagram</option>
                        <option value="line" @selected(old('social_platform') === 'line')>LINE</option>
                        <option value="other" @selected(old('social_platform') === 'other')>其他</option>
                    </select>
                </label>
                <label>社群帳號<input name="social_account" value="{{ old('social_account') }}" required>
                    <small>僅供課程聯絡使用</small>
                </label>
                <label>匯款帳號末五碼（選填）<input name="remittance_last_five" value="{{ old('remittance_last_five') }}"
                        inputmode="numeric" pattern="[0-9]{5}" maxlength="5">
                </label>
            </div>
            <label>備註
                <textarea name="notes" rows="4">{{ old('notes') }}</textarea>
            </label>
            @if ($errors->any())
                <div class="error-summary" role="alert">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <button class="button" type="submit">確認送出報名</button>
        </form>
        <aside class="bank-card">
            <h2>匯款資訊</h2>
            <x-bank-information :bank="$brand->bankSnapshot()" />
            <p class="warning">報名送出後會先保留名額，請於 24 小時內完成匯款，並私訊品牌粉專提供帳號末五碼。逾期由管理者人工處理。</p>
            <p>本頁不要求註冊會員。</p>
        </aside>
    </section>
@endsection
