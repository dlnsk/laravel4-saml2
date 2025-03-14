<?php
namespace Pitbulk\Saml2;

use Config;
use Route;
use URL;
use Illuminate\Support\ServiceProvider;
use OneLogin_Saml2_Utils;

class Saml2ServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('pitbulk/laravel4-saml2');

        $samlSettings = Config::get('laravel4-saml2::saml_settings');
        if (isset($samlSettings['lavarel']) &&
            isset($samlSettings['lavarel']['useRoutes']) &&
            $samlSettings['lavarel']['useRoutes']
        ) {
            include __DIR__ . '/../../routes.php';
        }

        if (isset($samlSettings['lavarel']['proxyVars']) 
                && $samlSettings['lavarel']['proxyVars']) {
            OneLogin_Saml2_Utils::setProxyVars(true);
        }
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app['saml2auth'] = $this->app->share(function ($app) {
            $samlSettings = Config::get('laravel4-saml2::saml_settings');

            if (empty($samlSettings['sp']['entityId'])) {
                $samlSettings['sp']['entityId'] = URL::route('saml_metadata');
            }
            if (empty($samlSettings['sp']['assertionConsumerService']['url'])) {
                $samlSettings['sp']['assertionConsumerService']['url'] = URL::route('saml_acs');
            }
            if (empty($samlSettings['sp']['singleLogoutService']['url'])) {
                $samlSettings['sp']['singleLogoutService']['url'] = URL::route('saml_sls');
            }

            return new \Pitbulk\Saml2\Saml2Auth($samlSettings);
        });

        $this->app->booting(function () {
            $loader = \Illuminate\Foundation\AliasLoader::getInstance();
            $loader->alias('Saml2Auth', 'Pitbulk\Saml2\Facades\Saml2Auth');
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array();
    }
}
