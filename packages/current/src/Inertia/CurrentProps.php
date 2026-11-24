<?php

declare(strict_types=1);

namespace Current\Inertia;

use Current\Actions\ActionItem;
use Current\Exceptions\InvalidDefinition;
use Current\Exceptions\ResolutionFailed;
use Current\Experience;
use Current\Navigation\Breadcrumb;
use Current\Subjects\SubjectItem;
use Illuminate\Container\Container;
use Inertia\PropertyContext;
use Inertia\ProvidesInertiaProperty;
use stdClass;

/**
 * Shares the current request's page ID, subject, breadcrumbs, and selected menus as one Inertia prop.
 *
 * The serialized form of the Current\Facades\Current facade. Place it in
 * HandleInertiaRequests::share() under a key of your choice; the documentation
 * uses `current`. It resolves when the response renders, after route-model binding, and not at
 * all when a partial reload excludes its key. The default shape is
 * `{ pageId, subject, breadcrumbs, menus }`. Independent pagePresentation(),
 * subjectPresentation(), and actions() opt-ins add the documented fields and
 * evaluate their readers only when requested, matching the TypeScript feature types.
 */
final class CurrentProps implements ProvidesInertiaProperty
{
    /** @var list<string> */
    private array $menus = [];

    private bool $pagePresentation = false;

    private bool $subjectPresentation = false;

    private bool $actions = false;

    private function __construct() {}

    /** Start a prop that shares the page ID and breadcrumbs, and no menus. */
    public static function make(): self
    {
        return new self;
    }

    /** Include the selected page's nullable title and description. */
    public function pagePresentation(): self
    {
        $this->pagePresentation = true;

        return $this;
    }

    /** Include the current subject's own title and description when a subject exists. */
    public function subjectPresentation(): self
    {
        $this->subjectPresentation = true;

        return $this;
    }

    /** Include visible subject/page actions, including disabled actions and reasons. */
    public function actions(): self
    {
        $this->actions = true;

        return $this;
    }

    /**
     * Share these registered menus, keyed by name, in the given order.
     *
     * Names are checked against registered menus when the prop resolves.
     *
     * @throws InvalidDefinition If a name is empty or already shared.
     */
    public function menus(string ...$names): self
    {
        foreach ($names as $name) {
            if (trim($name) === '' || in_array($name, $this->menus, true)) {
                throw new InvalidDefinition("Menu '{$name}' must have a non-empty name and be shared once.");
            }
            $this->menus[] = $name;
        }

        return $this;
    }

    /**
     * @internal Called by Inertia when the response renders.
     *
     * @return array{pageId: ?string, subject: SubjectItem|array{kind: string, id: string, label: string, url: ?string, title: string, description: ?string}|null, breadcrumbs: list<Breadcrumb>, menus: stdClass, title?: ?string, description?: ?string, actions?: list<ActionItem>}
     *
     * @throws InvalidDefinition If definitions or route references are invalid.
     * @throws ResolutionFailed If a menu is unknown, or callbacks or destination URLs cannot be resolved.
     */
    public function toInertiaProperty(PropertyContext $prop): array
    {
        $current = Container::getInstance()->make(Experience::class);

        // An object, so an empty set still serializes as `{}` for the TypeScript record.
        $menus = new stdClass;
        foreach ($this->menus as $name) {
            $menus->{$name} = $current->menu($name);
        }

        $subject = $current->subject();
        if ($this->subjectPresentation && $subject !== null) {
            $subject = [
                'kind' => $subject->kind,
                'id' => $subject->id,
                'label' => $subject->label,
                'url' => $subject->url,
                'title' => $current->subjectTitle() ?? throw new ResolutionFailed('The current subject disappeared while resolving its Inertia presentation.'),
                'description' => $current->subjectDescription(),
            ];
        }
        $data = [
            'pageId' => $current->pageId(),
            'subject' => $subject,
            'breadcrumbs' => $current->breadcrumbs(),
            'menus' => $menus,
        ];
        if ($this->pagePresentation) {
            $data['title'] = $current->title();
            $data['description'] = $current->description();
        }
        if ($this->actions) {
            $data['actions'] = $current->actions();
        }

        return $data;
    }
}
