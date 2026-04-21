@if ($payment)
    @if ($payment->amount_refunded)
        <h6 class="alert-heading mt-4">{{ trans('plugins/payment::payment.amount_refunded') }}:
            {{ $payment->amount_refunded }} {{ $payment->currency }}
        </h6>
    @endif

    <div class="mt-4">
        @include('plugins/payment::partials.view-payment-source')
    </div>
@endif
