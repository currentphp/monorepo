<?php

declare(strict_types=1);

namespace Current\Console;

/**
 * Lists registered actions by subject kind and page, without running callbacks.
 *
 * @internal Run as `php artisan current:actions`; the command name and output are the supported interface.
 *
 * @phpstan-import-type ActionDescription from Inspector
 */
final class ActionsCommand extends InspectionCommand
{
    /** @var string */
    protected $signature = 'current:actions {--json : Print the actions as JSON}';

    /** @var string */
    protected $description = 'List registered Current actions by subject kind and page';

    public function handle(Inspector $inspector): int
    {
        if ($this->reportProblems($inspector)) {
            return self::FAILURE;
        }

        $actions = $inspector->actions();
        if ($this->printedAsJsonOrEmpty($actions, 'No actions are registered.')) {
            return self::SUCCESS;
        }

        /** @var array<string, list<ActionDescription>> $groups */
        $groups = [];
        foreach ($actions as $action) {
            $owner = $action['subject'] === null ? 'page '.$action['page'] : 'subject '.$action['subject'];
            $groups[$owner][] = $action;
        }

        foreach ($groups as $owner => $group) {
            $this->newLine();
            $this->line("  <options=bold>{$owner}</>");
            foreach ($group as $action) {
                $details = array_filter([
                    $action['label'] ?? '(dynamic)',
                    $action['method'].' '.$action['route'],
                    $action['icon'] === null ? null : 'icon '.$action['icon'],
                    $action['ability'] === null ? null : 'can '.$action['ability'],
                    $action['visibility'] ? 'visible when' : null,
                    $action['enabled'] ? 'enabled when' : null,
                    $action['destructive'] ? 'destructive' : null,
                    $action['confirm'] ? 'confirm' : null,
                ]);
                $this->line('    '.$action['name'].'  <fg=gray>'.implode(' · ', $details).'</>');
            }
        }
        $this->newLine();

        return self::SUCCESS;
    }
}
