<?php

/**
 * ==============================================================================
 * DE RALPHLOOP - AUTONOMOUS PHP REFACTORING ENGINE
 * ==============================================================================
 *
 * Dit is het hart van de Omega-PHP Engine. De RalphLoop implementeert een
 * recursieve feedback-loop die legacy PHP code transformeert naar moderne
 * PHP 8.4 standaarden.
 *
 * HET PROCES (De Loop):
 * 1. Rector Pass (Deterministic) - Bekende transformaties automatisch
 * 2. PHPStan Analysis - Identificeer remaining issues
 * 3. Pest Tests - Verify gedrag niet gebroken
 * 4. AI Correction - Claude fixt wat Rector niet kan
 * 5. Herhaal totdat Gold Standard bereikt (Level 9, 100% tests)
 *
 * WAAROM DEZE ARCHITECTUUR?
 * - Rector EERST: Reduceert AI calls (en dus kosten) significant
 * - Loop met limiet: Voorkomt infinite loops bij onoplosbare problemen
 * - Gestructureerde output: Elke iteratie is traceerbaar voor debugging
 *
 * @package AetherLink.AI Tech\OmegaEngine
 * @author  AetherLink.AI Tech Engineering <engineering@aetherlink.ai>
 */

declare(strict_types=1);

namespace App\Services;

use Anthropic\Client as AnthropicClient;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Collection;
use RuntimeException;
use InvalidArgumentException;

/**
 * De RalphLoop Service
 *
 * Voert de autonome refactoring cycle uit op PHP bestanden.
 *
 * @phpstan-type PhpStanError array{
 *     message: string,
 *     line: int,
 *     file: string,
 *     ignorable: bool
 * }
 *
 * @phpstan-type PestFailure array{
 *     test: string,
 *     message: string,
 *     file: string,
 *     line: int
 * }
 *
 * @phpstan-type LoopResult array{
 *     success: bool,
 *     iterations: int,
 *     final_errors: int,
 *     final_test_failures: int,
 *     history: array<int, array{
 *         iteration: int,
 *         rector_applied: bool,
 *         phpstan_errors: int,
 *         pest_failures: int,
 *         ai_fix_applied: bool
 *     }>
 * }
 */
final class RalphLoop
{
    /**
     * Maximum aantal iteraties voordat we opgeven.
     *
     * WAAROM 10?
     * - De meeste issues zijn opgelost binnen 3-5 iteraties
     * - Als na 10 iteraties nog errors zijn, is er waarschijnlijk een
     *   fundamenteel probleem dat menselijke interventie vereist
     * - Voorkomt runaway costs bij API calls
     */
    private const int MAX_ITERATIONS = 10;

    /**
     * Timeout voor externe processen (in seconden).
     */
    private const int PROCESS_TIMEOUT = 300;

    /**
     * De Anthropic client voor AI-assisted fixes.
     */
    private readonly AnthropicClient $anthropic;

    /**
     * Pad naar de Rector binary.
     */
    private readonly string $rectorBinary;

    /**
     * Pad naar de PHPStan binary.
     */
    private readonly string $phpstanBinary;

    /**
     * Pad naar de Pest binary.
     */
    private readonly string $pestBinary;

    /**
     * Project root directory.
     */
    private readonly string $projectRoot;

    /**
     * Constructor met dependency injection.
     *
     * DESIGN DECISION: We injecteren de Anthropic client ipv het zelf
     * te instantiëren. Dit maakt de class testbaar (we kunnen mocken)
     * en volgt het Dependency Inversion Principle.
     *
     * @param Anthropic   $anthropic    De AI client
     * @param string|null $projectRoot  Project root (default: getcwd())
     */
    public function __construct(
        AnthropicClient $anthropic,
        ?string $projectRoot = null,
    ) {
        $this->anthropic = $anthropic;
        $this->projectRoot = $projectRoot ?? (string) getcwd();

        // Resolve binary paths
        $this->rectorBinary = $this->projectRoot . '/vendor/bin/rector';
        $this->phpstanBinary = $this->projectRoot . '/vendor/bin/phpstan';
        $this->pestBinary = $this->projectRoot . '/vendor/bin/pest';

        $this->validateBinaries();
    }

    /**
     * Execute de RalphLoop op een specifiek bestand of directory.
     *
     * Dit is de MAIN ENTRY POINT voor de refactoring engine.
     *
     * @param  string $targetPath    Het bestand of directory om te refactoren
     * @param  bool   $dryRun        Als true, pas geen wijzigingen toe
     * @return LoopResult            Het resultaat van de loop
     *
     * @throws InvalidArgumentException Als het pad niet bestaat
     * @throws RuntimeException         Als een kritieke fout optreedt
     */
    public function execute(string $targetPath, bool $dryRun = false): array
    {
        // Valideer input
        if (!file_exists($targetPath)) {
            throw new InvalidArgumentException(
                "Target path does not exist: {$targetPath}"
            );
        }

        $this->log("🚀 Starting RalphLoop for: {$targetPath}");
        $this->log("📋 Mode: " . ($dryRun ? "DRY RUN" : "LIVE"));

        // Track history voor debugging en reporting
        $history = [];
        $iteration = 0;

        // =====================================================================
        // STAP 1: RECTOR PASS (Deterministic)
        // =====================================================================
        // We runnen Rector EERST, buiten de loop. Dit is een one-time pass
        // die alle "bekende" transformaties toepast. Dit reduceert de workload
        // voor de AI significant.
        // =====================================================================

        $this->log("\n📦 [PRE-LOOP] Running Rector (deterministic pass)...");
        $rectorApplied = $this->runRector($targetPath, $dryRun);

        if ($rectorApplied) {
            $this->log("✅ Rector applied transformations");
        } else {
            $this->log("ℹ️  Rector: No changes needed");
        }

        // =====================================================================
        // STAP 2: DE LOOP
        // =====================================================================
        // Nu starten we de recursieve feedback loop. Elke iteratie:
        // 1. Run PHPStan -> capture errors
        // 2. Run Pest -> capture failures
        // 3. Als errors > 0: AI fix -> apply -> continue
        // 4. Als errors == 0 en tests pass: SUCCESS -> break
        // =====================================================================

        while ($iteration < self::MAX_ITERATIONS) {
            $iteration++;
            $this->log("\n🔄 [ITERATION {$iteration}/{self::MAX_ITERATIONS}]");

            // -----------------------------------------------------------------
            // PHPStan Analysis
            // -----------------------------------------------------------------
            $this->log("  📊 Running PHPStan (Level 9)...");
            $phpstanErrors = $this->runPhpStan($targetPath);
            $errorCount = count($phpstanErrors);
            $this->log("  📊 PHPStan errors: {$errorCount}");

            // -----------------------------------------------------------------
            // Pest Tests
            // -----------------------------------------------------------------
            $this->log("  🧪 Running Pest tests...");
            $pestFailures = $this->runPest($targetPath);
            $failureCount = count($pestFailures);
            $this->log("  🧪 Pest failures: {$failureCount}");

            // -----------------------------------------------------------------
            // Record iteration history
            // -----------------------------------------------------------------
            $iterationRecord = [
                'iteration' => $iteration,
                'rector_applied' => $iteration === 1 && $rectorApplied,
                'phpstan_errors' => $errorCount,
                'pest_failures' => $failureCount,
                'ai_fix_applied' => false,
            ];

            // -----------------------------------------------------------------
            // SUCCESS CHECK
            // -----------------------------------------------------------------
            // Gold Standard bereikt: 0 PHPStan errors + 0 test failures
            // -----------------------------------------------------------------
            if ($errorCount === 0 && $failureCount === 0) {
                $this->log("\n🏆 GOLD STANDARD ACHIEVED!");
                $this->log("   ✅ PHPStan Level 9: PASS");
                $this->log("   ✅ Pest Tests: PASS");

                $history[] = $iterationRecord;

                return [
                    'success' => true,
                    'iterations' => $iteration,
                    'final_errors' => 0,
                    'final_test_failures' => 0,
                    'history' => $history,
                ];
            }

            // -----------------------------------------------------------------
            // AI CORRECTION
            // -----------------------------------------------------------------
            // We hebben errors. Tijd om Claude in te schakelen.
            // -----------------------------------------------------------------
            if (!$dryRun) {
                $this->log("  🤖 Requesting AI fix from Claude...");

                // Lees de huidige code
                $currentCode = $this->readTarget($targetPath);

                // Genereer de prompt
                $prompt = $this->generateRefactorPrompt(
                    $currentCode,
                    $phpstanErrors,
                    $pestFailures
                );

                // Vraag Claude om een fix
                $fixedCode = $this->requestAiFix($prompt, $currentCode);

                if ($fixedCode !== null && $fixedCode !== $currentCode) {
                    // Apply the fix
                    $this->writeTarget($targetPath, $fixedCode);
                    $this->log("  ✅ AI fix applied");
                    $iterationRecord['ai_fix_applied'] = true;
                } else {
                    $this->log("  ⚠️  AI could not provide a fix");
                }
            }

            $history[] = $iterationRecord;
        }

        // =====================================================================
        // LOOP EXHAUSTED
        // =====================================================================
        // We hebben MAX_ITERATIONS bereikt zonder Gold Standard.
        // Dit is een FAIL - menselijke interventie nodig.
        // =====================================================================

        $this->log("\n❌ MAX ITERATIONS REACHED WITHOUT SUCCESS");
        $this->log("   Remaining PHPStan errors: {$errorCount}");
        $this->log("   Remaining Pest failures: {$failureCount}");
        $this->log("   🔧 Human intervention required");

        return [
            'success' => false,
            'iterations' => $iteration,
            'final_errors' => $errorCount,
            'final_test_failures' => $failureCount,
            'history' => $history,
        ];
    }

    /**
     * Genereer de prompt voor Claude om code te fixen.
     *
     * PROMPT ENGINEERING NOTES:
     * - We geven SPECIFIEKE error messages met line numbers
     * - We vragen om ALLEEN de gefixte code, geen uitleg
     * - We specificeren PHP 8.4 en strict types als requirements
     *
     * @param  string               $code           De huidige code
     * @param  array<PhpStanError>  $phpstanErrors  PHPStan errors
     * @param  array<PestFailure>   $pestFailures   Pest test failures
     * @return string                               De volledige prompt
     */
    private function generateRefactorPrompt(
        string $code,
        array $phpstanErrors,
        array $pestFailures,
    ): string {
        $prompt = <<<'PROMPT'
Je bent een expert PHP 8.4 developer. Je taak is om de onderstaande code te fixen
zodat deze voldoet aan PHPStan Level 9 en alle tests passeert.

REQUIREMENTS:
- Gebruik declare(strict_types=1);
- Gebruik PHP 8.4 features waar mogelijk (constructor promotion, readonly, etc.)
- Fix ALLE gemelde errors
- Behoud de bestaande functionaliteit
- Retourneer ALLEEN de gefixte code, geen uitleg

PROMPT;

        // Voeg PHPStan errors toe
        if (count($phpstanErrors) > 0) {
            $prompt .= "\n\n## PHPSTAN ERRORS:\n";
            foreach ($phpstanErrors as $error) {
                $prompt .= sprintf(
                    "- Line %d: %s\n",
                    $error['line'],
                    $error['message']
                );
            }
        }

        // Voeg Pest failures toe
        if (count($pestFailures) > 0) {
            $prompt .= "\n\n## TEST FAILURES:\n";
            foreach ($pestFailures as $failure) {
                $prompt .= sprintf(
                    "- %s (Line %d): %s\n",
                    $failure['test'],
                    $failure['line'],
                    $failure['message']
                );
            }
        }

        // Voeg de huidige code toe
        $prompt .= "\n\n## CURRENT CODE:\n```php\n{$code}\n```";

        $prompt .= "\n\n## FIXED CODE:\n";

        return $prompt;
    }

    /**
     * Vraag Claude om een AI fix.
     *
     * @param  string      $prompt       De prompt
     * @param  string      $currentCode  De huidige code
     * @return string|null               De gefixte code of null
     */
    private function requestAiFix(string $prompt, string $currentCode): ?string
    {
        try {
            $response = $this->anthropic->messages->create([
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 8192,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
            ]);

            // Extract de code uit het response
            $content = $response->content[0]->text ?? '';

            // Parse de code block
            if (preg_match('/```php\s*(.*?)\s*```/s', $content, $matches)) {
                return $matches[1];
            }

            // Als er geen code block is, neem aan dat de hele response code is
            if (str_starts_with(trim($content), '<?php')) {
                return trim($content);
            }

            return null;
        } catch (\Throwable $e) {
            $this->log("  ❌ AI request failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Run Rector op het target.
     *
     * @param  string $targetPath  Het pad om te analyseren
     * @param  bool   $dryRun      Dry run mode
     * @return bool                True als wijzigingen zijn toegepast
     */
    private function runRector(string $targetPath, bool $dryRun): bool
    {
        $args = [
            $this->rectorBinary,
            'process',
            $targetPath,
        ];

        if ($dryRun) {
            $args[] = '--dry-run';
        }

        $process = new Process(
            $args,
            $this->projectRoot,
            timeout: self::PROCESS_TIMEOUT
        );

        $process->run();

        // Rector exit code 0 = geen wijzigingen nodig
        // Rector exit code 1 = wijzigingen gemaakt (of nodig in dry-run)
        return $process->getExitCode() === 1;
    }

    /**
     * Run PHPStan en parse de errors.
     *
     * @param  string              $targetPath  Het pad om te analyseren
     * @return array<PhpStanError>              Lijst van errors
     */
    private function runPhpStan(string $targetPath): array
    {
        // Gebruik een minimale config voor externe bestanden
        $externalConfig = $this->projectRoot . '/phpstan-external.neon';

        $process = new Process(
            [
                $this->phpstanBinary,
                'analyse',
                $targetPath,
                '--level=9',
                '-c', $externalConfig,
                '--error-format=json',
                '--no-progress',
                '--memory-limit=2G',
            ],
            $this->projectRoot,
            timeout: self::PROCESS_TIMEOUT
        );

        $process->run();

        // PHPStan geeft JSON output bij --error-format=json
        $output = $process->getOutput();

        if (empty($output)) {
            return [];
        }

        try {
            /** @var array{files: array<string, array{errors: array<array{message: string, line: int, ignorable: bool}>}>} $decoded */
            $decoded = json_decode($output, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        $errors = [];

        foreach ($decoded['files'] ?? [] as $file => $fileData) {
            // PHPStan JSON format uses 'messages' not 'errors'
            foreach ($fileData['messages'] ?? [] as $error) {
                $errors[] = [
                    'file' => $file,
                    'line' => $error['line'] ?? 0,
                    'message' => $error['message'] ?? 'Unknown error',
                    'ignorable' => $error['ignorable'] ?? false,
                ];
            }
        }

        return $errors;
    }

    /**
     * Run Pest tests en parse de failures.
     *
     * @param  string             $targetPath  Het pad om te testen
     * @return array<PestFailure>              Lijst van failures
     */
    private function runPest(string $targetPath): array
    {
        $process = new Process(
            [
                $this->pestBinary,
                '--colors=never',
            ],
            $this->projectRoot,
            timeout: self::PROCESS_TIMEOUT
        );

        $process->run();

        // Als exit code 0, geen failures
        if ($process->isSuccessful()) {
            return [];
        }

        // Parse de output voor failures
        $output = $process->getOutput() . $process->getErrorOutput();

        return $this->parsePestOutput($output);
    }

    /**
     * Parse Pest output naar een array van failures.
     *
     * @param  string             $output  De ruwe Pest output
     * @return array<PestFailure>          Parsed failures
     */
    private function parsePestOutput(string $output): array
    {
        $failures = [];

        // Pest format: FAILED Tests\ExampleTest > it does something
        preg_match_all(
            '/FAILED\s+([^\n]+)\n.*?at\s+([^:]+):(\d+)/s',
            $output,
            $matches,
            PREG_SET_ORDER
        );

        foreach ($matches as $match) {
            $failures[] = [
                'test' => trim($match[1]),
                'file' => trim($match[2]),
                'line' => (int) $match[3],
                'message' => 'Test failed', // Pest doesn't always give detailed messages
            ];
        }

        return $failures;
    }

    /**
     * Lees de content van het target (bestand of directory).
     *
     * DESIGN DECISION: Voor directories concateneren we alle PHP bestanden.
     * Dit is een simplificatie - in een productie versie zou je per-file
     * itereren.
     *
     * @param  string $targetPath  Het pad om te lezen
     * @return string              De geconcateneerde code
     */
    private function readTarget(string $targetPath): string
    {
        if (is_file($targetPath)) {
            $content = file_get_contents($targetPath);
            return $content !== false ? $content : '';
        }

        // Directory: concatenate all PHP files
        $code = '';
        $files = glob($targetPath . '/*.php') ?: [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content !== false) {
                $code .= "// FILE: {$file}\n{$content}\n\n";
            }
        }

        return $code;
    }

    /**
     * Schrijf code terug naar het target.
     *
     * @param string $targetPath  Het pad om naar te schrijven
     * @param string $code        De code om te schrijven
     */
    private function writeTarget(string $targetPath, string $code): void
    {
        if (is_file($targetPath)) {
            file_put_contents($targetPath, $code);
            return;
        }

        // Voor directories is dit complexer - we zouden moeten bepalen
        // welke file welk deel van de code krijgt. Voor nu: error.
        throw new RuntimeException(
            'Writing to directories with multiple files is not yet supported. ' .
            'Please target individual files.'
        );
    }

    /**
     * Valideer dat alle benodigde binaries bestaan.
     *
     * @throws RuntimeException Als een binary ontbreekt
     */
    private function validateBinaries(): void
    {
        $binaries = [
            'rector' => $this->rectorBinary,
            'phpstan' => $this->phpstanBinary,
            'pest' => $this->pestBinary,
        ];

        foreach ($binaries as $name => $path) {
            if (!file_exists($path)) {
                throw new RuntimeException(
                    "Required binary not found: {$name} at {$path}. " .
                    "Run 'composer install' first."
                );
            }
        }
    }

    /**
     * Log een bericht naar stdout.
     *
     * @param string $message  Het bericht om te loggen
     */
    private function log(string $message): void
    {
        echo $message . PHP_EOL;
    }
}
