@extends('layouts.admin')
@section('title', ($session->exists ? '編輯' : '新增') . '場次')
@section('content')
    <div class="admin-heading">
        <div>
            <p class="admin-kicker">SESSION</p>
            <h1>{{ $course->name }}：{{ $session->exists ? '編輯' : '新增' }}場次</h1>
        </div>
        <a href="{{ route('admin.courses.edit', $course) }}">返回課程</a>
    </div>
    @php($sessionPlans = array_values(old('plans', $session->plans->map(fn($p) => ['name' => $p->name, 'participants' => $p->participants, 'price' => $p->price, 'is_enabled' => $p->is_enabled ? 1 : 0])->all())))
    <form method="post"
        action="{{ $session->exists ? route('admin.course-sessions.update', [$course, $session]) : route('admin.course-sessions.store', $course) }}"
        class="admin-panel admin-form" x-data="{ override: {{ old('override_plans', $session->exists && $session->plans->isNotEmpty() ? 1 : 0) ? 'true' : 'false' }}, plans: {{ Js::from($sessionPlans) }} }">
        @csrf
        @if ($session->exists)
            @method('PUT')
        @endif
        <div class="form-grid">
            <label>開始時間<input type="datetime-local" name="starts_at"
                    value="{{ old('starts_at', $session->starts_at?->format('Y-m-d\TH:i')) }}" required>
            </label>
            <label>結束時間<input type="datetime-local" name="ends_at"
                    value="{{ old('ends_at', $session->ends_at?->format('Y-m-d\TH:i')) }}" required>
            </label>
            <label>縣市<input name="city" value="{{ old('city', $session->city) }}" required>
            </label>
            <label>店家名稱<input name="venue_name" value="{{ old('venue_name', $session->venue_name) }}" required>
            </label>
            <label>地址<input name="address" value="{{ old('address', $session->address) }}" required>
            </label>
            <label>Google Maps
                連結<input type="url" name="google_maps_url"
                    value="{{ old('google_maps_url', $session->google_maps_url) }}" required>
            </label>
            <label>名額<input type="number" name="capacity" min="1" value="{{ old('capacity', $session->capacity) }}"
                    required @disabled($session->exists && $session->status === 'open')>
                @if ($session->exists && $session->status === 'open')
                    <input type="hidden" name="capacity" value="{{ $session->capacity }}">
                    <small>開放報名後不可調整</small>
                @endif
            </label>
            <label>開課前幾日停止報名<input type="number" name="registration_close_days" min="0"
                    value="{{ old('registration_close_days', $session->registration_close_days ?? config('courses.registration_close_days')) }}">
            </label>
            <label>狀態<select name="status">
                    <option value="draft" @selected(old('status', $session->status ?: 'draft') === 'draft')>草稿</option>
                    <option value="open" @selected(old('status', $session->status) === 'open')>開放報名</option>
                    <option value="closed" @selected(old('status', $session->status) === 'closed')>報名截止</option>
                    <option value="cancelled" @selected(old('status', $session->status) === 'cancelled')>取消場次</option>
                </select>
            </label>
        </div>
        <label class="check">
            <input type="checkbox" name="override_plans" value="1" x-model="override">此場次使用不同方案</label>
        <fieldset class="admin-subpanel" x-show="override" x-init="if (override && plans.length === 0) plans = {{ Js::from($course->plans->map(fn($p) => ['name' => $p->name, 'participants' => $p->participants, 'price' => $p->price, 'is_enabled' => $p->is_enabled ? 1 : 0])->values()) }}">
            <legend>場次方案</legend>
            <template x-for="(plan,index) in plans" :key="index">
                <div class="repeat-row">
                    <label>名稱<input :name="`plans[${index}][name]`" x-model="plan.name">
                    </label>
                    <label>包含人數<input type="number" min="1" :name="`plans[${index}][participants]`"
                            x-model="plan.participants">
                    </label>
                    <label>價格<input type="number" min="0" :name="`plans[${index}][price]`" x-model="plan.price">
                    </label>
                    <label class="check">
                        <input type="checkbox" value="1" :name="`plans[${index}][is_enabled]`"
                            x-model="plan.is_enabled">啟用</label>
                </div>
            </template>
        </fieldset>
        <div class="form-actions">
            <button class="admin-button">儲存場次</button>
        </div>
    </form>
    @if (
        $session->exists &&
            $session->status === 'cancelled' &&
            $session->registrations()->where('status', '!=', 'cancelled')->exists())
        <div class="error-summary">
            <strong>此場次已取消，仍有報名者需要人工聯絡。</strong>
        </div>
    @endif
    @if ($session->exists)
        <div class="danger-zone">
            <form method="post" action="{{ route('admin.course-sessions.destroy', [$course, $session]) }}"
                onsubmit="return confirm('確定刪除此場次？已有報名紀錄時將無法刪除。')">
                @csrf
                @method('DELETE')
                <button class="admin-button danger">刪除場次</button>
            </form>
        </div>
    @endif
@endsection
