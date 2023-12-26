<?php
declare(strict_types=1);

namespace annon\queue\job;

use NSQClient\Contract\Message;

class NSQJob
{
    public function __construct(
        protected Message &$message,
        protected string $jobId,
        protected string $rawBody,
        protected int $attempts,
    ) {}

    public function delete(): void
    {
        $this->message->done();
    }

    public function release($delay = 0): void
    {
        $this->message->delay($delay);
    }

    public function getJobId(): string
    {
        return $this->jobId;
    }

    public function attempts(): int
    {
        return $this->attempts;
    }

    public function getRawBody(): string
    {
        return $this->rawBody;
    }
}