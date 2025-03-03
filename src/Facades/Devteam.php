<?php

namespace mbscholars\Devteam\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \mbscholars\Devteam\Devteam
 */
class Devteam extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \mbscholars\Devteam\Devteam::class;
    }
}
