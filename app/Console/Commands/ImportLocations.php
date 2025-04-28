<?php

namespace App\Console\Commands;

use App\Services\MrMrsSmithImporter;
use App\Services\SelectRegistryImporter;
use App\Services\SLHImporter;
use Illuminate\Console\Command;

class ImportLocations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:import-locations';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports potential venue locations from all sources';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // (new SLHImporter)->import();
        // (new SelectRegistryImporter)->import();
        (new MrMrsSmithImporter)->import();
    }
}
