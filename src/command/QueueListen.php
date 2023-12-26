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
        $topic     = $input->getOption('topic') ?: $this->app->config->get('nsq.topic');
        $channel   = $input->getOption('channel') ?: $this->app->config->get('nsq.channel');
        $endpoint  = $this->app->config->get('nsq.endpoint');
		$debug     = $this->app->env->get('APP_DEBUG', false);
        $max_tries = $this->app->config->get('nsq.max_tries', 3);

        $endpoint = new Endpoint($endpoint);
        Queue::subscribe(
            endpoint: $endpoint,
            topic: $topic,
            channel: $channel,
            processor: function (Message $message) use ($output, $debug, $max_tries) {
                $payload = json_decode($message->payload(), true);
                $action = $payload['action'];
                $data   = $payload['data'];
                $hasAt  = str_contains($action, '@');
                $hasSlash = str_contains($action, '\\');

                if ($debug) {
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
                // 任务执行成功需要手动删除
                // 如果任务达到最大重试次数依旧失败
                if ($bridge->attempts() > $max_tries) {
                    // 判断方法是否存在
                    if (method_exists($job, 'failed')) {
                        $job->failed($data);
                    } else {
                        $output->writeln(sprintf("Job %s has failed", $message->id()));
                    }
                }
            }
        );
    }
}
