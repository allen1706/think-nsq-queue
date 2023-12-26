<?php
declare(strict_types=1);

namespace annon\queue;

use annon\queue\request\Http;

class Queue
{
    /**
     * @param string $job 任务方法
     * @param array|string $data 任务数据
     * @param string|null $queue 队列名称
     * @param string|null $channel 队列通道
     * @return bool
     */
    public static function push(
        string       $job,
        array|string $data = '',
        string       $queue = null,
        string       $channel = null
    ): bool
    {
        // 将任务推送到队列
        $topic   = $queue ?: config('nsq.topic');
        $channel = $channel ?: config('nsq.channel');
        $nsqd    = config('nsq.nsqd');
        // 直接使用http推送到nsqd
        $arr = [
            'action' => $job,
            'data'   => $data,
        ];
        $url = sprintf("%s/pub?topic=%s&channel=%s", $nsqd, $topic, $channel);
        $res = Http::post($url, $arr);

        if ($res === 'OK') {
            return true;
        }
        return false;
    }

    /**
     * @param int $delay 延迟时间，单位毫秒
     * @param string $job 任务方法
     * @param array|string $data 任务数据
     * @param string|null $queue 队列名称
     * @param string|null $channel 队列通道
     * @return bool
     */
    public static function later(
        int          $delay,
        string       $job,
        array|string $data = '',
        string       $queue = null,
        string       $channel = null
    ): bool
    {
        // 将任务推送到队列
        // 直接使用http推送到nsqd

        // 将任务推送到队列
        $topic   = $queue ?: config('nsq.topic');
        $channel = $channel ?: config('nsq.channel');
        $nsqd    = config('nsq.nsqd');
        // 直接使用http推送到nsqd
        $arr = [
            'action' => $job,
            'data'   => $data,
        ];
        $url = sprintf("%s/pub?topic=%s&channel=%s&defer=%s", $nsqd, $topic, $channel, $delay);
        $res = Http::post($url, $arr);

        if ($res === 'OK') {
            return true;
        }
        return false;
    }
}