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
                // microtime() bo'sh joyli string qaytaradi ("0.12345 1234567890") va
                // bank tarafida hash qayta hisoblanganda escape farqlari yuzaga keladi.
                $rnd = bin2hex(random_bytes(16));
                $islemTipi = 'Auth';
                $storeType = '3d_pay';

                $currencyValue = $paymentData['currency'] == 'TL' ? 'TRY' : $paymentData['currency'];
                $currencyCode = Currency::getNumericCode($currencyValue);

                $gatewayUrl = $sandbox
                    ? 'https://entegrasyon.asseco-see.com.tr/fim/est3Dgate'
                    : 'https://sanalpos.isbank.com.tr/fim/est3Dgate';

                // Checkout formada ko'rsatilgan kart ma'lumotlarini olish (odeme.php'dagi maydon nomlari)
                $cardHolder = trim((string) $request->input('card_holder'));
                $pan = preg_replace('/\s+/', '', (string) $request->input('pan'));
                $cv2 = trim((string) $request->input('cv2'));
                $expMonth = str_pad(trim((string) $request->input('Ecom_Payment_Card_ExpDate_Month')), 2, '0', STR_PAD_LEFT);
                $expYear = trim((string) $request->input('Ecom_Payment_Card_ExpDate_Year'));
                $cardType = trim((string) $request->input('cardType'));

                // NestPay Hash Version 3 (SHA-512 + base64) — Payten dokumentatsiyasiga muvofiq
                $formData = [
                    'clientid' => $clientId,
                    'amount' => $amount,
                    'oid' => $oid,
                    'okUrl' => $okUrl,
                    'failUrl' => $failUrl,
                    'rnd' => $rnd,
                    'storetype' => $storeType,
                    'hashAlgorithm' => 'ver3',
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

                $formData['hash'] = $this->calculateVer3Hash($formData, $storeKey);

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
            return $this->paymentInfoDetail($data, $payment);
        }, 20, 2);
    }

    // NestPay Hash Version 3: parametr nomlari case-insensitive alfavit tartibida,
    // qiymatlarda "\" -> "\\", "|" -> "\|" escape qilinadi, "encoding" va "hash"
    // hash hisoblashga kirmaydi. Oxiriga "|storeKey" qo'shilib SHA-512 + base64.
    protected function calculateVer3Hash(array $params, string $storeKey): string
    {
        $filtered = [];
        foreach ($params as $key => $value) {
            $lower = strtolower($key);
            if ($lower === 'encoding' || $lower === 'hash') {
                continue;
            }
            $filtered[$key] = $value;
        }

        uksort($filtered, fn ($a, $b) => strcasecmp($a, $b));

        $escaped = array_map(
            fn ($v) => str_replace(['\\', '|'], ['\\\\', '\\|'], (string) $v),
            $filtered
        );

        $plaintext = implode('|', $escaped) . '|' . str_replace(['\\', '|'], ['\\\\', '\\|'], $storeKey);

        return base64_encode(hash('sha512', $plaintext, true));
    }

    protected function paymentInfoDetail($data, $payment)
    {
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
    }
}
