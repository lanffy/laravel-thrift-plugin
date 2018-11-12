<?php

namespace Lanffy\Thrift;

use Illuminate\Contracts\Config\Repository;
use Lanffy\Thrift\Contracts\ThriftServer;
use Thrift\Exception\TApplicationException;
use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Protocol\TProtocol;
use Thrift\TMultiplexedProcessor;
use Thrift\Transport\TTransport;
use Thrift\Type\TMessageType;

/**
 * 服务端封装
 */
class ThriftServerImpl implements ThriftServer
{
    /**
     * @var Repository
     */
    private $config;

    /**
     * 总的 Processor
     *
     * @var TMultiplexedProcessor
     */
    private $mProcessor = null;

    /**
     * @var string
     */
    private $protocolClass;

    /**
     * ThriftServiceImpl constructor.
     *
     * @param Repository $config
     */
    public function __construct (Repository $config)
    {
        $this->config = $config;
        $this->protocolClass = $this->config->get("thrift.protocol", TBinaryProtocolAccelerated::class);
    }

    /**
     * 注册所有配置文件中的服务
     */
    protected function registerAll ()
    {
        if (!is_null($this->mProcessor)) {
            return;
        }
        $providers = $this->config->get("thrift.providers");
        $this->mProcessor = new TMultiplexedProcessor();
        foreach ($providers as $provider) {
            if (is_string($provider)) {
                $this->register($provider);
            } elseif (is_array($provider)) {
                $this->register($provider[0], $provider[1]);
            } else {
                throw new \InvalidArgumentException("provider must be name or array. now it's " . var_export($provider));
            }
        }
    }

    /**
     * 注册 name 的 handler 和 processor
     *
     * @param             $name
     * @param string|null $handlerClass
     * @param string|null $processorClass
     */
    public function register ($name, $handlerClass = null, $processorClass = null)
    {
        $className = str_replace(".", "\\", $name);
        if ($handlerClass === null) {
            $handlerClass = $className . "Handler";
        }
        if ($processorClass === null) {
            $processorClass = $className . "Processor";
        }
        $handler = new $handlerClass();
        $processor = new $processorClass($handler);
        $this->mProcessor->registerProcessor($name, $processor);
    }

    /**
     * 处理 RPC 请求
     * @param TTransport $inputTrans
     * @param TTransport $outputTrans
     * @throws \Thrift\Exception\TTransportException
     */
    public function process (TTransport $inputTrans, TTransport $outputTrans)
    {
        /* @var TProtocol $inputProto */
        $inputProto = new $this->protocolClass($inputTrans);
        /* @var TProtocol $outputProto */
        $outputProto = new $this->protocolClass($outputTrans);
        if (!$inputTrans->isOpen()) {
            $inputTrans->open();
        }
        if (!$outputTrans->isOpen()) {
            $outputTrans->open();
        }

        try {
            $this->registerAll();

            $this->mProcessor->process($inputProto, $outputProto);
        } catch (\Exception $e) {
            $appException = new TApplicationException(
                $e->getMessage() . PHP_EOL . $e->getFile() . ':' . $e->getLine(),
                TApplicationException::UNKNOWN
            );
            $outputProto->writeMessageBegin(__METHOD__, TMessageType::EXCEPTION, 0);
            $appException->write($outputProto);
            $outputProto->writeMessageEnd();
            $outputProto->getTransport()->flush();
            // TODO \Log::error($e);
        }
    }
}