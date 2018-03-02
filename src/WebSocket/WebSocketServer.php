<?php
/**
 * Created by PhpStorm.
 * User: sl
 * Date: 2018/3/2
 * Time: 下午3:54
 */

namespace Swoft\Websocket\Server\WebSocket;

use Swoft\Http\Server\Http\HttpServer;
use Swoft\Websocket\Server\Bootstrap\SwooleEvent;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class WebSocketServer extends HttpServer
{
    /**
     * @var WebSocketServer
     */
    public $server;


    public $wsSetting = [];

    /**
     * @return array
     */
    public function getWsSetting(): array
    {
        return $this->wsSetting;
    }

    /**
     * @param array $wsSetting
     */
    public function setWsSetting(array $wsSetting): void
    {
        $this->wsSetting = $wsSetting;
    }


    /**
     * @var \Swoole\Server::$port tcp port
     */
    protected $listen;

    /**
     * Start Server
     *
     * @throws \Swoft\Exception\RuntimeException
     */
    public function start()
    {
        $this->server = new Server($this->wsSetting['host'], $this->wsSetting['port'], $this->wsSetting['model'], $this->wsSetting['type']);
        // Bind event callback
        $this->server->set($this->setting);
        $this->server->on(SwooleEvent::ON_START, [$this, 'onStart']);
        $this->server->on(SwooleEvent::ON_WORKER_START, [$this, 'onWorkerStart']);
        $this->server->on(SwooleEvent::ON_MANAGER_START, [$this, 'onManagerStart']);
        $this->server->on(SwooleEvent::ON_OPEN,[$this,'onOpen']);
        $this->server->on(SwooleEvent::ON_REQUEST, [$this, 'onRequest']);
        $this->server->on(SwooleEvent::ON_MESSAGE,[$this,"onMessage"]);
        $this->server->on(SwooleEvent::ON_PIPE_MESSAGE, [$this, 'onPipeMessage']);

        $this->registerSwooleServerEvents();
        $this->beforeServerStart();
        $this->server->start();
    }

    /**
     * @param Server $server
     * @param Request $request
     */
    public function onOpen(Server $server,Request $request){
        $server->push($request->fd,"hello");
    }

    /**
     * 客户端发送的ping帧不会触发onMessage，底层会自动回复pong包
     * onMessage回调必须被设置，未设置服务器将无法启动
     * @param Server $server
     * @param Frame $frame  是swoole_websocket_frame对象，包含了客户端发来的数据帧信息
     */
    public function onMessage(Server $server,Frame $frame){
        $server->push($frame->fd,"111");
    }

    /**
     * 自定义握手过程使用,使用后不触发 onPen
     * @param Request $request
     * @param Response $response
     * @return bool
     */
    public function onHandShake(Request $request,Response $response){
// print_r( $request->header );
        // if (如果不满足我某些自定义的需求条件，那么返回end输出，返回false，握手失败) {
        //    $response->end();
        //     return false;
        // }

        // websocket握手连接算法验证
        $secWebSocketKey = $request->header['sec-websocket-key'];
        $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
        if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
            $response->end();
            return false;
        }
        echo $request->header['sec-websocket-key'];
        $key = base64_encode(sha1(
            $request->header['sec-websocket-key'] . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11',
            true
        ));

        $headers = [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => $key,
            'Sec-WebSocket-Version' => '13',
        ];

        // WebSocket connection to 'ws://127.0.0.1:9502/'
        // failed: Error during WebSocket handshake:
        // Response must not include 'Sec-WebSocket-Protocol' header if not present in request: websocket
        if (isset($request->header['sec-websocket-protocol'])) {
            $headers['Sec-WebSocket-Protocol'] = $request->header['sec-websocket-protocol'];
        }

        foreach ($headers as $key => $val) {
            $response->header($key, $val);
        }

        $response->status(101);
        $response->end();
        echo "connected!" . PHP_EOL;
        return true;
    }

}