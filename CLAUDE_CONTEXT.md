# OMEGA-PHP REFACTORING ENGINE - Project Context

> Dit bestand helpt Claude Code snel op gang te komen bij een nieuwe sessie.

---

## 🎯 Project Overview

**Naam:** Omega PHP Refactoring Engine (De RalphLoop)
**Repository:** https://github.com/Maca2024/omega-engine
**Eigenaar:** AetherLink.AI Tech Engineering
**Doel:** Autonome transformatie van legacy PHP naar moderne PHP 8.4 met AI-powered self-healing

---

## 🏗️ Architectuur

```
omega-engine/
├── docker/                    # PHP 8.4 Alpine container
│   └── Dockerfile
├── engine/                    # Laravel Zero CLI applicatie
│   ├── app/
│   │   ├── Commands/
│   │   │   └── RefactorCommand.php
│   │   └── Services/
│   │       └── RalphLoop.php      ← CORE ENGINE
│   ├── rector.php
│   ├── phpstan.neon
│   └── omega                      ← CLI entry point
├── test-files/
│   ├── legacy/                    # Input: biohazard legacy code
│   ├── modern/                    # Output: transformatie #1 (32 tests)
│   └── modern-v2/                 # Output: transformatie #2 (98 tests)
└── trigger/                       # Trigger.dev orchestrator (optioneel)
```

---

## 🔄 De RalphLoop - Hoe Het Werkt

```
┌─────────────┐
│ Legacy PHP  │
└──────┬──────┘
       ▼
┌─────────────┐
│   RECTOR    │ ← Automatische syntax upgrades
└──────┬──────┘
       ▼
┌─────────────────────────────────────┐
│  LOOP (max 10 iteraties):           │
│  1. PHPStan Level 9 analyse         │
│  2. Pest tests uitvoeren            │
│  3. Claude AI fixt errors           │
│  └─→ Herhaal tot 0 errors           │
└──────┬──────────────────────────────┘
       ▼
┌─────────────┐
│ GOLD STD ✅ │ ← PHPStan Level 9 PASS
└─────────────┘
```

---

## 📊 Huidige Status

### Voltooide Transformaties

| Legacy File | Modern Output | Files | Tests | Lines |
|-------------|---------------|-------|-------|-------|
| `OrderProcess_Controller.php` | `test-files/modern/` | 26 | 32 | 987 |
| `ToxicOrderProcessor_v2_FINAL.php` | `test-files/modern-v2/` | 50 | 98 | 2,218 |
| **TOTAAL** | | **76** | **130** | **3,205** |

### Security Fixes Toegepast

- ✅ SQL Injection → PDO Prepared Statements
- ✅ XSS → htmlspecialchars() escaping
- ✅ CSRF → CsrfTokenManager
- ✅ eval() RCE → Type-safe DiscountRules
- ✅ Register Globals → Explicit validation
- ✅ mysql_* → PDO
- ✅ serialize() → JSON encoding

---

## 🛠️ Development Commands

### Engine Runnen (Docker)

```bash
# Dry-run (alleen analyse)
docker run --rm \
  -v "C:/pad/naar/project:/workspace" \
  -v "C:/Users/info/omega-engine/engine:/app" \
  aetherlink/omega-engine:latest \
  php omega refactor /workspace/src/File.php --dry-run

# Live refactoring met AI
docker run --rm \
  -v "C:/pad/naar/project:/workspace" \
  -v "C:/Users/info/omega-engine/engine:/app" \
  -e ANTHROPIC_API_KEY=sk-ant-xxx \
  aetherlink/omega-engine:latest \
  php omega refactor /workspace/src/File.php
```

### Tests Runnen

```bash
# Modern transformatie #1
cd test-files/modern && composer install && ./vendor/bin/pest

# Modern transformatie #2
cd test-files/modern-v2 && composer install && ./vendor/bin/pest
```

### Docker Image Builden

```bash
cd docker
docker build -t aetherlink/omega-engine:latest .
```

---

## 📁 Belangrijke Bestanden

| Bestand | Beschrijving |
|---------|--------------|
| `engine/app/Services/RalphLoop.php` | Core refactoring loop logic |
| `engine/app/Commands/RefactorCommand.php` | CLI interface |
| `engine/rector.php` | Rector configuratie |
| `engine/phpstan.neon` | PHPStan Level 9 config |
| `docker/Dockerfile` | PHP 8.4 container definitie |
| `README.md` | Volledige documentatie |

---

## 🧪 Test Suite Structuur

### modern/ (32 tests)
```
tests/Unit/Domain/Order/
├── DTOs/
│   ├── CartItemDTOTest.php
│   └── OrderTotalsDTOTest.php
├── Enums/
│   ├── OrderStatusTest.php
│   └── VatRateTest.php
└── Services/
    └── OrderCalculationServiceTest.php
```

### modern-v2/ (98 tests)
```
tests/Unit/
├── Domain/
│   ├── Cart/DTOs/
│   │   ├── CartDTOTest.php
│   │   └── CartItemDTOTest.php
│   ├── Catalog/Enums/
│   │   ├── ProductStatusTest.php
│   │   └── VatCategoryTest.php
│   ├── Order/Enums/
│   │   └── OrderStatusTest.php
│   └── Pricing/
│       ├── DTOs/PriceBreakdownDTOTest.php
│       └── Services/DiscountCalculatorTest.php
└── Http/
    ├── Security/CsrfTokenManagerTest.php
    └── Validation/OrderRequestValidatorTest.php
```

---

## 🚀 Volgende Stappen (Roadmap)

- [ ] Multi-file refactoring support
- [ ] Git integration (auto-commit per iteratie)
- [ ] Slack/Teams notifications
- [ ] Cost tracking dashboard
- [ ] Custom rule definitions
- [ ] Web UI

---

## 💡 Tips voor Claude

1. **Engine code** staat in `engine/app/Services/RalphLoop.php`
2. **Moderne transformaties** staan in `test-files/modern/` en `test-files/modern-v2/`
3. **PHPStan Level 9** is de standaard - geen `mixed` types toegestaan
4. **Pest PHP** voor testing - gebruik `describe()` en `it()` syntax
5. **Docker** is vereist om de engine te runnen

---

## 📞 Snelle Referenties

- **GitHub:** https://github.com/Maca2024/omega-engine
- **Anthropic Console:** https://console.anthropic.com
- **PHPStan Docs:** https://phpstan.org/user-guide/rule-levels
- **Pest Docs:** https://pestphp.com/docs/writing-tests
- **Rector Docs:** https://getrector.com/documentation

---

*Laatst bijgewerkt: Januari 2025*
