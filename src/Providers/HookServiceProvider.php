<?php

namespace MsTeknoloji\IsbankSanalpos\Providers;

use Botble\Base\Facades\Html;
use Botble\Ecommerce\Models\Currency as CurrencyEcommerce;
use Botble\Payment\Enums\PaymentMethodEnum;
use Botble\Payment\Facades\PaymentMethods;
use MsTeknoloji\IsbankSanalpos\Forms\IsbankSanalposPaymentMethodForm;
use MsTeknoloji\IsbankSanalpos\Models\Currency;
use MsTeknoloji\IsbankSanalpos\Services\Gateways\IsbankSanalposPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Throwable;

class HookServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        add_filter(PAYMENT_FILTER_ADDITIONAL_PAYMENT_METHODS, function (?string $html, array $data) {
            PaymentMethods::method(ISBANK_SANALPOS_PAYMENT_METHOD_NAME, [
                'html' => view('plugins/isbank-sanalpos::methods', $data)->render(),
            ]);

            return $html;
        }, 13, 2);

        add_filter(PAYMENT_FILTER_AFTER_POST_CHECKOUT, function (array $data, Request $request) {
            if ($data['type'] != ISBANK_SANALPOS_PAYMENT_METHOD_NAME) {
                return $data;
            }

            $paymentData = apply_filters(PAYMENT_FILTER_PAYMENT_DATA, [], $request);

            $currentCurrency = get_application_currency();
            $supportedCurrencies = $this->app->make(IsbankSanalposPaymentService::class)->supportedCurrencyCodes();

            if (in_array(strtoupper($currentCurrency->title), $supportedCurrencies)) {
                $paymentData['currency'] = strtoupper($currentCurrency->title);
            } else {
                $currency = is_plugin_active('ecommerce') ? CurrencyEcommerce::class : null;

                if ($currency) {
                    $supportedCurrency = $currency::query()->whereIn('title', $supportedCurrencies)->first();

                    if ($supportedCurrency) {
                        $paymentData['currency'] = strtoupper($supportedCurrency->title);
                        if ($currentCurrency->is_default) {
                            $paymentData['amount'] = $paymentData['amount'] * $supportedCurrency->exchange_rate;
                        } else {
                            $paymentData['amount'] = format_price(
                                $paymentData['amount'] / $currentCurrency->exchange_rate,
                                $currentCurrency,
                                true
                            );
                        }
                    } else {
                        $paymentData['currency'] = null;
                    }
                }
            }

            if (! in_array($paymentData['currency'], $supportedCurrencies)) {
                $data['error'] = true;
                $data['message'] = __(":name doesn't support :currency. Supported currencies: :currencies.", [
                    'name' => 'Isbank Sanalpos',
                    'currency' => $data['currency'] ?? '',
                    'currencies' => implode(', ', $supportedCurrencies),
                ]);

                return $data;
            }

            if (empty($paymentData['address']['email'])) {
                return [
                    ...$data,
                    'error' => true,
                    'message' => __('Please enter your email address.'),
                ];
            }

            try {
                $clientId = get_payment_setting('client_id', ISBANK_SANALPOS_PAYMENT_METHOD_NAME);
                $storeKey = get_payment_setting('store_key', ISBANK_SANALPOS_PAYMENT_METHOD_NAME);
                $sandbox = (bool) get_payment_setting('sandbox', ISBANK_SANALPOS_PAYMENT_METHOD_NAME);

                $amount = number_format($paymentData['amount'], 2, '.', '');

                // merchant_oid — order_id va customer_id'ni qaytib topish uchun pattern
                $oid = sprintf(
                    'ISB%s000OR%sCUSID%s',
                    Str::upper(Str::random(6)),
                    Arr::get($paymentData, 'order_id.0'),
                    $paymentData['customer_id'] ?? 0,
                );

                // Checkout tokenni callback'da aniqlash uchun URL query-string orqali uzatamiz
                // (Isbank server POST callback qilganda mijoz session'i mavjud emas)
                $checkoutToken = $paymentData['checkout_token']
                    ?? (is_plugin_active('ecommerce') ? \Botble\Ecommerce\Facades\OrderHelper::getOrderSessionToken() : null);

                $okUrl = route('payments.isbank-sanalpos.callback', ['token' => $checkoutToken]);
                $failUrl = route('payments.isbank-sanalpos.fail', ['token' => $checkoutToken]);
                $rnd = microtime();
                $islemTipi = 'Auth';
                $storeType = '3d_pay';

                $currencyValue = $paymentData['currency'] == 'TL' ? 'TRY' : $paymentData['currency'];
                $currencyCode = Currency::getNumericCode($currencyValue);

                // Hash formulasi — eski odeme.php'dan olingan:
                // base64_encode(pack('H*', sha1(clientId + oid + amount + okUrl + failUrl + islemtipi + rnd + storekey)))
                $hashStr = $clientId . $oid . $amount . $okUrl . $failUrl . $islemTipi . $rnd . $storeKey;
                $hash = base64_encode(pack('H*', sha1($hashStr)));

                $gatewayUrl = $sandbox
                    ? 'https://entegrasyon.asseco-see.com.tr/fim/est3Dgate'
                    : 'https://sanalpos.isbank.com.tr/servlet/est3Dgate';

                // Checkout formada ko'rsatilgan kart ma'lumotlarini olish (odeme.php'dagi maydon nomlari)
                $cardHolder = $request->input('card_holder');
                $pan = $request->input('pan');
                $cv2 = $request->input('cv2');
                $expMonth = $request->input('Ecom_Payment_Card_ExpDate_Month');
                $expYear = $request->input('Ecom_Payment_Card_ExpDate_Year');
                $cardType = $request->input('cardType');

                $formData = [
                    'clientid' => $clientId,
                    'amount' => $amount,
                    'oid' => $oid,
                    'okUrl' => $okUrl,
                    'failUrl' => $failUrl,
                    'rnd' => $rnd,
                    'hash' => $hash,
                    'storetype' => $storeType,
                    'lang' => 'tr',
                    'currency' => $currencyCode,
                    'islemtipi' => $islemTipi,
                    'card_holder' => $cardHolder,
                    'cardType' => $cardType,
                    'pan' => $pan,
                    'cv2' => $cv2,
                    'Ecom_Payment_Card_ExpDate_Month' => $expMonth,
                    'Ecom_Payment_Card_ExpDate_Year' => $expYear,
                ];

                echo view('plugins/isbank-sanalpos::redirect', [
                    'formData' => $formData,
                    'gatewayUrl' => $gatewayUrl,
                ]);

                exit;
            } catch (Throwable $exception) {
                $data['error'] = true;
                $data['message'] = json_encode($exception->getMessage());
            }

            return $data;
        }, 13, 2);

        add_filter(PAYMENT_METHODS_SETTINGS_PAGE, function (?string $html) {
            return $html . IsbankSanalposPaymentMethodForm::create()->renderForm();
        }, 93);

        add_filter(BASE_FILTER_ENUM_ARRAY, function ($values, $class) {
            if ($class === PaymentMethodEnum::class) {
                $values['ISBANK_SANALPOS'] = ISBANK_SANALPOS_PAYMENT_METHOD_NAME;
            }

            return $values;
        }, 20, 2);

        add_filter(BASE_FILTER_ENUM_LABEL, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == ISBANK_SANALPOS_PAYMENT_METHOD_NAME) {
                $value = 'Isbank Sanalpos';
            }

            return $value;
        }, 20, 2);

        add_filter(BASE_FILTER_ENUM_HTML, function ($value, $class) {
            if ($class == PaymentMethodEnum::class && $value == ISBANK_SANALPOS_PAYMENT_METHOD_NAME) {
                $value = Html::tag(
                    'span',
                    PaymentMethodEnum::getLabel($value),
                    ['class' => 'label-success status-label']
                )->toHtml();
            }

            return $value;
        }, 20, 2);

        add_filter(PAYMENT_FILTER_GET_SERVICE_CLASS, function ($data, $value) {
            if ($value == ISBANK_SANALPOS_PAYMENT_METHOD_NAME) {
                $data = IsbankSanalposPaymentService::class;
            }

            return $data;
        }, 20, 2);

        add_filter(PAYMENT_FILTER_PAYMENT_INFO_DETAIL, function ($data, $payment) {
            if ($payment->payment_channel == ISBANK_SANALPOS_PAYMENT_METHOD_NAME) {
                $paymentService = new IsbankSanalposPaymentService();
                $paymentDetail = $paymentService->getPaymentDetails($payment->charge_id);

                if ($paymentDetail) {
                    $data = view('plugins/isbank-sanalpos::detail', [
                        'payment' => $paymentDetail,
                        'paymentModel' => $payment,
                    ])->render();
                }
            }

            return $data;
        }, 20, 2);
    }
}
