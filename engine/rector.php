<?php

/**
 * ==============================================================================
 * OMEGA-PHP RECTOR CONFIGURATIE
 * ==============================================================================
 *
 * Dit bestand configureert Rector voor de RalphLoop Engine.
 *
 * RECTOR FILOSOFIE:
 * Rector is een "deterministic refactoring tool" - gegeven dezelfde input,
 * produceert het ALTIJD dezelfde output. Dit is cruciaal voor de RalphLoop:
 * we runnen Rector EERST (voor AI) zodat alle "bekende" transformaties
 * automatisch worden toegepast. De AI handelt alleen de edge cases af.
 *
 * WAAROM DEZE SETS?
 * 1. PHP 8.4: Target versie - alle moderne features activeren
 * 2. Dead Code: Opruimen van ongebruikte code reduceert ruis voor PHPStan
 * 3. Type Declarations: Expliciete types = betere static analysis = minder AI calls
 * 4. Code Quality: Algemene verbeteringen die PHPStan compliance verhogen
 *
 * @see https://getrector.com/documentation
 */

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddVoidReturnTypeWhereNoReturnRector;
use Rector\TypeDeclaration\Rector\Property\TypedPropertyFromAssignsRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodRector;
use Rector\DeadCode\Rector\Property\RemoveUnusedPrivatePropertyRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPrivateMethodParameterRector;
use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\Php81\Rector\Property\ReadOnlyPropertyRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;

return RectorConfig::configure()
    // =========================================================================
    // PATHS
    // =========================================================================
    // Standaard analyseren we de hele 'app' directory.
    // Bij runtime kan dit overschreven worden via CLI arguments.
    // =========================================================================
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/tests',
    ])

    // =========================================================================
    // SKIP PATHS
    // =========================================================================
    // Sommige directories willen we NOOIT refactoren:
    // - vendor: Third-party code, niet onze verantwoordelijkheid
    // - cache: Gegenereerde bestanden
    // - storage: Runtime data
    // =========================================================================
    ->withSkip([
        __DIR__ . '/vendor',
        __DIR__ . '/storage',
        __DIR__ . '/bootstrap/cache',
    ])

    // =========================================================================
    // PHP VERSION TARGET
    // =========================================================================
    // We upgraden naar PHP 8.4 - de meest recente stable versie.
    // Dit activeert ALLE PHP upgrades van 5.x/7.x naar 8.4.
    // =========================================================================
    ->withPhpSets(php84: true)

    // =========================================================================
    // PREPARED SETS
    // =========================================================================
    // Dit zijn gecureerde collecties van Rector rules.
    // We kiezen specifieke sets die onze doelen ondersteunen.
    // =========================================================================
    ->withPreparedSets(
        // Dead Code Removal - Cruciaal voor het opruimen van legacy
        deadCode: true,

        // Code Quality - Algemene verbeteringen
        codeQuality: true,

        // Type Declarations - Expliciete types toevoegen
        typeDeclarations: true,

        // Privatization - Private maken wat private kan zijn
        privatization: true,

        // Naming - Consistente naming conventions
        naming: true,

        // Early Return - Betere code flow
        earlyReturn: true,
    )

    // =========================================================================
    // ATTRIBUTE CONVERSION
    // =========================================================================
    // Converteer PHPDoc annotations naar native PHP 8 Attributes.
    // Dit is essentieel voor moderne Laravel code.
    // =========================================================================
    ->withAttributesSets(
        symfony: true,
        doctrine: true,
    )

    // =========================================================================
    // SPECIFIEKE RULES
    // =========================================================================
    // Naast de sets, activeren we specifieke rules die extra waarde toevoegen.
    // Deze rules zijn geselecteerd omdat ze directe impact hebben op
    // PHPStan Level 9 compliance.
    // =========================================================================
    ->withRules([
        // Void return types toevoegen waar geen return statement is
        AddVoidReturnTypeWhereNoReturnRector::class,

        // Property types afleiden uit assignments
        TypedPropertyFromAssignsRector::class,

        // Unused private methods verwijderen
        RemoveUnusedPrivateMethodRector::class,

        // Unused private properties verwijderen
        RemoveUnusedPrivatePropertyRector::class,

        // Unused method parameters verwijderen
        RemoveUnusedPrivateMethodParameterRector::class,

        // Constructor property promotion (PHP 8.0+)
        ClassPropertyAssignToConstructorPromotionRector::class,

        // Inline constructor defaults naar property
        InlineConstructorDefaultToPropertyRector::class,

        // Readonly properties waar mogelijk (PHP 8.1+)
        ReadOnlyPropertyRector::class,

        // Readonly classes waar mogelijk (PHP 8.2+)
        ReadOnlyClassRector::class,
    ])

    // =========================================================================
    // IMPORT SETTINGS
    // =========================================================================
    // Configuratie voor hoe Rector omgaat met use statements.
    // =========================================================================
    ->withImportNames(
        importNames: true,
        importDocBlockNames: true,
        importShortClasses: false,
        removeUnusedImports: true,
    )

    // =========================================================================
    // PARALLEL PROCESSING
    // =========================================================================
    // Voor grote codebases: gebruik multiple CPU cores.
    // =========================================================================
    ->withParallel(
        maxNumberOfProcess: 8,
        jobSize: 20,
    );
