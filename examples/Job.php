<?php
declare(strict_types=1);

namespace annon\examples;

use annon\queue\job\NSQJob;

class Job1
{
    public function fire(NSQJob $job, $data)
    {
        dump($job->getJobId(), $data);
        $job->delete();
    }

    public function failed($data)
    {
        dump($data);
    }
}