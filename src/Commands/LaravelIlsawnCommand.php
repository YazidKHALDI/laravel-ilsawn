<?php

namespace ilsawn\LaravelIlsawn\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use ilsawn\LaravelIlsawn\LaravelIlsawn;

class LaravelIlsawnCommand extends Command
{
    protected $signature = 'ilsawn:generate
                            {--scan              : Scan source files and add missing translation keys to the CSV}
                            {--cleanup           : Remove translation keys from the CSV that are no longer used}
                            {--remove-duplicates : Remove keys from the CSV that already exist in Laravel\'s own lang files}
                            {--dry-run           : Preview what would change without modifying any files}';

    protected $description = 'Generate JSON locale files from the CSV, with optional scan, cleanup, and deduplication';

    public function __construct(private readonly LaravelIlsawn $ilsawn)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');

        if (! File::exists($this->ilsawn->csvPath())) {
            $this->error('CSV file not found: ' . $this->ilsawn->csvPath());

            return self::FAILURE;
        }

        if (! $isDryRun && config('ilsawn.backup', true)) {
            $this->info('Backup created: ' . $this->ilsawn->backupCsv());
            $this->ilsawn->pruneBackups((int) config('ilsawn.backup_limit', 5));
        }

        $csvData       = $this->ilsawn->loadCsv();
        $originalCount = count($csvData);

        if ($this->option('remove-duplicates')) {
            $this->info("Checking for keys that duplicate Laravel's own lang files...");
            $duplicates = $this->ilsawn->findDuplicatesInLangFiles($csvData);

            if (! empty($duplicates)) {
                $this->warn('Found ' . count($duplicates) . ' duplicate key(s).');

                if ($isDryRun) {
                    $this->table(
                        ['Key', 'Conflicts with'],
                        array_map(fn ($k, $f) => [$k, $f], array_keys($duplicates), $duplicates)
                    );
                } elseif ($this->confirm('Remove these duplicate keys from the CSV?')) {
                    $csvData = $this->ilsawn->removeKeys($csvData, array_keys($duplicates));
                    $this->info('Duplicate keys removed.');
                }
            } else {
                $this->info('No duplicate keys found.');
            }
        }

        if ($this->option('scan')) {
            $this->info('Scanning source files for missing translation keys...');

            ['missing' => $missing, 'skipped' => $skipped] = $this->ilsawn->scanForNewKeys($csvData);

            foreach ($skipped as $key => $file) {
                $this->line("  Skipping '{$key}' — already handled by {$file}.");
            }

            if (! empty($missing)) {
                $this->info('Found ' . count($missing) . ' missing key(s).');

                if ($isDryRun) {
                    $this->table(['Missing Key'], array_map(fn ($k) => [$k], $missing));
                } else {
                    $csvData = $this->ilsawn->addMissingKeys($csvData, $missing);
                    $this->info('Missing keys added to CSV data.');
                }
            } else {
                $this->info('No missing translations found.');
            }
        }

        if ($this->option('cleanup')) {
            $this->info('Scanning for unused translation keys...');
            $unused = $this->ilsawn->findUnusedKeys($csvData);

            if (! empty($unused)) {
                $this->warn('Found ' . count($unused) . ' unused key(s).');

                if ($isDryRun) {
                    $this->table(['Unused Key'], array_map(fn ($k) => [$k], $unused));
                } elseif ($this->confirm('Remove these unused keys from the CSV?')) {
                    $csvData = $this->ilsawn->removeKeys($csvData, $unused);
                    $this->info('Unused keys removed.');
                }
            } else {
                $this->info('No unused translations found.');
            }
        }

        if ($isDryRun) {
            $this->info('Dry run complete — no files were modified.');

            return self::SUCCESS;
        }

        if (count($csvData) !== $originalCount) {
            $this->ilsawn->saveCsv($csvData);
            $this->info('CSV file updated.');
        }

        foreach ($this->ilsawn->generateJsonFiles($csvData) as $filePath) {
            $this->info("Generated: {$filePath}");
        }

        $this->info('JSON locale files generated successfully.');

        try {
            Artisan::call('optimize:clear');
            $this->info('Cache cleared.');
        } catch (\Throwable) {
            // cache:clear may fail if the database cache table doesn't exist yet
        }

        return self::SUCCESS;
    }
}
