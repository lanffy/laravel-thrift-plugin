<?php

namespace Lanffy\Thrift\Contracts;

use Thrift\Transport\TTransport;

/**
 */
interface ThriftServer
{

    /**
     * @param      $name
     * @param null $handlerClass
     * @param null $processorClass
     * @return mixed
     */
    public function register($name, $handlerClass = null, $processorClass = null);

    /**
     * @param \Thrift\Transport\TTransport $inputTrans
     * @param \Thrift\Transport\TTransport $outputTrans
     * @return mixed
     */
    public function process(TTransport $inputTrans, TTransport $outputTrans);

}