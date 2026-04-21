<?php

namespace MsTeknoloji\IsbankSanalpos\Http\Controllers;

use Botble\Base\Http\Controllers\BaseController;
use Botble\Ecommerce\Facades\OrderHelper;
use Botble\Ecommerce\Models\Order as EcommerceOrder;
use Botble\Payment\Enums\PaymentStatusEnum;
use Botble\Payment\Events\PaymentWebhookReceived;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IsbankSanalposController extends BaseController
{
    public function callback(Request $request)
    {
        $mdStatus = $request->input('mdStatus');
        $response = $request->input('Response');
        $procReturnCode = $request->input('ProcReturnCode');
        $errMsg = $request->input('ErrMsg');
        $oid = $request->input('oid');
        $maskedPan = $request->input('MaskedPan');
        $authCode = $request->input('AuthCode');
        $hostRefNum = $request->input('HostRefNum');
        $transId = $request->input('TransId');
        $amount = (float) $request->input('amount', 0);
        $checkoutToken = $request->input('token');

        if (! $oid) {
            return $this->redirectToFail($checkoutToken, __('Invalid payment response: missing order id.'));
        }

        if (! $this->verifyHash($request)) {
            return $this->redirectToFail($checkoutToken, __('Invalid payment response: hash verification failed.'));
        }

        // oid pattern: ISB{RAND}000OR{ORDER_ID}CUSID{CUSTOMER_ID}
        $orderId = Str::of($oid)->after('000OR')->before('CUSID')->toString();
        $customerId = Str::afterLast($oid, 'CUSID');

        if (! $orderId) {
            return $this->redirectToFail($checkoutToken, __('Invalid payment response: bad order id format.'));
        }

        $customerType = is_plugin_active('ecommerce')
            ? \Botble\Ecommerce\Models\Customer::class
            : null;

        if (is_plugin_active('ecommerce')) {
            $order = EcommerceOrder::query()->where('id', $orderId)->first();

            if ($order && $order->payment_id) {
                return $this->redirectToSuccess($checkoutToken);
            }
        }

        $isApproved = in_array($mdStatus, ['1', '2', '3', '4'], true)
            && strcasecmp((string) $response, 'Approved') === 0;

        if ($isApproved) {
            $currency = $this->numericToIsoCurrency($request->input('currency', '949'));
            $chargeId = $transId ?: $hostRefNum ?: $oid;

            do_action(PAYMENT_ACTION_PAYMENT_PROCESSED, [
                'amount' => $amount,
                'currency' => $currency,
                'charge_id' => $chargeId,
                'payment_channel' => ISBANK_SANALPOS_PAYMENT_METHOD_NAME,
                'status' => PaymentStatusEnum::COMPLETED,
                'customer_id' => $customerId,
                'customer_type' => $customerType,
                'payment_type' => 'confirm',
                'order_id' => [$orderId],
                'metadata' => [
                    'auth_code' => $authCode,
                    'host_ref_num' => $hostRefNum,
                    'trans_id' => $transId,
                    'masked_pan' => $maskedPan,
                    'proc_return_code' => $procReturnCode,
                ],
            ], $request);

            PaymentWebhookReceived::dispatch($chargeId);

            return $this->redirectToSuccess($checkoutToken);
        }

        return $this->redirectToFail(
            $checkoutToken,
            $this->translateErrorMessage($mdStatus, $procReturnCode, $errMsg)
        );
    }

    public function fail(Request $request)
    {
        $checkoutToken = $request->input('token');

        $errMsg = $this->translateErrorMessage(
            $request->input('mdStatus'),
            $request->input('ProcReturnCode'),
            $request->input('ErrMsg') ?: $request->input('mdErrorMsg')
        );

        return $this->redirectToFail($checkoutToken, $errMsg);
    }

    protected function verifyHash(Request $request): bool
    {
        $postedHash = $request->input('HASH');
        $hashParamsVal = $request->input('HASHPARAMSVAL');

        if (! $postedHash || ! $hashParamsVal) {
            return (bool) get_payment_setting('sandbox', ISBANK_SANALPOS_PAYMENT_METHOD_NAME);
        }

        $storeKey = get_payment_setting('store_key', ISBANK_SANALPOS_PAYMENT_METHOD_NAME);
        $calculatedHash = base64_encode(pack('H*', sha1($hashParamsVal . $storeKey)));

        return hash_equals($calculatedHash, $postedHash);
    }

    protected function translateErrorMessage(?string $mdStatus, ?string $procReturnCode, ?string $rawErrMsg): string
    {
        $byProcCode = match ($procReturnCode) {
            '05' => __('Your bank declined the transaction. Please contact your bank or try a different card.'),
            '12' => __('Invalid transaction. Please check your card details and try again.'),
            '14' => __('Invalid card number.'),
            '51' => __('Insufficient funds on the card.'),
            '54' => __('Your card has expired.'),
            '57' => __('This card does not allow online purchases. Please contact your bank.'),
            '61' => __('The amount exceeds your card limit.'),
            '62' => __('Restricted card. Please use a different card.'),
            '65' => __('You have exceeded the transaction count limit on your card.'),
            '91' => __('Your bank is not responding. Please try again in a few minutes.'),
            default => null,
        };

        if ($byProcCode) {
            return $byProcCode;
        }

        $byMdStatus = match ($mdStatus) {
            '0' => __('3D Secure authentication failed. You may have cancelled the verification or entered a wrong SMS/password.'),
            '5' => __('3D Secure authentication could not be completed. Please try again.'),
            '6', '7' => __('3D Secure system error. Please try again or use a different card.'),
            '8' => __('Your card does not support 3D Secure. Please contact your bank.'),
            default => null,
        };

        if ($byMdStatus) {
            return $byMdStatus;
        }

        if ($rawErrMsg) {
            $clean = preg_replace('#https?://\S+#i', '', $rawErrMsg);
            $clean = trim(preg_replace('/\s+/', ' ', $clean));

            if ($clean) {
                return $clean;
            }
        }

        return __('Payment could not be completed. Please try again.');
    }

    protected function numericToIsoCurrency(string $code): string
    {
        return match ($code) {
            '949' => 'TRY',
            '840' => 'USD',
            '978' => 'EUR',
            '826' => 'GBP',
            '392' => 'JPY',
            '643' => 'RUB',
            default => 'TRY',
        };
    }

    protected function redirectToSuccess(?string $checkoutToken)
    {
        if (is_plugin_active('ecommerce')) {
            $token = $checkoutToken ?: OrderHelper::getOrderSessionToken();

            return redirect()->to(route('public.checkout.success', $token));
        }

        return redirect()->to(url('/'));
    }

    protected function redirectToFail(?string $checkoutToken, string $message)
    {
        if (is_plugin_active('ecommerce') && $checkoutToken) {
            return redirect()
                ->to(route('public.checkout.information', [
                    $checkoutToken,
                    'error' => 1,
                    'error_type' => 'payment',
                    'error_message' => $message,
                ]))
                ->with('error_msg', $message);
        }

        return redirect()
            ->to(url('/'))
            ->with('error_msg', $message);
    }
}
