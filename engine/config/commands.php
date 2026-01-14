<?php

/**
 * ==============================================================================
 * COMMANDS CONFIGURATION
 * ==============================================================================
 *
 * Registratie van Artisan commands voor de Omega Engine.
 */

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Default Command
    |--------------------------------------------------------------------------
    |
    | Het default command dat wordt uitgevoerd als geen command is opgegeven.
    |
    */

    'default' => NunoMaduro\LaravelConsoleSummary\SummaryCommand::class,

    /*
    |--------------------------------------------------------------------------
    | Commands Paths
    |--------------------------------------------------------------------------
    |
    | Paden waar Laravel Zero zoekt naar commands.
    |
    */

    'paths' => [app_path('Commands')],

    /*
    |--------------------------------------------------------------------------
    | Added Commands
    |--------------------------------------------------------------------------
    |
    | Extra commands die geregistreerd moeten worden.
    |
    */

    'add' => [
        // App\Commands\RefactorCommand::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Hidden Commands
    |--------------------------------------------------------------------------
    |
    | Commands die verborgen moeten zijn in de help output.
    |
    */

    'hidden' => [
        NunoMaduro\LaravelConsoleSummary\SummaryCommand::class,
        Symfony\Component\Console\Command\DumpCompletionCommand::class,
        Symfony\Component\Console\Command\HelpCommand::class,
        Symfony\Component\Console\Command\ListCommand::class,
        Illuminate\Console\Scheduling\ScheduleRunCommand::class,
        Illuminate\Console\Scheduling\ScheduleListCommand::class,
        Illuminate\Console\Scheduling\ScheduleFinishCommand::class,
        Illuminate\Console\Scheduling\ScheduleTestCommand::class,
        Illuminate\Console\Scheduling\ScheduleClearCacheCommand::class,
        Illuminate\Console\Scheduling\ScheduleWorkCommand::class,
        Illuminate\Foundation\Console\VendorPublishCommand::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Removed Commands
    |--------------------------------------------------------------------------
    |
    | Commands die verwijderd moeten worden.
    |
    */

    'remove' => [],
];
