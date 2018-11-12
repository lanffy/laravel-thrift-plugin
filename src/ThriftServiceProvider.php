<?php

namespace Lanffy\Thrift;

use Illuminate\Support\ServiceProvider;
use Lanffy\Thrift\Contracts\ThriftClient;
use Lanffy\Thrift\Contracts\ThriftServer;

/**
 * 实例
 */
class ThriftServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register ()
    {
        $this->app->singleton(ThriftServer::class, ThriftServerImpl::class);
        $this->app->singleton(ThriftClient::class, ThriftClientImpl::class);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides ()
    {
        return [
            ThriftServer::class,
            ThriftClient::class,
        ];
    }
}