<?php

namespace MsTeknoloji\IsbankSanalpos\Services\Gateways;

use MsTeknoloji\IsbankSanalpos\Models\Currency;
use MsTeknoloji\IsbankSanalpos\Services\Abstracts\IsbankSanalposPaymentAbstract;
use Illuminate\Http\Request;

class IsbankSanalposPaymentService extends IsbankSanalposPaymentAbstract
{
    public function makePayment(Request $request)
    {
    }

    public function afterMakePayment(Request $request)
    {
    }

    public function supportedCurrencyCodes(): array
    {
        return [
            Currency::TRY,
            Currency::TL,
            Currency::USD,
            Currency::EUR,
            Currency::GBP,
            Currency::JPY,
            Currency::RUB,
        ];
    }
}
