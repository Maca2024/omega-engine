/**
 * ==============================================================================
 * NIGHT SHIFT - Trigger.dev Orchestrator voor de RalphLoop Engine
 * ==============================================================================
 *
 * Dit is de ORCHESTRATOR die de Omega-PHP Engine aanstuurt. Hij draait als een
 * Trigger.dev v3 task en is verantwoordelijk voor:
 *
 * 1. Het ontvangen van een todo_list.json payload met te refactoren files
 * 2. Het opstarten van de Docker container met de RalphLoop Engine
 * 3. Het monitoren van de output en rapporteren van resultaten
 *
 * WAAROM TRIGGER.DEV?
 * - Serverless execution: betaal alleen voor wat je gebruikt
 * - Built-in retries en error handling
 * - Real-time logging en monitoring via dashboard
 * - Cron scheduling voor nachtelijke runs (vandaar "Night Shift")
 *
 * ARCHITECTUUR:
 * Trigger.dev -> Docker Container -> RalphLoop (PHP) -> Claude API
 *                                         ^
 *                                         |
 *                                   PHPStan + Rector + Pest
 *
 * @see https://trigger.dev/docs
 */

import { task, logger, wait } from "@trigger.dev/sdk/v3";
import { exec } from "child_process";
import { promisify } from "util";
import * as fs from "fs/promises";
import * as path from "path";

const execAsync = promisify(exec);

// =============================================================================
// TYPE DEFINITIONS
// =============================================================================

/**
 * Een item in de todo lijst - representeert een bestand om te refactoren.
 */
interface TodoItem {
  /** Uniek ID voor tracking */
  id: string;
  /** Pad naar het bestand relatief aan de repository root */
  filePath: string;
  /** Prioriteit (1 = hoogste) */
  priority: number;
  /** Optionele repository URL voor git clone */
  repositoryUrl?: string;
  /** Branch om te gebruiken */
  branch?: string;
  /** Extra metadata */
  metadata?: Record<string, unknown>;
}

/**
 * De payload die de Night Shift job ontvangt.
 */
interface NightShiftPayload {
  /** Lijst van bestanden om te refactoren */
  todoList: TodoItem[];
  /** Of we in dry-run mode moeten draaien */
  dryRun?: boolean;
  /** Maximum aantal items om te verwerken (default: alle) */
  maxItems?: number;
  /** Docker image tag om te gebruiken */
  dockerImage?: string;
}

/**
 * Resultaat van een enkele refactoring operatie.
 */
interface RefactorResult {
  /** ID van het todo item */
  todoId: string;
  /** Of de refactoring succesvol was */
  success: boolean;
  /** Aantal iteraties dat nodig was */
  iterations: number;
  /** Remaining PHPStan errors */
  finalErrors: number;
  /** Remaining test failures */
  finalTestFailures: number;
  /** Tijdsduur in milliseconden */
  durationMs: number;
  /** Error message als gefaald */
  errorMessage?: string;
}

/**
 * Het complete resultaat van de Night Shift run.
 */
interface NightShiftResult {
  /** Of alle items succesvol waren */
  allSuccessful: boolean;
  /** Totaal aantal verwerkte items */
  totalProcessed: number;
  /** Aantal succesvolle refactors */
  successCount: number;
  /** Aantal gefaalde refactors */
  failureCount: number;
  /** Individuele resultaten */
  results: RefactorResult[];
  /** Totale tijdsduur in milliseconden */
  totalDurationMs: number;
}

// =============================================================================
// CONFIGURATIE
// =============================================================================

const CONFIG = {
  /** Default Docker image voor de RalphLoop Engine */
  defaultDockerImage: "solvari/omega-engine:latest",

  /** Timeout per bestand (in milliseconden) */
  perFileTimeout: 600_000, // 10 minuten

  /** Workspace directory in de container */
  containerWorkspace: "/workspace",

  /** Output directory voor logs */
  outputDir: "/tmp/omega-output",
} as const;

// =============================================================================
// NIGHT SHIFT TASK
// =============================================================================

/**
 * De Night Shift Task
 *
 * Dit is de main Trigger.dev task die de RalphLoop Engine orchestreert.
 * Hij kan gescheduled worden als cron job of handmatig getriggerd worden.
 *
 * @example
 * // Schedule als nachtelijke cron (in trigger.config.ts):
 * schedules.task({
 *   id: "night-shift-cron",
 *   task: nightShift,
 *   cron: "0 2 * * *", // Elke nacht om 02:00
 * });
 *
 * @example
 * // Handmatig triggeren:
 * await nightShift.trigger({
 *   todoList: [{ id: "1", filePath: "src/Legacy/OldController.php", priority: 1 }],
 * });
 */
export const nightShift = task({
  id: "night-shift",
  // Maximale runtime voor de hele batch
  maxDuration: 3600, // 1 uur

  run: async (payload: NightShiftPayload): Promise<NightShiftResult> => {
    const startTime = Date.now();

    logger.info("🌙 Night Shift starting", {
      totalItems: payload.todoList.length,
      dryRun: payload.dryRun ?? false,
      maxItems: payload.maxItems ?? "all",
    });

    // =========================================================================
    // VALIDATIE
    // =========================================================================

    if (!payload.todoList || payload.todoList.length === 0) {
      logger.warn("⚠️ Empty todo list received, nothing to process");
      return {
        allSuccessful: true,
        totalProcessed: 0,
        successCount: 0,
        failureCount: 0,
        results: [],
        totalDurationMs: Date.now() - startTime,
      };
    }

    // =========================================================================
    // PREPARE WORKSPACE
    // =========================================================================

    // Maak output directory aan
    await fs.mkdir(CONFIG.outputDir, { recursive: true });

    // =========================================================================
    // SORT BY PRIORITY
    // =========================================================================
    // Items met lagere priority number worden eerst verwerkt.
    // =========================================================================

    const sortedTodos = [...payload.todoList].sort(
      (a, b) => a.priority - b.priority
    );

    // Limiteer aantal items indien gespecificeerd
    const todosToProcess = payload.maxItems
      ? sortedTodos.slice(0, payload.maxItems)
      : sortedTodos;

    logger.info(`📋 Processing ${todosToProcess.length} items`);

    // =========================================================================
    // PROCESS ITEMS
    // =========================================================================
    // We verwerken items sequentieel om resource contention te voorkomen.
    // De Docker container is al resource-intensief; parallelle runs
    // zouden kunnen leiden tot OOM errors.
    // =========================================================================

    const results: RefactorResult[] = [];
    const dockerImage = payload.dockerImage ?? CONFIG.defaultDockerImage;

    for (const todo of todosToProcess) {
      logger.info(`\n🔄 Processing: ${todo.filePath}`, { todoId: todo.id });

      const itemStartTime = Date.now();

      try {
        // ---------------------------------------------------------------------
        // STAP 1: Clone repository (indien nodig)
        // ---------------------------------------------------------------------

        let workspacePath = CONFIG.containerWorkspace;

        if (todo.repositoryUrl) {
          workspacePath = path.join(CONFIG.outputDir, `workspace-${todo.id}`);
          await cloneRepository(
            todo.repositoryUrl,
            workspacePath,
            todo.branch
          );
        }

        // ---------------------------------------------------------------------
        // STAP 2: Run Docker container met RalphLoop
        // ---------------------------------------------------------------------

        const containerResult = await runRalphLoopContainer(
          dockerImage,
          workspacePath,
          todo.filePath,
          payload.dryRun ?? false
        );

        // ---------------------------------------------------------------------
        // STAP 3: Parse resultaat
        // ---------------------------------------------------------------------

        results.push({
          todoId: todo.id,
          success: containerResult.success,
          iterations: containerResult.iterations,
          finalErrors: containerResult.finalErrors,
          finalTestFailures: containerResult.finalTestFailures,
          durationMs: Date.now() - itemStartTime,
        });

        if (containerResult.success) {
          logger.info(`✅ Successfully refactored: ${todo.filePath}`);
        } else {
          logger.warn(`⚠️ Refactoring incomplete: ${todo.filePath}`, {
            remainingErrors: containerResult.finalErrors,
            remainingFailures: containerResult.finalTestFailures,
          });
        }
      } catch (error) {
        // ---------------------------------------------------------------------
        // ERROR HANDLING
        // ---------------------------------------------------------------------

        const errorMessage =
          error instanceof Error ? error.message : String(error);

        logger.error(`❌ Failed to process: ${todo.filePath}`, {
          error: errorMessage,
        });

        results.push({
          todoId: todo.id,
          success: false,
          iterations: 0,
          finalErrors: -1,
          finalTestFailures: -1,
          durationMs: Date.now() - itemStartTime,
          errorMessage,
        });
      }

      // -----------------------------------------------------------------------
      // COOL-DOWN PERIOD
      // -----------------------------------------------------------------------
      // Kleine pauze tussen items om API rate limits te respecteren
      // en systeem resources te laten herstellen.
      // -----------------------------------------------------------------------

      await wait.for({ seconds: 5 });
    }

    // =========================================================================
    // COMPILE RESULTS
    // =========================================================================

    const successCount = results.filter((r) => r.success).length;
    const failureCount = results.length - successCount;

    const finalResult: NightShiftResult = {
      allSuccessful: failureCount === 0,
      totalProcessed: results.length,
      successCount,
      failureCount,
      results,
      totalDurationMs: Date.now() - startTime,
    };

    // =========================================================================
    // FINAL LOG
    // =========================================================================

    logger.info("\n🌙 Night Shift completed", {
      duration: `${Math.round(finalResult.totalDurationMs / 1000)}s`,
      success: successCount,
      failed: failureCount,
      successRate: `${Math.round((successCount / results.length) * 100)}%`,
    });

    return finalResult;
  },
});

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Clone een Git repository naar een lokale directory.
 *
 * @param repositoryUrl  De URL van de repository
 * @param targetPath     Het pad waar de repo gecloned moet worden
 * @param branch         Optionele branch om te checken (default: default branch)
 */
async function cloneRepository(
  repositoryUrl: string,
  targetPath: string,
  branch?: string
): Promise<void> {
  logger.info(`📥 Cloning repository: ${repositoryUrl}`);

  // Verwijder bestaande directory indien aanwezig
  await fs.rm(targetPath, { recursive: true, force: true });

  // Clone command
  const branchArg = branch ? `--branch ${branch}` : "";
  const cloneCmd = `git clone --depth 1 ${branchArg} ${repositoryUrl} ${targetPath}`;

  await execAsync(cloneCmd);

  logger.info(`✅ Repository cloned to: ${targetPath}`);
}

/**
 * Run de RalphLoop Engine in een Docker container.
 *
 * @param dockerImage    De Docker image om te gebruiken
 * @param workspacePath  Het pad naar de workspace (wordt gemount)
 * @param filePath       Het bestand om te refactoren (relatief aan workspace)
 * @param dryRun         Of we in dry-run mode moeten draaien
 * @returns              Het resultaat van de RalphLoop
 */
async function runRalphLoopContainer(
  dockerImage: string,
  workspacePath: string,
  filePath: string,
  dryRun: boolean
): Promise<{
  success: boolean;
  iterations: number;
  finalErrors: number;
  finalTestFailures: number;
}> {
  logger.info(`🐳 Starting Docker container: ${dockerImage}`);

  // Build Docker run command
  // We mounten de workspace als volume en runnen de RalphLoop command
  const dryRunFlag = dryRun ? "--dry-run" : "";

  const dockerCmd = [
    "docker run",
    "--rm", // Remove container after exit
    `--memory=4g`, // Memory limit
    `--cpus=2`, // CPU limit
    `-v "${workspacePath}:/workspace"`, // Mount workspace
    `-e "ANTHROPIC_API_KEY=${process.env.ANTHROPIC_API_KEY}"`, // Pass API key
    dockerImage,
    "refactor", // The Laravel Zero command
    `/workspace/${filePath}`,
    dryRunFlag,
    "--output=json", // Request JSON output for parsing
  ]
    .filter(Boolean)
    .join(" ");

  logger.debug("Docker command", { cmd: dockerCmd });

  try {
    const { stdout } = await execAsync(dockerCmd, {
      timeout: CONFIG.perFileTimeout,
    });

    // Parse JSON output from the container
    const result = JSON.parse(stdout);

    return {
      success: result.success ?? false,
      iterations: result.iterations ?? 0,
      finalErrors: result.final_errors ?? -1,
      finalTestFailures: result.final_test_failures ?? -1,
    };
  } catch (error) {
    // Als de container faalt, log de error en return failure
    const errorMessage =
      error instanceof Error ? error.message : String(error);
    logger.error("Container execution failed", { error: errorMessage });

    return {
      success: false,
      iterations: 0,
      finalErrors: -1,
      finalTestFailures: -1,
    };
  }
}

// =============================================================================
// ADDITIONAL TASKS
// =============================================================================

/**
 * Health Check Task
 *
 * Een simpele task om te verifiëren dat de Trigger.dev setup werkt.
 * Kan gebruikt worden in monitoring/alerting.
 */
export const healthCheck = task({
  id: "night-shift-health-check",
  run: async () => {
    logger.info("🏥 Health check running");

    // Verify Docker is beschikbaar
    try {
      const { stdout } = await execAsync("docker --version");
      logger.info("Docker available", { version: stdout.trim() });
    } catch {
      logger.error("Docker not available");
      throw new Error("Docker is not available on this runner");
    }

    // Verify de Omega Engine image bestaat
    try {
      await execAsync(`docker image inspect ${CONFIG.defaultDockerImage}`);
      logger.info("Omega Engine image found");
    } catch {
      logger.warn("Omega Engine image not found, may need to be pulled");
    }

    return { healthy: true, timestamp: new Date().toISOString() };
  },
});
