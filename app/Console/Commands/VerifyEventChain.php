<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\EventHasher;
use Illuminate\Console\Command;

final class VerifyEventChain extends Command
{
    protected $signature = 'events:verify-chain
                            {--fix : Hash any unhashed events before verification}';

    protected $description = 'Verify the integrity of the event chain (blockchain-style verification)';

    public function handle(EventHasher $hasher): int
    {
        $this->info('Event Chain Verification');
        $this->info('========================');
        $this->newLine();

        // Optionally hash unhashed events first
        if ($this->option('fix')) {
            $this->info('Hashing any unhashed events...');
            $count = $hasher->hashAllUnhashed();
            $this->info(sprintf('Hashed %d events.', $count));
            $this->newLine();
        }

        $this->info('Verifying chain integrity...');
        $this->newLine();

        $brokenLinks = $hasher->verifyChain();

        if ($brokenLinks === []) {
            $this->info('✓ Chain integrity verified! All events are valid and untampered.');

            return Command::SUCCESS;
        }

        $this->error('✗ Chain integrity FAILED! Found '.count($brokenLinks).' issue(s):');
        $this->newLine();

        foreach ($brokenLinks as $link) {
            $this->error('Event ID: '.$link['event_id']);
            $this->error('  Error: '.$link['error']);
            $this->error('  Expected: '.$link['expected']);
            $this->error('  Actual: '.$link['actual']);
            $this->newLine();
        }

        $this->warn('WARNING: The event store may have been tampered with!');

        return Command::FAILURE;
    }
}
