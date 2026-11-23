<?php

declare(strict_types=1);

namespace App;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Shared demo projects, initially populated from database/fixtures/projects.json.
 *
 * @property int $id
 * @property string $name
 * @property bool $is_visible
 * @property bool $is_editable
 */
#[Fillable(['id', 'name'])]
final class ProjectExample extends Model
{
    protected $table = 'projects';

    public $timestamps = false;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['is_visible' => 'boolean', 'is_editable' => 'boolean'];
    }

    /** @return list<self> */
    public static function examples(): array
    {
        return array_values(self::query()->orderBy('id')->get()->all());
    }

    public static function findExample(string $id): ?self
    {
        return ctype_digit($id) ? self::query()->find((int) $id) : null;
    }
}
