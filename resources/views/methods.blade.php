@if (get_payment_setting('status', ISBANK_SANALPOS_PAYMENT_METHOD_NAME) == 1)
    <x-plugins-payment::payment-method
        :name="ISBANK_SANALPOS_PAYMENT_METHOD_NAME"
        paymentName="Isbank Sanalpos - Kredi Kartı"
    >
        <div class="payment_isbank_sanalpos_wrap payment_checkbox mt-3">
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">{{ __('Kart Sahibi') }} *</label>
                    <input
                        type="text"
                        name="card_holder"
                        class="form-control"
                        autocomplete="cc-name"
                    >
                </div>
                <div class="col-md-12">
                    <label class="form-label">{{ __('Kart Tipi') }} *</label>
                    <select name="cardType" class="form-control">
                        <option value="1">Visa</option>
                        <option value="2">MasterCard</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <label class="form-label">{{ __('Kart Numarası') }} *</label>
                    <input
                        type="text"
                        name="pan"
                        class="form-control"
                        maxlength="19"
                        inputmode="numeric"
                        autocomplete="cc-number"
                    >
                </div>
                <div class="col-md-4">
                    <label class="form-label">{{ __('Güvenlik Kodu (CVV)') }} *</label>
                    <input
                        type="text"
                        name="cv2"
                        class="form-control"
                        maxlength="4"
                        inputmode="numeric"
                        autocomplete="cc-csc"
                    >
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Ay') }} *</label>
                    <input
                        type="text"
                        name="Ecom_Payment_Card_ExpDate_Month"
                        class="form-control"
                        maxlength="2"
                        placeholder="MM"
                        inputmode="numeric"
                        autocomplete="cc-exp-month"
                    >
                </div>
                <div class="col-md-6">
                    <label class="form-label">{{ __('Yıl') }} *</label>
                    <input
                        type="text"
                        name="Ecom_Payment_Card_ExpDate_Year"
                        class="form-control"
                        maxlength="4"
                        placeholder="YYYY"
                        inputmode="numeric"
                        autocomplete="cc-exp-year"
                    >
                </div>
            </div>
            <p class="mt-2 text-muted" style="font-size: 12px;">
                {{ __('3D Secure ile güvenli ödeme. Kart bilgileriniz Isbank tarafından şifrelenir.') }}
            </p>
        </div>
    </x-plugins-payment::payment-method>
@endif
