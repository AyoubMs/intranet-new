<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Redis;

class LoadDataFromDB implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 5;

    public $query;
    public $type;

    /**
     * Create a new job instance.
     */
    public function __construct($query, $type)
    {
        $this->query = $query;
        $this->type = $type;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty(array_diff(json_decode(Redis::get($this->type) ?? "[]"), $this->query->toArray()))) {
            Redis::set($this->type, $this->query);
        }
    }
}
