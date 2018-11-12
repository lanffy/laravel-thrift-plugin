<?php

namespace Lanffy\Thrift\Middleware;

use Closure;
use Illuminate\Http\Response;
use Lanffy\Thrift\Contracts\ThriftServer;
use Thrift\Transport\TMemoryBuffer;

/**
 * 接受rpc请求
 */
class ThriftServerMiddleware
{

    const THRIFT_CONTENT_TYPE = 'application/x-thrift';

    /**
     * @var \Lanffy\Thrift\Contracts\ThriftServer
     */
    public $thriftServer;

    /**
     * ThriftServerMiddleware constructor.
     * @param ThriftServer $thriftServer
     */
    public function __construct (ThriftServer $thriftServer)
    {
        $this->thriftServer = $thriftServer;
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return Response
     */
    protected function process ($request)
    {
        $input_trans = new TMemoryBuffer($request->getContent());
        $output_trans = new TMemoryBuffer();

        $input_trans->open();
        $this->thriftServer->process($input_trans, $output_trans);
        $buffer = $output_trans->getBuffer();
        $output_trans->close();
        return (new Response($buffer, 200))->header('Content-Type', self::THRIFT_CONTENT_TYPE);
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure                 $next
     * @return mixed
     */
    public function handle ($request, Closure $next)
    {
        if ($request->is('rpc') && self::THRIFT_CONTENT_TYPE == $request->header('CONTENT_TYPE')) {
            return $this->process($request);
        } else {
            return $next($request);
        }
    }

}