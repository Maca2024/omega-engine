<?php

/**
 * ==============================================================================
 * RALPHLOOP TESTS
 * ==============================================================================
 *
 * Feature tests voor de RalphLoop service.
 * We gebruiken Pest syntax zoals gespecificeerd in de requirements.
 *
 * TESTING STRATEGIE:
 * - We mocken de Anthropic client om geen echte API calls te maken
 * - We testen de loop logic, niet de externe services
 * - We verifiëren dat de juiste prompts gegenereerd worden
 */

declare(strict_types=1);

use App\Services\RalphLoop;
use Anthropic\Anthropic;

/**
 * Test dat de RalphLoop correct instantieert met dependencies.
 */
test('RalphLoop kan geïnstantieerd worden met Anthropic client', function (): void {
    // Arrange
    $mockClient = Mockery::mock(Anthropic::class);

    // Act
    $loop = new RalphLoop(
        anthropic: $mockClient,
        projectRoot: __DIR__ . '/fixtures',
    );

    // Assert
    expect($loop)->toBeInstanceOf(RalphLoop::class);
});

/**
 * Test dat execute() een InvalidArgumentException gooit voor niet-bestaande paden.
 */
test('execute gooit exception voor niet-bestaand pad', function (): void {
    // Arrange
    $mockClient = Mockery::mock(Anthropic::class);

    $loop = new RalphLoop(
        anthropic: $mockClient,
        projectRoot: __DIR__ . '/fixtures',
    );

    // Act & Assert
    $loop->execute('/non/existent/path.php');
})->throws(InvalidArgumentException::class, 'Target path does not exist');

/**
 * Test dat dry-run mode geen wijzigingen aanbrengt.
 */
test('dry-run mode brengt geen wijzigingen aan', function (): void {
    // Arrange
    $mockClient = Mockery::mock(Anthropic::class);

    // De client mag NOOIT aangeroepen worden in dry-run mode
    $mockClient->shouldNotReceive('messages');

    $fixtureFile = __DIR__ . '/fixtures/sample.php';

    // Sla originele content op
    $originalContent = file_get_contents($fixtureFile);

    $loop = new RalphLoop(
        anthropic: $mockClient,
        projectRoot: dirname($fixtureFile),
    );

    // Act
    $result = $loop->execute($fixtureFile, dryRun: true);

    // Assert
    expect(file_get_contents($fixtureFile))->toBe($originalContent);
    expect($result)->toHaveKey('success');
    expect($result)->toHaveKey('iterations');
})->skip(fn () => !file_exists(__DIR__ . '/fixtures/sample.php'), 'Fixture file not found');

/**
 * Test dat het resultaat de correcte structuur heeft.
 */
test('execute retourneert correct gestructureerd resultaat', function (): void {
    // Arrange
    $mockClient = Mockery::mock(Anthropic::class);

    $fixtureFile = __DIR__ . '/fixtures/sample.php';

    $loop = new RalphLoop(
        anthropic: $mockClient,
        projectRoot: dirname($fixtureFile),
    );

    // Act
    $result = $loop->execute($fixtureFile, dryRun: true);

    // Assert - check de structuur
    expect($result)->toHaveKeys([
        'success',
        'iterations',
        'final_errors',
        'final_test_failures',
        'history',
    ]);

    expect($result['history'])->toBeArray();
})->skip(fn () => !file_exists(__DIR__ . '/fixtures/sample.php'), 'Fixture file not found');

/**
 * Test dat history correct wordt bijgehouden per iteratie.
 */
test('history bevat correcte iteration data', function (): void {
    // Arrange
    $mockClient = Mockery::mock(Anthropic::class);

    $fixtureFile = __DIR__ . '/fixtures/sample.php';

    $loop = new RalphLoop(
        anthropic: $mockClient,
        projectRoot: dirname($fixtureFile),
    );

    // Act
    $result = $loop->execute($fixtureFile, dryRun: true);

    // Assert - als er iteraties zijn, check de structuur
    if (count($result['history']) > 0) {
        $firstIteration = $result['history'][0];

        expect($firstIteration)->toHaveKeys([
            'iteration',
            'rector_applied',
            'phpstan_errors',
            'pest_failures',
            'ai_fix_applied',
        ]);
    }
})->skip(fn () => !file_exists(__DIR__ . '/fixtures/sample.php'), 'Fixture file not found');

// =============================================================================
// TEST FIXTURES SETUP
// =============================================================================

beforeAll(function (): void {
    // Maak fixtures directory aan indien nodig
    $fixturesDir = __DIR__ . '/fixtures';

    if (!is_dir($fixturesDir)) {
        mkdir($fixturesDir, 0755, true);
    }

    // Maak een sample PHP file voor testing
    $sampleFile = $fixturesDir . '/sample.php';

    if (!file_exists($sampleFile)) {
        file_put_contents($sampleFile, <<<'PHP'
<?php

declare(strict_types=1);

/**
 * Sample file voor RalphLoop tests.
 */
final class SampleClass
{
    public function __construct(
        private readonly string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }
}
PHP);
    }
});
