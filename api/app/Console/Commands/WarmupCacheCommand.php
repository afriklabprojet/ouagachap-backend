<?php

namespace App\Console\Commands;

use App\Services\CacheService;
use Illuminate\Console\Command;

class WarmupCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'cache:warmup 
                            {--clear : Clear cache before warming up}';

    /**
     * The console command description.
     */
    protected $description = 'Préchauffer le cache applicatif (zones, FAQs, config)';

    /**
     * Execute the console command.
     */
    public function handle(CacheService $cacheService): int
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║    Préchauffage du Cache OUAGA CHAP      ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->info('');

        if ($this->option('clear')) {
            $this->warn('Vidage du cache...');
            $cacheService->clearAll();
            $this->info('  ✓ Cache vidé');
        }

        $this->info('Préchauffage en cours...');

        $start = microtime(true);

        // Zones
        $zones = $cacheService->getActiveZones();
        $this->info("  ✓ Zones actives: {$zones->count()}");

        // FAQs
        $faqs = $cacheService->getActiveFaqs();
        $this->info("  ✓ FAQs actives: {$faqs->count()}");

        // Config
        $config = $cacheService->getGeneralConfig();
        $this->info("  ✓ Configuration générale chargée");

        // Contact
        $contact = $cacheService->getContactInfo();
        $this->info("  ✓ Informations de contact chargées");

        $duration = round((microtime(true) - $start) * 1000, 2);

        $this->info('');
        $this->info("✅ Cache préchauffé en {$duration}ms");
        $this->info('');

        return Command::SUCCESS;
    }
}
