<?php

namespace GrisePet\IsbankSanalpos\Services\Abstracts;

use Botble\Payment\Models\Payment;
use Botble\Payment\Services\Traits\PaymentErrorTrait;
use Botble\Support\Services\ProduceServiceInterface;
use Exception;
use Illuminate\Http\Request;

abstract class IsbankSanalposPaymentAbstract implements ProduceServiceInterface
{
    use PaymentErrorTrait;

    protected bool $supportRefundOnline;

    public function __construct()
    {
        $this->supportRefundOnline = true;
    }

    public function getSupportRefundOnline(): bool
    {
        return $this->supportRefundOnline;
    }

    public function getPaymentDetails($paymentId)
    {
        try {
            $response = Payment::query()->where('charge_id', '=', $paymentId)->first();
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }

        return $response;
    }

    public function refundOrder($paymentId, $amount, array $options = []): array
    {
        // TODO: Isbank Sanalpos refund API chaqiruvi shu yerda amalga oshiriladi
        // Isbank'ning haqiqiy API kodlari yuborilgandan so'ng to'ldiriladi.
        return [
            'error' => true,
            'message' => 'Refund not implemented yet.',
        ];
    }

    public function getRefundDetails($refundId): void
    {
    }

    public function execute(Request $request): bool
    {
        try {
            return $this->makePayment($request);
        } catch (Exception $exception) {
            $this->setErrorMessageAndLogging($exception, 1);

            return false;
        }
    }

    abstract public function makePayment(Request $request);

    abstract public function afterMakePayment(Request $request);
}
