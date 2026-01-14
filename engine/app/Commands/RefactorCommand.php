<?php

/**
 * ==============================================================================
 * REFACTOR COMMAND
 * ==============================================================================
 *
 * Dit is de CLI interface voor de RalphLoop Engine.
 * Het is de primary way om de engine aan te roepen vanuit de Docker container.
 *
 * GEBRUIK:
 *   php omega refactor /path/to/file.php
 *   php omega refactor /path/to/directory --dry-run
 *   php omega refactor src/Legacy --output=json
 */

declare(strict_types=1);

namespace App\Commands;

use App\Services\RalphLoop;
use Anthropic\Client as AnthropicClient;
use LaravelZero\Framework\Commands\Command;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 * Het Refactor command voor de Omega CLI.
 *
 * Dit command accepteert een bestand of directory en voert de volledige
 * RalphLoop cycle uit om het te refactoren naar PHP 8.4 standards.
 */
#[AsCommand(
    name: 'refactor',
    description: 'Refactor legacy PHP code naar PHP 8.4 standards met de RalphLoop Engine'
)]
final class RefactorCommand extends Command
{
    /**
     * De command signature.
     *
     * ARGUMENTEN:
     * - target: Het bestand of directory om te refactoren
     *
     * OPTIES:
     * - --dry-run: Voer analyse uit zonder wijzigingen toe te passen
     * - --output: Output formaat (text|json)
     * - --max-iterations: Override het maximum aantal iteraties
     *
     * @var string
     */
    protected $signature = 'refactor
                            {target : Het bestand of directory om te refactoren}
                            {--dry-run : Voer analyse uit zonder wijzigingen}
                            {--output=text : Output formaat (text|json)}
                            {--max-iterations=10 : Maximum aantal iteraties}';

    /**
     * De command beschrijving voor help output.
     *
     * @var string
     */
    protected $description = 'Transformeer legacy PHP code naar moderne PHP 8.4 met AI-assisted refactoring';

    /**
     * Execute het command.
     *
     * @return int Exit code (0 = success, 1 = failure)
     */
    public function handle(): int
    {
        // =====================================================================
        // INPUT VALIDATIE
        // =====================================================================

        $target = $this->argument('target');
        $dryRun = $this->option('dry-run');
        $outputFormat = $this->option('output');

        if (!is_string($target)) {
            $this->error('Target argument must be a string');
            return self::FAILURE;
        }

        // Resolve naar absoluut pad
        $targetPath = $this->resolveTargetPath($target);

        if ($targetPath === null) {
            $this->error("Target does not exist: {$target}");
            return self::FAILURE;
        }

        // =====================================================================
        // API KEY VALIDATIE
        // =====================================================================

        $apiKey = $this->getAnthropicApiKey();

        if ($apiKey === null && !$dryRun) {
            $this->error('ANTHROPIC_API_KEY environment variable is required');
            $this->error('Set it via: export ANTHROPIC_API_KEY=your-key');
            return self::FAILURE;
        }

        // =====================================================================
        // INITIALISATIE
        // =====================================================================

        if ($outputFormat !== 'json') {
            $this->displayHeader($targetPath, (bool) $dryRun);
        }

        // Maak de Anthropic client
        // In dry-run mode maken we een dummy client die nooit aangeroepen wordt
        $anthropic = $dryRun
            ? $this->createDummyClient()
            : $this->createAnthropicClient($apiKey);

        // Instantieer de RalphLoop
        // We gebruiken /app als project root (waar de vendor binaries staan)
        $ralphLoop = new RalphLoop(
            anthropic: $anthropic,
            projectRoot: base_path(),
        );

        // =====================================================================
        // EXECUTE
        // =====================================================================

        try {
            $result = $ralphLoop->execute($targetPath, (bool) $dryRun);
        } catch (\Throwable $e) {
            $this->error("Fatal error: " . $e->getMessage());

            if ($outputFormat === 'json') {
                $this->outputJson([
                    'success' => false,
                    'error' => $e->getMessage(),
                ]);
            }

            return self::FAILURE;
        }

        // =====================================================================
        // OUTPUT
        // =====================================================================

        if ($outputFormat === 'json') {
            $this->outputJson($result);
        } else {
            $this->displayResult($result);
        }

        return $result['success'] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Resolve het target pad naar een absoluut pad.
     *
     * @param  string      $target  Het input pad
     * @return string|null          Het absolute pad of null als niet bestaat
     */
    private function resolveTargetPath(string $target): ?string
    {
        // Als het al een absoluut pad is
        if (str_starts_with($target, '/') || preg_match('/^[A-Z]:/i', $target)) {
            return file_exists($target) ? $target : null;
        }

        // Probeer relatief aan de huidige directory
        $resolved = getcwd() . '/' . $target;

        return file_exists($resolved) ? $resolved : null;
    }

    /**
     * Haal de Anthropic API key uit de environment.
     *
     * @return string|null De API key of null
     */
    private function getAnthropicApiKey(): ?string
    {
        $key = getenv('ANTHROPIC_API_KEY');

        return $key !== false && $key !== '' ? $key : null;
    }

    /**
     * Maak een Anthropic client.
     *
     * @param  string|null $apiKey  De API key
     * @return AnthropicClient      De client
     */
    private function createAnthropicClient(?string $apiKey): AnthropicClient
    {
        return new AnthropicClient($apiKey ?? '');
    }

    /**
     * Maak een dummy Anthropic client voor dry-run mode.
     *
     * DESIGN NOTE: In een ideale wereld zouden we een interface gebruiken
     * en een NullObject pattern implementeren. Voor nu is dit een pragmatische
     * oplossing die werkt met de concrete AnthropicClient class.
     *
     * @return AnthropicClient
     */
    private function createDummyClient(): AnthropicClient
    {
        // We maken een client met een dummy key
        // Deze wordt nooit daadwerkelijk aangeroepen in dry-run mode
        return new AnthropicClient('dry-run-mode');
    }

    /**
     * Toon de header bij start van de command.
     *
     * @param string $targetPath  Het target pad
     * @param bool   $dryRun      Of we in dry-run mode zijn
     */
    private function displayHeader(string $targetPath, bool $dryRun): void
    {
        $this->newLine();
        $this->line('╔══════════════════════════════════════════════════════════════╗');
        $this->line('║         OMEGA-PHP REFACTORING ENGINE - De RalphLoop          ║');
        $this->line('║                   Solvari Engineering © 2024                 ║');
        $this->line('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->info("Target: {$targetPath}");
        $this->info("Mode: " . ($dryRun ? "DRY RUN (no changes)" : "LIVE"));
        $this->newLine();
    }

    /**
     * Toon het resultaat van de refactoring.
     *
     * @param array{
     *     success: bool,
     *     iterations: int,
     *     final_errors: int,
     *     final_test_failures: int,
     *     history: array<int, array<string, mixed>>
     * } $result Het resultaat van de RalphLoop
     */
    private function displayResult(array $result): void
    {
        $this->newLine();
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->line('                         RESULTAAT                            ');
        $this->line('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->newLine();

        if ($result['success']) {
            $this->info('🏆 GOLD STANDARD BEREIKT!');
            $this->info("   ✅ PHPStan Level 9: PASS");
            $this->info("   ✅ Pest Tests: PASS");
            $this->info("   📊 Iteraties nodig: {$result['iterations']}");
        } else {
            $this->error('❌ REFACTORING NIET COMPLEET');
            $this->error("   PHPStan errors remaining: {$result['final_errors']}");
            $this->error("   Test failures remaining: {$result['final_test_failures']}");
            $this->error("   Iteraties uitgevoerd: {$result['iterations']}");
            $this->newLine();
            $this->warn('   🔧 Menselijke interventie vereist');
        }

        // Toon iteration history
        $this->newLine();
        $this->line('Iteration History:');

        foreach ($result['history'] as $iteration) {
            $this->line(sprintf(
                "  [%d] Errors: %d | Failures: %d | AI Fix: %s",
                $iteration['iteration'],
                $iteration['phpstan_errors'],
                $iteration['pest_failures'],
                $iteration['ai_fix_applied'] ? 'Yes' : 'No'
            ));
        }

        $this->newLine();
    }

    /**
     * Output resultaat als JSON.
     *
     * @param array<string, mixed> $result Het resultaat
     */
    private function outputJson(array $result): void
    {
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    }
}
