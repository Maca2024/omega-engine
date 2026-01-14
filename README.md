# 🚀 Omega-PHP Refactoring Engine

> **De RalphLoop** - Autonome PHP code transformatie naar moderne PHP 8.4 standards met AI-assisted refactoring.

[![PHP Version](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php)](https://php.net)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%209-brightgreen)](https://phpstan.org)
[![License](https://img.shields.io/badge/License-Proprietary-red)](LICENSE)

---

## 📋 Wat is de Omega Engine?

De Omega Engine is een **volledig autonoom systeem** dat legacy PHP code (5.x/7.x) transformeert naar moderne PHP 8.4 standards. Het gebruikt een recursieve feedback-loop genaamd **"De RalphLoop"** om code te verbeteren totdat deze de **"Gold Standard"** bereikt:

| Gold Standard Criteria | Beschrijving |
|------------------------|--------------|
| ✅ PHPStan Level 9 | Hoogste niveau van static analysis |
| ✅ 100% Test Coverage | Alle code is getest |
| ✅ Strict Types | `declare(strict_types=1)` overal |
| ✅ PHP 8.4 Features | Constructor promotion, readonly, attributes |

---

## 🏗️ Architectuur

```
┌─────────────────────────────────────────────────────────────────────┐
│                         TRIGGER.DEV                                  │
│                   (Night Shift Orchestrator)                         │
│                    Scheduled of on-demand                            │
└──────────────────────────────┬──────────────────────────────────────┘
                               │
                               ▼
┌─────────────────────────────────────────────────────────────────────┐
│                      DOCKER CONTAINER                                │
│  ┌────────────────────────────────────────────────────────────────┐ │
│  │                    DE RALPHLOOP ENGINE                         │ │
│  │                                                                │ │
│  │   ┌──────────┐      ┌──────────┐      ┌──────────┐            │ │
│  │   │  RECTOR  │ ──▶  │ PHPSTAN  │ ──▶  │   PEST   │            │ │
│  │   │  Pass 1  │      │ Level 9  │      │  Tests   │            │ │
│  │   └──────────┘      └────┬─────┘      └────┬─────┘            │ │
│  │                          │                 │                   │ │
│  │                          ▼                 ▼                   │ │
│  │                    ┌─────────────────────────┐                 │ │
│  │                    │     ERRORS FOUND?       │                 │ │
│  │                    └───────────┬─────────────┘                 │ │
│  │                                │                               │ │
│  │              YES ◀─────────────┴─────────────▶ NO              │ │
│  │               │                               │                │ │
│  │               ▼                               ▼                │ │
│  │        ┌─────────────┐                ┌─────────────┐         │ │
│  │        │  CLAUDE AI  │                │  ✅ SUCCESS  │         │ │
│  │        │  Fix Code   │                │ Gold Standard│         │ │
│  │        └──────┬──────┘                └─────────────┘         │ │
│  │               │                                                │ │
│  │               └──────────── LOOP (max 10x) ◀──────────────────┤ │
│  │                                                                │ │
│  └────────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 🚀 Quick Start

### Vereisten

- [Docker](https://docker.com) (v20.10+)
- [Anthropic API Key](https://console.anthropic.com) (voor AI-assisted fixes)

### 1. Clone de Repository

```bash
git clone https://github.com/solvari/omega-engine.git
cd omega-engine
```

### 2. Build de Docker Image

```bash
cd docker
docker build -t solvari/omega-engine:latest .
```

### 3. Verifieer de Installatie

```bash
# Check PHP versie
docker run --rm solvari/omega-engine:latest php -v
# Output: PHP 8.4.16

# Check Omega CLI
docker run --rm -v "$(pwd)/../engine:/app" solvari/omega-engine:latest php omega --version
# Output: Omega Engine 1.0.0
```

---

## 📖 Gebruik

### Basis Commando

```bash
docker run --rm \
  -v "/pad/naar/je/code:/workspace" \
  -v "$(pwd)/engine:/app" \
  -e ANTHROPIC_API_KEY=sk-ant-xxx \
  solvari/omega-engine:latest \
  php omega refactor /workspace/bestand.php
```

### Command Opties

| Optie | Beschrijving | Default |
|-------|--------------|---------|
| `--dry-run` | Analyseer zonder wijzigingen | `false` |
| `--output=json` | Output in JSON formaat | `text` |
| `--max-iterations=N` | Maximum loop iteraties | `10` |

### Voorbeelden

#### 1. Dry Run (Analyse zonder wijzigingen)

```bash
docker run --rm \
  -v "C:/mijn-project:/workspace" \
  -v "C:/omega-engine/engine:/app" \
  solvari/omega-engine:latest \
  php omega refactor /workspace/src/LegacyController.php --dry-run
```

#### 2. Volledige Refactoring met AI

```bash
docker run --rm \
  -v "C:/mijn-project:/workspace" \
  -v "C:/omega-engine/engine:/app" \
  -e ANTHROPIC_API_KEY=sk-ant-api03-xxx \
  solvari/omega-engine:latest \
  php omega refactor /workspace/src/LegacyController.php
```

#### 3. JSON Output (voor CI/CD integratie)

```bash
docker run --rm \
  -v "C:/mijn-project:/workspace" \
  -v "C:/omega-engine/engine:/app" \
  -e ANTHROPIC_API_KEY=sk-ant-api03-xxx \
  solvari/omega-engine:latest \
  php omega refactor /workspace/src/LegacyController.php --output=json
```

#### 4. Windows PowerShell

```powershell
docker run --rm `
  -v "C:/Users/developer/project:/workspace" `
  -v "C:/Users/developer/omega-engine/engine:/app" `
  -e ANTHROPIC_API_KEY=$env:ANTHROPIC_API_KEY `
  solvari/omega-engine:latest `
  php omega refactor /workspace/src/OldClass.php
```

---

## 📊 Output Formaten

### Text Output (Default)

```
╔══════════════════════════════════════════════════════════════╗
║         OMEGA-PHP REFACTORING ENGINE - De RalphLoop          ║
║                   Solvari Engineering © 2024                 ║
╚══════════════════════════════════════════════════════════════╝

Target: /workspace/src/LegacyController.php
Mode: LIVE

📦 [PRE-LOOP] Running Rector (deterministic pass)...
✅ Rector applied transformations

🔄 [ITERATION 1/10]
  📊 Running PHPStan (Level 9)...
  📊 PHPStan errors: 5
  🧪 Running Pest tests...
  🧪 Pest failures: 0
  🤖 Requesting AI fix from Claude...
  ✅ AI fix applied

🔄 [ITERATION 2/10]
  📊 PHPStan errors: 0
  🧪 Pest failures: 0

🏆 GOLD STANDARD ACHIEVED!
   ✅ PHPStan Level 9: PASS
   ✅ Pest Tests: PASS
   📊 Iteraties nodig: 2
```

### JSON Output

```json
{
  "success": true,
  "iterations": 2,
  "final_errors": 0,
  "final_test_failures": 0,
  "history": [
    {
      "iteration": 1,
      "rector_applied": true,
      "phpstan_errors": 5,
      "pest_failures": 0,
      "ai_fix_applied": true
    },
    {
      "iteration": 2,
      "rector_applied": false,
      "phpstan_errors": 0,
      "pest_failures": 0,
      "ai_fix_applied": false
    }
  ]
}
```

---

## 🔧 Configuratie

### Rector (`engine/rector.php`)

De Rector configuratie past automatisch toe:

- ✅ PHP 8.4 upgrades (van 5.x/7.x)
- ✅ Dead code removal
- ✅ Type declarations toevoegen
- ✅ Constructor property promotion
- ✅ Readonly properties
- ✅ Attribute conversie

### PHPStan (`engine/phpstan.neon`)

- Level 9 (maximum strictness)
- Strict rules enabled
- Deprecation rules
- No implicit mixed types

---

## 📂 Project Structuur

```
omega-engine/
├── docker/
│   ├── Dockerfile              # PHP 8.4 Alpine container
│   └── docker-compose.yml      # Development setup
│
├── engine/
│   ├── app/
│   │   ├── Commands/
│   │   │   └── RefactorCommand.php    # CLI interface
│   │   ├── Providers/
│   │   │   └── AppServiceProvider.php
│   │   └── Services/
│   │       └── RalphLoop.php          # 🔑 CORE ENGINE
│   │
│   ├── config/
│   │   ├── app.php
│   │   └── commands.php
│   │
│   ├── tests/
│   │   ├── Feature/
│   │   │   └── RalphLoopTest.php
│   │   └── Pest.php
│   │
│   ├── composer.json
│   ├── rector.php              # Rector configuratie
│   ├── phpstan.neon            # PHPStan Level 9
│   └── omega                   # CLI entry point
│
├── trigger/
│   ├── jobs/
│   │   └── nightShift.ts       # Trigger.dev orchestrator
│   ├── package.json
│   └── trigger.config.ts
│
└── README.md
```

---

## 🔄 De RalphLoop Algoritme

```
┌─────────────────────────────────────────┐
│           START                         │
└─────────────────┬───────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  1. RECTOR PASS (Deterministic)         │
│     - PHP version upgrades              │
│     - Dead code removal                 │
│     - Type declarations                 │
└─────────────────┬───────────────────────┘
                  ▼
┌─────────────────────────────────────────┐
│  2. PHPSTAN ANALYSIS (Level 9)          │◀──────────┐
│     - Static type checking              │           │
│     - Capture all errors                │           │
└─────────────────┬───────────────────────┘           │
                  ▼                                   │
┌─────────────────────────────────────────┐           │
│  3. PEST TESTS                          │           │
│     - Run test suite                    │           │
│     - Capture failures                  │           │
└─────────────────┬───────────────────────┘           │
                  ▼                                   │
        ┌─────────────────┐                           │
        │ Errors == 0 AND │                           │
        │ Failures == 0?  │                           │
        └────────┬────────┘                           │
                 │                                    │
     YES ◀───────┴───────▶ NO                         │
      │                    │                          │
      ▼                    ▼                          │
┌──────────┐    ┌─────────────────────┐               │
│ SUCCESS! │    │ 4. CLAUDE AI FIX    │               │
│ Gold     │    │    - Send errors    │               │
│ Standard │    │    - Get fixed code │               │
└──────────┘    │    - Apply changes  │               │
                └──────────┬──────────┘               │
                           │                          │
                           └──────────────────────────┘
                              (max 10 iterations)
```

---

## 🌙 Trigger.dev Integratie (Night Shift)

Voor automatische nachtelijke runs:

### Setup

```bash
cd trigger
npm install
npx trigger login
npx trigger deploy
```

### Cron Schedule

```typescript
// In trigger.config.ts
schedules.task({
  id: "night-shift-cron",
  task: nightShift,
  cron: "0 2 * * *", // Elke nacht om 02:00
});
```

### Handmatig Triggeren

```typescript
await nightShift.trigger({
  todoList: [
    { id: "1", filePath: "src/Legacy/OldController.php", priority: 1 },
    { id: "2", filePath: "src/Legacy/OldService.php", priority: 2 },
  ],
  dryRun: false,
});
```

---

## 🧪 Development

### Lokaal Testen

```bash
# Start interactive shell in container
docker run -it --rm \
  -v "C:/omega-engine/engine:/app" \
  solvari/omega-engine:latest \
  bash

# In de container:
composer test          # Run Pest tests
composer analyse       # Run PHPStan
composer rector        # Run Rector (dry-run)
```

### Tests Uitvoeren

```bash
docker run --rm \
  -v "C:/omega-engine/engine:/app" \
  solvari/omega-engine:latest \
  composer test
```

---

## 🔐 Environment Variables

| Variable | Beschrijving | Verplicht |
|----------|--------------|-----------|
| `ANTHROPIC_API_KEY` | Claude API key voor AI fixes | Ja (voor live mode) |
| `PHP_MEMORY_LIMIT` | PHP memory limit | Nee (default: 2G) |

---

## ⚠️ Beperkingen

1. **Single File Focus**: De huidige versie werkt het beste op individuele bestanden
2. **Test Coverage**: Vereist bestaande tests voor volledige validatie
3. **API Costs**: AI fixes gebruiken Claude API calls (kosten per call)
4. **Max Iterations**: Na 10 iteraties stopt de loop (menselijke interventie nodig)

---

## 🛣️ Roadmap

- [ ] Multi-file refactoring support
- [ ] Git integration (auto-commit per iteratie)
- [ ] Slack/Teams notifications
- [ ] Cost tracking dashboard
- [ ] Custom rule definitions

---

## 📜 License

Proprietary - Solvari Engineering © 2024

---

## 🤝 Contributing

Intern Solvari project. Neem contact op met het Engineering team voor bijdragen.

---

<p align="center">
  <strong>Built with ❤️ by Solvari Engineering</strong><br>
  <em>Powered by Claude AI, Rector, PHPStan & Pest</em>
</p>
