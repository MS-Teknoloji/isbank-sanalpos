<?php

namespace GrisePet\IsbankSanalpos\Forms;

use Botble\Base\Facades\BaseHelper;
use Botble\Base\Forms\FieldOptions\OnOffFieldOption;
use Botble\Base\Forms\FieldOptions\TextFieldOption;
use Botble\Base\Forms\Fields\OnOffCheckboxField;
use Botble\Base\Forms\Fields\TextField;
use Botble\Payment\Forms\PaymentMethodForm;

class IsbankSanalposPaymentMethodForm extends PaymentMethodForm
{
    public function setup(): void
    {
        parent::setup();

        $this
            ->paymentId(ISBANK_SANALPOS_PAYMENT_METHOD_NAME)
            ->paymentName('Isbank Sanalpos')
            ->paymentDescription(__('Customer pays by Visa / MasterCard via :name 3D Secure', ['name' => 'Isbank Sanalpos']))
            ->paymentLogo(url('vendor/core/plugins/isbank-sanalpos/images/isbank.png'))
            ->paymentUrl('https://www.isbank.com.tr/')
            ->paymentInstructions(view('plugins/isbank-sanalpos::instructions')->render())
            ->add(
                get_payment_setting_key('client_id', ISBANK_SANALPOS_PAYMENT_METHOD_NAME),
                TextField::class,
                TextFieldOption::make()
                    ->label(trans('plugins/isbank-sanalpos::isbank-sanalpos.client_id'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '*******************************' : get_payment_setting('client_id', ISBANK_SANALPOS_PAYMENT_METHOD_NAME))
                    ->toArray()
            )
            ->add(
                get_payment_setting_key('store_key', ISBANK_SANALPOS_PAYMENT_METHOD_NAME),
                'password',
                TextFieldOption::make()
                    ->label(trans('plugins/isbank-sanalpos::isbank-sanalpos.store_key'))
                    ->value(BaseHelper::hasDemoModeEnabled() ? '*******************************' : get_payment_setting('store_key', ISBANK_SANALPOS_PAYMENT_METHOD_NAME))
            )
            ->add(
                get_payment_setting_key('sandbox', ISBANK_SANALPOS_PAYMENT_METHOD_NAME),
                OnOffCheckboxField::class,
                OnOffFieldOption::make()
                    ->label(trans('plugins/isbank-sanalpos::isbank-sanalpos.sandbox'))
                    ->disabled(BaseHelper::hasDemoModeEnabled())
                    ->value(BaseHelper::hasDemoModeEnabled() ? true : get_payment_setting('sandbox', ISBANK_SANALPOS_PAYMENT_METHOD_NAME))
            );
    }
}
