<?php
declare(strict_types=1);

namespace annon\queue;

use annon\queue\command\QueueListen;

class Service extends \think\Service
{
    public function register(): void
    {
        $this->app->bind('nsq', Queue::class);
    }

    public function boot(): void
    {
        $this->commands([
            QueueListen::class,
        ]);
    }
}