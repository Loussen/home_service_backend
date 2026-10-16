<?php

namespace App\Console\Commands;

use App\Services\AppStringSyncService;
use Illuminate\Console\Command;

class SyncAppStringsCommand extends Command
{
    protected $signature = 'app-strings:sync {--overwrite : Replace all DB values from PHP files}';

    protected $description = 'Sync config/app_strings/*.php into app_strings table (fixes EN/RU copied from AZ)';

    public function handle(AppStringSyncService $sync): int
    {
        $overwrite = (bool) $this->option('overwrite');
        $result = $sync->syncFromFiles(overwriteExisting: $overwrite);

        $this->info(sprintf(
            'created=%d updated=%d fixed_copies=%d total=%d',
            $result['created'],
            $result['updated'],
            $result['fixed_copies'],
            $result['total'],
        ));

        return self::SUCCESS;
    }
}
