<?php

declare(strict_types=1);

namespace Prototype;

use Illuminate\Database\Eloquent\Model;

final class Project extends Model
{
    public $timestamps = false;

    protected $guarded = [];
}
