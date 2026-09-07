@props(['bank' => []])
@if ($bank)
    <dl class="bank-information">
        @if (data_get($bank, 'bank_name'))
            <dt>銀行</dt>
            <dd>{{ data_get($bank, 'bank_name') }}（{{ data_get($bank, 'bank_code') }}）</dd>
        @endif
        @if (data_get($bank, 'bank_branch'))
            <dt>分行</dt>
            <dd>{{ data_get($bank, 'bank_branch') }}</dd>
        @endif
        @if (data_get($bank, 'bank_account_name'))
            <dt>戶名</dt>
            <dd>{{ data_get($bank, 'bank_account_name') }}</dd>
        @endif
        @if (data_get($bank, 'bank_account_number'))
            <dt>帳號</dt>
            <dd>{{ data_get($bank, 'bank_account_number') }}</dd>
        @endif
    </dl>
    @if (data_get($bank, 'remittance_notice'))
        <p>{{ data_get($bank, 'remittance_notice') }}</p>
    @endif
@else
    <p>匯款帳戶尚未設定，請先私訊品牌粉專。</p>
@endif
