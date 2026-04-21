<?php

namespace GrisePet\IsbankSanalpos;

use Botble\PluginManagement\Abstracts\PluginOperationAbstract;
use Botble\Setting\Facades\Setting;

class Plugin extends PluginOperationAbstract
{
    public static function remove(): void
    {
        Setting::delete([
            'payment_isbank_sanalpos_name',
            'payment_isbank_sanalpos_description',
            'payment_isbank_sanalpos_client_id',
            'payment_isbank_sanalpos_store_key',
            'payment_isbank_sanalpos_sandbox',
            'payment_isbank_sanalpos_status',
        ]);
    }
}
