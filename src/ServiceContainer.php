<?php

/*
 * This file is part of the Eventum (Issue Tracking System) package.
 *
 * @copyright (c) Eventum Team
 * @license GNU General Public License, version 2 or later (GPL-2+)
 *
 * For the full copyright and license information,
 * please see the COPYING and AUTHORS files
 * that were distributed with this source code.
 */

namespace Eventum;

use Eventum\Config\Config;
use Pimple\Container;

class ServiceContainer
{
    public static function getInstance(): Container
    {
        static $container;

        if (!$container) {
            $container = new Container();
            $container->register(new ServiceProvider\ServiceProvider());
            $container->register(new ServiceProvider\FulltextSearchService());
        }

        return $container;
    }

    public static function get(string $className)
    {
        return static::getInstance()[$className];
    }

    public static function getConfig(): Config
    {
        return static::get('config');
    }
}
