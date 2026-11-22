<?php

declare(strict_types=1);

/**
 * Every public class, method, property, and enum case in src that is not marked
 *
 * @internal. Changing this list changes the supported API: update the
 * CHANGELOG, and flag removals and signature changes as breaking.
 */
const SUPPORTED_API = [
    'Current\Actions\Action',
    'Current\Actions\Action::$name',
    'Current\Actions\Action::can()',
    'Current\Actions\Action::confirm()',
    'Current\Actions\Action::destructive()',
    'Current\Actions\Action::enabledWhen()',
    'Current\Actions\Action::for()',
    'Current\Actions\Action::icon()',
    'Current\Actions\Action::label()',
    'Current\Actions\Action::make()',
    'Current\Actions\Action::on()',
    'Current\Actions\Action::order()',
    'Current\Actions\Action::route()',
    'Current\Actions\Action::visibleWhen()',
    'Current\Actions\ActionDefinition',
    'Current\Actions\ActionDefinition::$ability',
    'Current\Actions\ActionDefinition::$confirm',
    'Current\Actions\ActionDefinition::$destructive',
    'Current\Actions\ActionDefinition::$enabledWhen',
    'Current\Actions\ActionDefinition::$icon',
    'Current\Actions\ActionDefinition::$kind',
    'Current\Actions\ActionDefinition::$label',
    'Current\Actions\ActionDefinition::$method',
    'Current\Actions\ActionDefinition::$name',
    'Current\Actions\ActionDefinition::$order',
    'Current\Actions\ActionDefinition::$pageId',
    'Current\Actions\ActionDefinition::$routeName',
    'Current\Actions\ActionDefinition::$routeParameters',
    'Current\Actions\ActionDefinition::$visibleWhen',
    'Current\Actions\ActionDefinition::id()',
    'Current\Actions\ActionItem',
    'Current\Actions\ActionItem::$confirm',
    'Current\Actions\ActionItem::$current',
    'Current\Actions\ActionItem::$destructive',
    'Current\Actions\ActionItem::$enabled',
    'Current\Actions\ActionItem::$icon',
    'Current\Actions\ActionItem::$id',
    'Current\Actions\ActionItem::$label',
    'Current\Actions\ActionItem::$method',
    'Current\Actions\ActionItem::$name',
    'Current\Actions\ActionItem::$reason',
    'Current\Actions\ActionItem::$url',
    'Current\Actions\ActionRegistry',
    'Current\Actions\ActionRegistry::all()',
    'Current\Actions\ActionRegistry::register()',
    'Current\Actions\Actions',
    'Current\Actions\Actions::current()',
    'Current\Actions\Actions::for()',
    'Current\Context',
    'Current\Context::$page',
    'Current\Context::$user',
    'Current\Context::parameter()',
    'Current\Context::parameters()',
    'Current\CurrentServiceProvider',
    'Current\Exceptions\CurrentException',
    'Current\Exceptions\InvalidDefinition',
    'Current\Exceptions\ResolutionFailed',
    'Current\Experience',
    'Current\Experience::actions()',
    'Current\Experience::breadcrumbs()',
    'Current\Experience::context()',
    'Current\Experience::description()',
    'Current\Experience::forPage()',
    'Current\Experience::is()',
    'Current\Experience::menu()',
    'Current\Experience::page()',
    'Current\Experience::pageId()',
    'Current\Experience::subject()',
    'Current\Experience::subjectDescription()',
    'Current\Experience::subjectTitle()',
    'Current\Experience::title()',
    'Current\Experience::user()',
    'Current\Facades\Current',
    'Current\Navigation\Breadcrumb',
    'Current\Navigation\Breadcrumb::$current',
    'Current\Navigation\Breadcrumb::$icon',
    'Current\Navigation\Breadcrumb::$label',
    'Current\Navigation\Breadcrumb::$pageId',
    'Current\Navigation\Breadcrumb::$url',
    'Current\Navigation\DefinedMenu',
    'Current\Navigation\DerivedMenu',
    'Current\Navigation\DerivedMenu::except()',
    'Current\Navigation\DerivedMenu::exceptWhen()',
    'Current\Navigation\MenuEntry',
    'Current\Navigation\MenuEntry::$id',
    'Current\Navigation\MenuEntry::children()',
    'Current\Navigation\MenuEntry::group()',
    'Current\Navigation\MenuEntry::icon()',
    'Current\Navigation\MenuEntry::label()',
    'Current\Navigation\MenuEntry::page()',
    'Current\Navigation\MenuEntry::visibleWhen()',
    'Current\Navigation\MenuItem',
    'Current\Navigation\MenuItem::$active',
    'Current\Navigation\MenuItem::$children',
    'Current\Navigation\MenuItem::$current',
    'Current\Navigation\MenuItem::$icon',
    'Current\Navigation\MenuItem::$kind',
    'Current\Navigation\MenuItem::$label',
    'Current\Navigation\MenuItem::$pageId',
    'Current\Navigation\MenuItem::$url',
    'Current\Navigation\MenuItemKind',
    'Current\Navigation\MenuItemKind::Group',
    'Current\Navigation\MenuItemKind::Page',
    'Current\Navigation\MenuRegistry',
    'Current\Navigation\MenuRegistry::define()',
    'Current\Navigation\MenuRegistry::derive()',
    'Current\Navigation\Navigation',
    'Current\Navigation\Navigation::breadcrumbs()',
    'Current\Navigation\Navigation::menu()',
    'Current\Pages\Page',
    'Current\Pages\Page::$id',
    'Current\Pages\Page::children()',
    'Current\Pages\Page::description()',
    'Current\Pages\Page::icon()',
    'Current\Pages\Page::label()',
    'Current\Pages\Page::make()',
    'Current\Pages\Page::order()',
    'Current\Pages\Page::parent()',
    'Current\Pages\Page::presents()',
    'Current\Pages\Page::route()',
    'Current\Pages\Page::routeParameters()',
    'Current\Pages\Page::title()',
    'Current\Pages\PageDefinition',
    'Current\Pages\PageDefinition::$description',
    'Current\Pages\PageDefinition::$icon',
    'Current\Pages\PageDefinition::$id',
    'Current\Pages\PageDefinition::$label',
    'Current\Pages\PageDefinition::$labelled',
    'Current\Pages\PageDefinition::$order',
    'Current\Pages\PageDefinition::$parentId',
    'Current\Pages\PageDefinition::$presents',
    'Current\Pages\PageDefinition::$presentsParameter',
    'Current\Pages\PageDefinition::$routeName',
    'Current\Pages\PageDefinition::$routeParameters',
    'Current\Pages\PageDefinition::$title',
    'Current\Pages\PageRegistry',
    'Current\Pages\PageRegistry::all()',
    'Current\Pages\PageRegistry::page()',
    'Current\Pages\PageRegistry::register()',
    'Current\Pages\Pages',
    'Current\Pages\Pages::activeTrail()',
    'Current\Pages\Pages::context()',
    'Current\Pages\Pages::current()',
    'Current\Pages\Pages::description()',
    'Current\Pages\Pages::icon()',
    'Current\Pages\Pages::label()',
    'Current\Pages\Pages::title()',
    'Current\Pages\Pages::url()',
    'Current\Subjects\Subject',
    'Current\Subjects\Subject::$name',
    'Current\Subjects\Subject::description()',
    'Current\Subjects\Subject::for()',
    'Current\Subjects\Subject::icon()',
    'Current\Subjects\Subject::label()',
    'Current\Subjects\Subject::make()',
    'Current\Subjects\Subject::page()',
    'Current\Subjects\Subject::title()',
    'Current\Subjects\SubjectDefinition',
    'Current\Subjects\SubjectDefinition::$class',
    'Current\Subjects\SubjectDefinition::$description',
    'Current\Subjects\SubjectDefinition::$icon',
    'Current\Subjects\SubjectDefinition::$label',
    'Current\Subjects\SubjectDefinition::$name',
    'Current\Subjects\SubjectDefinition::$pageId',
    'Current\Subjects\SubjectDefinition::$routeParameters',
    'Current\Subjects\SubjectDefinition::$title',
    'Current\Subjects\SubjectItem',
    'Current\Subjects\SubjectItem::$id',
    'Current\Subjects\SubjectItem::$kind',
    'Current\Subjects\SubjectItem::$label',
    'Current\Subjects\SubjectItem::$url',
    'Current\Subjects\SubjectItem::key()',
    'Current\Subjects\SubjectRegistry',
    'Current\Subjects\SubjectRegistry::all()',
    'Current\Subjects\SubjectRegistry::kind()',
    'Current\Subjects\SubjectRegistry::register()',
    'Current\Subjects\Subjects',
    'Current\Subjects\Subjects::current()',
    'Current\Subjects\Subjects::description()',
    'Current\Subjects\Subjects::of()',
    'Current\Subjects\Subjects::title()',
    'Current\Testing\InteractsWithCurrent',
    'Current\Testing\InteractsWithCurrent::assertActionDisabled()',
    'Current\Testing\InteractsWithCurrent::assertActionEnabled()',
    'Current\Testing\InteractsWithCurrent::assertActionHas()',
    'Current\Testing\InteractsWithCurrent::assertActionMissing()',
    'Current\Testing\InteractsWithCurrent::assertBreadcrumbs()',
    'Current\Testing\InteractsWithCurrent::assertCurrentPage()',
    'Current\Testing\InteractsWithCurrent::assertCurrentSubject()',
    'Current\Testing\InteractsWithCurrent::assertDescription()',
    'Current\Testing\InteractsWithCurrent::assertMenuActive()',
    'Current\Testing\InteractsWithCurrent::assertMenuHas()',
    'Current\Testing\InteractsWithCurrent::assertMenuMissing()',
    'Current\Testing\InteractsWithCurrent::assertTitle()',
];

/** @return list<string> */
function supportedApi(): array
{
    $root = dirname(__DIR__, 2).'/src';
    $isInternal = fn (Reflector $element): bool => str_contains((string) $element->getDocComment(), '@internal');
    $symbols = [];

    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
    foreach ($files as $file) {
        $class = 'Current\\'.str_replace(['/', '.php'], ['\\', ''], substr($file->getPathname(), strlen($root) + 1));
        $reflection = new ReflectionClass($class);
        if ($isInternal($reflection)) {
            continue;
        }
        $symbols[] = $class;

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === $class && ! $method->isInternal() && ! $isInternal($method)) {
                $symbols[] = "{$class}::{$method->getName()}()";
            }
        }
        if ($reflection->isEnum()) {
            foreach (array_keys($reflection->getConstants()) as $case) {
                $symbols[] = "{$class}::{$case}";
            }

            continue;
        }
        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->getDeclaringClass()->getName() === $class && ! $isInternal($property)) {
                $symbols[] = "{$class}::\${$property->getName()}";
            }
        }
    }
    sort($symbols);

    return $symbols;
}

test('the supported API changes only deliberately', function () {
    expect(supportedApi())->toBe(SUPPORTED_API);
});

test('every supported method is documented', function () {
    $undocumented = array_filter(
        SUPPORTED_API,
        function (string $symbol): bool {
            if (! str_ends_with($symbol, '()')) {
                return false;
            }
            [$class, $method] = explode('::', substr($symbol, 0, -2));

            return (new ReflectionMethod($class, $method))->getDocComment() === false;
        },
    );

    expect(array_values($undocumented))->toBe([]);
});
