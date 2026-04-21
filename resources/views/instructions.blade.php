<ol>
    <li>
        <p>
            <a href="https://www.isbank.com.tr/" target="_blank">
                {{ trans('plugins/isbank-sanalpos::isbank-sanalpos.instructions.step_1', ['name' => 'Isbank Sanalpos']) }}
            </a>
        </p>
    </li>
    <li>
        <p>
            {{ trans('plugins/isbank-sanalpos::isbank-sanalpos.instructions.step_2') }}
        </p>
    </li>
    <li>
        <p>
            {{ trans('plugins/isbank-sanalpos::isbank-sanalpos.instructions.step_3') }}
        </p>
    </li>
    <li>
        <p>
            {{ trans('plugins/isbank-sanalpos::isbank-sanalpos.instructions.step_4') }}
        </p>

        <p>
            <strong>okUrl:</strong> <code>{{ route('payments.isbank-sanalpos.callback') }}</code><br>
            <strong>failUrl:</strong> <code>{{ route('payments.isbank-sanalpos.fail') }}</code>
        </p>
    </li>
    <li>
        <p class="text-muted" style="font-size: 12px;">
            <strong>ℹ {{ __('Sandbox info') }}:</strong>
            {{ trans('plugins/isbank-sanalpos::isbank-sanalpos.instructions.sandbox_note') }}
        </p>
        <p class="text-muted" style="font-size: 12px;">
            <strong>💳 {{ __('Test cards') }}:</strong>
            {{ trans('plugins/isbank-sanalpos::isbank-sanalpos.instructions.test_cards') }}
        </p>
    </li>
</ol>
