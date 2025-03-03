<?php

namespace mbscholars\Devteam;

use mbscholars\Devteam\Commands\DevteamCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class DevteamServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('devteam')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_devteam_table')
            ->hasCommand(DevteamCommand::class);
    }
}
