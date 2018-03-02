<?php
/**
 * Created by PhpStorm.
 * User: sl
 * Date: 2018/3/2
 * Time: 下午4:14
 */

namespace Swoft\Websocket\Server\Bootstrap;


class SwooleEvent extends \Swoft\Bootstrap\SwooleEvent
{
    /**
     * 自定义握手过程使用,使用后不触发 onPen
     */
    const ON_HAND_SHAKE = "handshake";

    const ON_OPEN = "onOpen";

    const ON_MESSAGE = "onMessage";
}