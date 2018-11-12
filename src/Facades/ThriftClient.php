<?php

namespace Lanffy\Thrift\Facades;

use Illuminate\Support\Facades\Facade;

/**
 */
class ThriftClient extends Facade
{

    /**
     * @return string
     */
    protected static function getFacadeAccessor ()
    {
        return \Lanffy\Thrift\Contracts\ThriftClient::class;
    }

}