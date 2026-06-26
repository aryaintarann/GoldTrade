<?php

use App\Jobs\FetchGoldPriceJob;
use App\Jobs\UpdateSignalOutcomesJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new FetchGoldPriceJob)->everyMinute();
Schedule::job(new UpdateSignalOutcomesJob)->everyFiveMinutes();
