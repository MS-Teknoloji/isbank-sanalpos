<?php

namespace GrisePet\IsbankSanalpos\Providers;

use Botble\Base\Traits\LoadAndPublishDataTrait;
use Illuminate\Support\ServiceProvider;

class IsbankSanalposServiceProvider extends ServiceProvider
{
    use LoadAndPublishDataTrait;

    public function boot(): void
    {
        if (! is_plugin_active('payment')) {
            return;
        }

        $this->setNamespace('plugins/isbank-sanalpos')
            ->loadHelpers()
            ->loadAndPublishViews()
            ->publishAssets()
            ->loadAndPublishTranslations()
            ->loadRoutes();

        $this->app->register(HookServiceProvider::class);
    }
}
