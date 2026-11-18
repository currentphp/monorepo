<?php

declare(strict_types=1);

namespace Current\Console;

/**
 * Lists registered menus, or one menu, as trees of entries.
 *
 * @internal Run as `php artisan current:menus`; the command name and output are the supported interface.
 *
 * @phpstan-import-type MenuNode from Inspector
 */
final class MenusCommand extends InspectionCommand
{
    /** @var string */
    protected $signature = 'current:menus
        {name? : Only this menu}
        {--json : Print the menus as JSON}';

    /** @var string */
    protected $description = 'List registered Current menus as trees';

    public function handle(Inspector $inspector): int
    {
        if ($this->reportProblems($inspector)) {
            return self::FAILURE;
        }

        $names = $inspector->menuNames();
        $name = $this->argument('name');
        if (is_string($name)) {
            if (! in_array($name, $names, true)) {
                $this->components->error("Unknown menu '{$name}'.");

                return self::FAILURE;
            }
            $names = [$name];
        }

        $menus = array_map($inspector->menu(...), $names);
        if ($this->printedAsJsonOrEmpty($menus, 'No menus are registered.')) {
            return self::SUCCESS;
        }

        foreach ($menus as $menu) {
            $source = $menu['kind'] === 'derived'
                ? 'derived from '.implode(', ', $menu['roots']).($menu['except'] === [] ? '' : '; except '.implode(', ', $menu['except']))
                    .($menu['exceptWhen'] === [] ? '' : '; except when: '.implode(', ', $menu['exceptWhen']))
                : 'defined';
            $this->newLine();
            $this->line("  <options=bold>{$menu['name']}</> <fg=gray>({$source})</>");
            $this->render($menu['entries'], 1, $menu['kind'] === 'derived' ? 'hidden when' : 'visible when');
        }
        $this->newLine();

        return self::SUCCESS;
    }

    /** @param list<mixed> $entries */
    private function render(array $entries, int $depth, string $rule): void
    {
        /** @var list<MenuNode> $entries */
        foreach ($entries as $entry) {
            $details = array_filter([
                $entry['label'] ?? '(dynamic)',
                $entry['kind'] === 'group' ? 'group' : null,
                $entry['icon'] === null ? null : 'icon '.$entry['icon'],
                $entry['visibility'] ? $rule : null,
            ]);
            // Written directly: the two-column component trims the indentation.
            $this->line('  '.str_repeat('  ', $depth).$entry['id'].'  <fg=gray>'.implode(' · ', $details).'</>');
            $this->render($entry['children'], $depth + 1, $rule);
        }
    }
}
