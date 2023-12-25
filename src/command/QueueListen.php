<?php
declare (strict_types = 1);

namespace annon\queue\command;

use annon\queue\job\NSQJob;
use NSQClient\Access\Endpoint;
use NSQClient\Contract\Message;
use NSQClient\Queue;
use think\console\Command;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;

/**
 * 监听邮件发送队列
 * Class WatchMail
 * @package app\command
 */
class QueueListen extends Command
{
    /**
     * 配置指令
     * @return void
     */
    protected function configure(): void
    {
        // 指令配置
        $this->setName('nsq:listen')
            ->addOption('topic', 't', Option::VALUE_OPTIONAL, 'The queue to listen on', null)
            ->addOption('channel', 'c', Option::VALUE_OPTIONAL, 'The channel to listen on', null)
            ->setDescription('to listen nsq queue');
    }

    /**
     * 执行指令
     * @param Input $input
     * @param Output $output
     * @return void
     */
    protected function execute(Input $input, Output $output): void
    {
        $output->writeln("start listen queue");
        $topic    = $input->getOption('topic') ?: $this->app->config->get('nsq.topic');
        $channel  = $input->getOption('channel') ?: $this->app->config->get('nsq.channel');
        $endpoint = $this->app->config->get('nsq.endpoint');

        $endpoint = new Endpoint($endpoint);
        Queue::subscribe(
            endpoint: $endpoint,
            topic: $topic,
            channel: $channel,
            processor: function (Message $message) use ($output) {
                $payload = json_decode($message->payload(), true);
                $action = $payload['action'];
                $data   = $payload['data'];
                $hasAt  = str_contains($action, '@');
                $hasSlash = str_contains($action, '\\');

                if ($this->app->config->get('app_debug')) {
                    $output->writeln(sprintf("GOT %s %s %s %s",
                        $message->id(),
                        $message->payload(),
                        $message->attempts(),
                        $message->timestamp()
                    ));
                }

                // 实例化job
                $bridge = new NSQJob(
                    $message,
                    (string)$message->id(),
                    $message->payload(),
                    $message->attempts()
                );

                // 获取类名
                $path = $hasAt ? strstr($action, '@', true) : $action;
                // 获取方法名
                $method = $hasAt ? substr(strstr($action, '@'), 1) : 'fire';
                // 获取命名空间
                $class = $hasSlash ? $path : "app\\job\\$path";
                // 实例化类
                $job = new $class();
                // 执行方法
                $job->$method($bridge, $data);
            }
        );
    }
}
