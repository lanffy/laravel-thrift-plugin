<?php

namespace Lanffy\Thrift;

use Illuminate\Contracts\Config\Repository;
use Lanffy\Thrift\Contracts\ThriftClient;
use Thrift\Protocol\TBinaryProtocolAccelerated;
use Thrift\Protocol\TMultiplexedProtocol;
use Thrift\Protocol\TProtocol;
use Thrift\Transport\TCurlClient;

/**
 * 客户端封装
 */
class ThriftClientImpl implements ThriftClient
{
    /**
     * @var \Illuminate\Contracts\Config\Repository
     */
    private $config;

    /**
     * @var mixed
     */
    private $protocolClass;

    /**
     * @var mixed
     */
    private $transportClass;

    /**
     * @var
     */
    private $providers;

    /**
     * ThriftClientImpl constructor.
     *
     * @param Repository $config
     */
    public function __construct (Repository $config)
    {
        $this->config = $config;
        $this->protocolClass = $this->config->get("thrift.protocol", TBinaryProtocolAccelerated::class);
        $this->transportClass = $this->config->get("thrift.transport", TCurlClient::class);
        $client = $this->config->get('thrift.client');
        foreach ($client as $host => $service) {
            if (empty($host)) {
                throw new \InvalidArgumentException(
                    'The endpoint of ' . (is_array($service) ? implode($service, ', ') : $service) . ' doesn\'t exist!'
                );
            }
            $info = parse_url($host);
            $info = [
                'host' => $info['host'],
                'port' => $info['port'] ?? 80,
                'uri' => $info['path'] ?? '/',
                'scheme' => $info['scheme'] ?? 'http',
            ];
            $info['port'] = intval($info['port']);
            if (is_string($service)) {
                $this->providers[$service] = $info;
            } elseif (is_array($service)) {
                foreach ($service as $serviceName) {
                    /** @var string $serviceName */
                    $this->providers[$serviceName] = $info;
                }
            }
        }
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function with ($name)
    {
        $info = $this->providers[$name] ?? [];
        if (empty($info)) {
            throw new \InvalidArgumentException(
                "service name:{$name} doesn\'t deployed in thrift.client!"
            );
        }
        $transport = new $this->transportClass($info['host'], $info['port'], $info['uri'], $info['scheme']);
        /** @var TProtocol $protocol */
        $protocol = new $this->protocolClass($transport);
        $protocol = new TMultiplexedProtocol($protocol, $name);
        $client_class = str_replace(".", "\\", $name) . "Client";
        $client = new $client_class($protocol);
        return $client;
    }
}