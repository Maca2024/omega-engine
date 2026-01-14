/**
 * ==============================================================================
 * TRIGGER.DEV CONFIGURATIE
 * ==============================================================================
 *
 * Dit bestand configureert het Trigger.dev project voor de Omega Engine.
 *
 * @see https://trigger.dev/docs/config
 */

import { defineConfig } from "@trigger.dev/sdk/v3";

export default defineConfig({
  // ===========================================================================
  // PROJECT IDENTIFICATIE
  // ===========================================================================
  // Dit moet matchen met je project ID in het Trigger.dev dashboard.
  // ===========================================================================
  project: "solvari-omega-engine",

  // ===========================================================================
  // RUNTIME CONFIGURATIE
  // ===========================================================================
  runtime: "node",

  // ===========================================================================
  // LOG LEVEL
  // ===========================================================================
  // In production wil je waarschijnlijk "info" of "warn".
  // "debug" is handig tijdens development.
  // ===========================================================================
  logLevel: "info",

  // ===========================================================================
  // RETRY CONFIGURATIE
  // ===========================================================================
  // Default retry settings voor alle tasks.
  // Individuele tasks kunnen dit overriden.
  // ===========================================================================
  retries: {
    enabledInDev: false,
    default: {
      maxAttempts: 3,
      minTimeoutInMs: 1000,
      maxTimeoutInMs: 60000,
      factor: 2,
    },
  },

  // ===========================================================================
  // DIRECTORIES
  // ===========================================================================
  // Waar Trigger.dev moet zoeken naar task definities.
  // ===========================================================================
  dirs: ["./jobs"],

  // ===========================================================================
  // BUILD CONFIGURATIE
  // ===========================================================================
  build: {
    // We gebruiken ESM modules
    format: "esm",

    // External dependencies die niet gebundeld moeten worden
    external: [],
  },

  // ===========================================================================
  // MACHINE CONFIGURATIE
  // ===========================================================================
  // Specificeer welke machine specs je wilt voor de tasks.
  // De Night Shift is resource-intensief (Docker, AI calls).
  // ===========================================================================
  machine: "medium-2x", // 4 vCPU, 8GB RAM

  // ===========================================================================
  // TELEMETRY
  // ===========================================================================
  // Enable telemetry voor debugging en monitoring.
  // ===========================================================================
  telemetry: {
    exporters: [
      // Je kunt hier exporters toevoegen voor je observability stack
      // bijv. OpenTelemetry, Datadog, etc.
    ],
  },
});
