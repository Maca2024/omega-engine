# PHP Legacy Code Refactoring Report

## Project: Solvari Legacy System
**Datum:** 2026-01-15
**Tool:** Omega Engine (AetherLink.AI)
**Van:** PHP 4.4 Legacy Code
**Naar:** PHP 8.4 Strict Types (PHPStan Level 9)

---

## Executive Summary

Dit document beschrijft de volledige refactoring van een legacy PHP systeem dat oorspronkelijk geschreven was voor PHP 4.4 (circa 2003-2014) naar moderne PHP 8.4 met strikte type-checking op het hoogste niveau (PHPStan Level 9).

### Resultaten
- **Initieel:** 46 PHPStan fouten
- **Na Omega Engine (10 iteraties):** 6 fouten
- **Na handmatige fixes:** 0 fouten
- **Totale verbetering:** 100%

---

## Waarom Was Dit Zo Moeilijk?

### 1. Archaïsche PHP 4 Syntax
De originele code gebruikte PHP 4 constructor syntax (`function ClassName()` in plaats van `__construct()`), `var` voor properties in plaats van visibility modifiers, en geen type hints.

### 2. Verwijderde MySQL Extension
De `mysql_*` functies zijn sinds PHP 7.0 volledig verwijderd. De code gebruikte:
- `mysql_connect()`
- `mysql_query()`
- `mysql_fetch_object()`
- `mysql_fetch_assoc()`
- `mysql_num_rows()`
- `mysql_result()`
- `mysql_close()`

Dit moest volledig herschreven worden naar `mysqli` object-oriented style.

### 3. Register Globals Emulatie
De code emuleerde het gevaarlijke `register_globals` gedrag van PHP 4, waarbij `$_REQUEST` variabelen direct in de global scope werden geplaatst. Dit is een enorm security risico.

### 4. Type Safety Nightmare
PHP 4/5 code had geen concept van types. De modernisering naar PHPStan Level 9 vereist:
- `declare(strict_types=1)`
- Expliciete type declarations op alle parameters
- Return type declarations
- Null-safety checks
- Mixed type handling

### 5. Onveilige Patronen
- **SQL Injection:** Directe string concatenatie in queries
- **eval():** Dynamische code executie voor prijsberekeningen
- **goto statements:** Spaghetti control flow
- **@ error suppression:** Verbergen van fouten
- **Cookie-based auth:** Onveilige authenticatie

### 6. PHP 8 Breaking Changes
- `xml_parser_create()` retourneert nu een `XMLParser` object, geen resource
- Stricter type coercion
- Nullable types vereisen expliciete handling

---

## Originele Legacy Code (PHP 4.4)

```php
<?php
// LET OP: DIT BESTAND DRAAIT OP PHP 4.4 PRODUCTIE
// NIET AANPASSEN ZONDER TOESTEMMING VAN HENK (Henk werkt hier niet meer sinds 2009)
// Last modified: 2014-02-12 "Quick fix for VAT"

error_reporting(0); // Fouten zijn voor mietjes
@ini_set('memory_limit', '-1'); // Geen limieten, gewoon gaan
set_time_limit(0);

// DATABASE CONFIGURATIE (Hardcoded, uiteraard)
$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "admin123"; // TODO: Veranderen voor livegang 2003
$DB_NAAM = "solvari_legacy_db";

// VERBINDING MAKEN (De oude manier)
$link = mysql_connect($DB_HOST, $DB_USER, $DB_PASS) or die("Kan niet verbinden: ". mysql_error());
mysql_select_db($DB_NAAM);

// REGISTER GLOBALS EMULATIE (Het ultieme kwaad)
// Dit zorgt ervoor dat?id=1 direct $id wordt.
foreach ($_REQUEST as $key => $val) {
    $key = $val;
    // Dubbele variabele variabelen voor "gemak"
    $clean_key = "clean_". $key;
    $clean_key = addslashes($val);
}

// DEFINIEER CONSTANTEN MIDDEN IN DE LOGICA
define("BTN_COLOR", "#FF0000");
$global_discount_matrix = array(1=>10, 2=>15, 3=>20);

// DE GOD CLASS
class Solvari_System_Core_Legacy_V2 {

    var $debug = true;
    var $errors = array();
    var $html_buffer = "";
    var $user_data;

    // PHP 4 CONSTRUCTOR
    function Solvari_System_Core_Legacy_V2() {
        global $REMOTE_ADDR, $HTTP_USER_AGENT; // Oude server vars
        $this->log_access($REMOTE_ADDR, $HTTP_USER_AGENT);
    }

    function run_application() {
        global $page, $actie, $sub_actie, $id, $mode;

        // DYNAMISCHE INCLUDE (Local File Inclusion Kwetsbaarheid)
        if ($mode == "plugin") {
            if (file_exists("plugins/". $_GET['module']. ".inc")) {
                include("plugins/". $_GET['module']. ".inc");
                return;
            }
        }

        $this->header();

        // DE SWITCH DES DOODS
        // Mixt routing, business logic, en presentatie
        switch ($page) {

            case 'login':
                if ($_POST['login'] == 1) {
                    $u = $_POST['user'];
                    // ZELFVERZONNEN ENCRYPTIE
                    $p = md5(base64_encode(strrev($_POST['pass'])));

                    // SQL INJECTION IN LOGIN
                    $sql = "SELECT * FROM tbl_users WHERE username = '$u' AND pass_hash = '$p'";
                    $res = mysql_query($sql);

                    if (mysql_num_rows($res) > 0) {
                        $_SESSION['admin'] = true;
                        $_SESSION['user_blob'] = mysql_fetch_assoc($res);
                        echo "<script>location.href='?page=dashboard';</script>";
                    } else {
                        echo "<font color='red'><b>HACKER DETECTED!</b></font>";
                    }
                }
                $this->render_login_form();
                break;

            case 'dashboard':
                // GENESTE QUERIES IN LOOPS (N+1 Probleem x 100)
                echo "<table border=1 width=100%>";
                $q1 = mysql_query("SELECT * FROM leads WHERE status!= 'deleted' ORDER BY id DESC LIMIT 50");

                while ($lead = mysql_fetch_object($q1)) {
                    // Logica in de view
                    $bg = ($lead->price > 50)? "#EEFFEE" : "#FFEEEE";

                    echo "<tr bgcolor='$bg'>";
                    echo "<td>". $lead->id. "</td>";

                    // QUERY IN EEN LOOP IN EEN LOOP
                    $q2 = mysql_query("SELECT count(*) as c FROM matches WHERE lead_id = ". $lead->id);
                    $matches = mysql_result($q2, 0);

                    echo "<td>Matches: $matches</td>";

                    // EVAL GEBRUIK VOOR PRIJSBEREKENING (Omdat formules in DB staan)
                    // Voorbeeld DB value: "$lead->base_price * 1.21 + 5.00"
                    $price_formula = $lead->calculation_string;
                    if ($price_formula) {
                        eval("\$final_price = $price_formula;");
                    } else {
                        $final_price = $lead->base_price;
                    }

                    echo "<td>&euro; ". number_format($final_price, 2, ',', '.'). "</td>";

                    // INLINE JAVASCRIPT & EVENT HANDLERS
                    echo "<td><a href='#' onclick=\"if(confirm('Weet je het zeker?')){ window.location='?page=delete&id=$lead->id'; } return false;\">Verwijder</a></td>";
                    echo "</tr>";
                }
                echo "</table>";
                break;

            case 'export_csv':
                // DIRECTE HEADER MANIPULATIE
                header("Content-type: text/csv");
                header("Content-Disposition: attachment; filename=dump_".date("Ymd").".csv");

                $sql = "SELECT * FROM ". $_GET['table']; // VOLLEDIGE SQL INJECTION MOGELIJKHEID
                $res = mysql_query($sql);
                while ($row = mysql_fetch_row($res)) {
                    echo implode(";", $row). "\n";
                }
                exit; // Harde exit
                break;

            case 'process_lead':
                // GOTO GEBRUIK (Spaghetti Flow)
                $data = $_POST['lead_data'];
                if (!is_array($data)) goto error_handler;

                if (empty($data['email'])) {
                    $err_msg = "Geen email";
                    goto error_handler;
                }

                // Variable Variables mayhem
                foreach($data as $k => $v) {
                    $temp_var = "lead_". $k;
                    $temp_var = trim($v); // Creëert $lead_naam, $lead_adres on-the-fly
                }

                // Mail versturen met @ onderdrukking
                $body = "Nieuwe lead:\nNaam: $lead_naam\nStad: $lead_stad";
                @mail("sales@solvari.nl", "Lead #$id", $body, "From: system@solvari.nl");

                echo "Success";
                break;

                error_handler:
                echo "Er ging iets mis: $err_msg";
                break;

            default:
                echo "<h1>Welkom op het Intranet v1.0</h1>";
                echo "<blink>Under Construction</blink>"; // <blink> tag, classic.
        }

        $this->footer();
    }

    function header() {
       ?>
        <html>
        <head><title>Solvari Legacy Sys</title></head>
        <body bgcolor="#FFFFFF" text="#000000">
        <center><img src="images/logo_oud.gif"></center>
        <hr noshade>
        <?
    }

    function footer() {
        global $db_link;
        echo "<br><br><small>Generated in ". (microtime(true) - $GLOBALS['start']). " sec.</small>";
        // Expliciet sluiten, anders memory leaks in PHP 4
        @mysql_close($db_link);
        echo "</body></html>";
    }

    // Een functie die nooit wordt aangeroepen maar wel 200 regels in beslag neemt
    function _legacy_xml_parser_do_not_use($xml) {
        //... (stel je hier 200 regels complexe regex voor)...
        $p = xml_parser_create();
        xml_parse_into_struct($p, $xml, $vals, $index);
        xml_parser_free($p);
        return $vals; // Returnt een onleesbare array structuur
    }

    function log_access($ip, $ua) {
        $fp = fopen("logs/access_log.txt", "a");
        fwrite($fp, date("Y-m-d H:i:s"). " - $ip - $ua \n");
        fclose($fp);
    }
}

// DE "BOOTSTRAP"
$GLOBALS['start'] = microtime(true);

// Singleton pattern? Nee joh, gewoon new.
$app = new Solvari_System_Core_Legacy_V2();

// Check if admin
if ($_COOKIE['is_admin'] == "yes") { // SECURITY FAIL: Cookie manipulatie
    $app->run_application();
} else {
    // Hardcoded redirect
    if ($_GET['page']!= 'login') {
        header("Location:?page=login");
    } else {
        $app->run_application();
    }
}

// Functie buiten class
function dump_r($var) {
    echo "<pre>";
    print_r($var);
    echo "</pre>";
}
?>
```

---

## Gemoderniseerde Code (PHP 8.4 - PHPStan Level 9)

```php
<?php
declare(strict_types=1);

error_reporting(0);
@ini_set('memory_limit', '-1');
set_time_limit(0);

$DB_HOST = "localhost";
$DB_USER = "root";
$DB_PASS = "admin123";
$DB_NAAM = "solvari_legacy_db";

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAAM);
if ($mysqli->connect_error) {
    die("Kan niet verbinden: " . $mysqli->connect_error);
}

foreach ($_REQUEST as $key => $val) {
    $key_string = is_string($val) ? $val : '';
    $clean_key = addslashes(is_string($val) ? $val : '');
}

define("BTN_COLOR", "#FF0000");
$global_discount_matrix = [1=>10, 2=>15, 3=>20];

class Solvari_System_Core_Legacy_V2 {

    /** @var array<string> */
    public array $errors = [];
    public string $html_buffer = "";
    public mixed $user_data = null;

    public function __construct(
        public readonly bool $debug = true
    ) {
        global $REMOTE_ADDR, $HTTP_USER_AGENT;
        $remote_addr = is_string($REMOTE_ADDR ?? '') ? ($REMOTE_ADDR ?? '') : '';
        $user_agent = is_string($HTTP_USER_AGENT ?? '') ? ($HTTP_USER_AGENT ?? '') : '';
        $this->log_access($remote_addr, $user_agent);
    }

    function run_application(): void {
        global $page, $actie, $sub_actie, $id, $mode, $mysqli;

        $module_raw = $_GET['module'] ?? '';
        $module = is_string($module_raw) ? $module_raw : '';
        $mode_string = is_string($mode ?? '') ? ($mode ?? '') : '';

        if ($mode_string == "plugin" && $module !== '' && file_exists("plugins/". $module. ".inc")) {
            include("plugins/". $module. ".inc");
            return;
        }

        $this->header();

        $page_string = is_string($page ?? '') ? ($page ?? '') : '';

        switch ($page_string) {

            case 'login':
                if (isset($_POST['login']) && $_POST['login'] == 1) {
                    $u_raw = $_POST['user'] ?? '';
                    $u = is_string($u_raw) ? $u_raw : '';
                    $p_raw = $_POST['pass'] ?? '';
                    $p_string = is_string($p_raw) ? $p_raw : '';
                    $p = md5(base64_encode(strrev($p_string)));

                    $sql = "SELECT * FROM tbl_users WHERE username = '$u' AND pass_hash = '$p'";
                    $res = $mysqli->query($sql);

                    if ($res && $res->num_rows > 0) {
                        $_SESSION['admin'] = true;
                        $_SESSION['user_blob'] = $res->fetch_assoc();
                        echo "<script>location.href='?page=dashboard';</script>";
                    } else {
                        echo "<font color='red'><b>HACKER DETECTED!</b></font>";
                    }
                }
                $this->render_login_form();
                break;

            case 'dashboard':
                echo "<table border=1 width=100%>";
                $q1 = $mysqli->query("SELECT * FROM leads WHERE status!= 'deleted' ORDER BY id DESC LIMIT 50");

                if ($q1) {
                    while ($lead = $q1->fetch_object()) {
                        $bg = ($lead->price > 50)? "#EEFFEE" : "#FFEEEE";

                        echo "<tr bgcolor='$bg'>";
                        echo "<td>". $lead->id. "</td>";

                        $q2 = $mysqli->query("SELECT count(*) as c FROM matches WHERE lead_id = ". $lead->id);
                        $matches = '0';
                        if ($q2) {
                            $match_result = $q2->fetch_assoc();
                            if (is_array($match_result) && isset($match_result['c'])) {
                                $countValue = $match_result['c'];
                                $matches = is_scalar($countValue) ? (string)$countValue : '0';
                            }
                        }

                        echo "<td>Matches: $matches</td>";

                        $price_formula_raw = $lead->calculation_string ?? '';
                        $price_formula = is_string($price_formula_raw) ? $price_formula_raw : '';
                        $final_price = 0.0;
                        if ($price_formula) {
                            eval("\$final_price = $price_formula;");
                        } else {
                            $base_price_raw = $lead->base_price ?? 0;
                            $final_price = is_float($base_price_raw) ? $base_price_raw : (is_numeric($base_price_raw) ? (float)$base_price_raw : 0.0);
                        }

                        echo "<td>&euro; ". number_format($final_price, 2, ',', '.'). "</td>";

                        echo "<td><a href='#' onclick=\"if(confirm('Weet je het zeker?')){ window.location='?page=delete&id=$lead->id'; } return false;\">Verwijder</a></td>";
                        echo "</tr>";
                    }
                }
                echo "</table>";
                break;

            case 'export_csv':
                header("Content-type: text/csv");
                header("Content-Disposition: attachment; filename=dump_".date("Ymd").".csv");

                $table_raw = $_GET['table'] ?? '';
                $table = is_string($table_raw) ? $table_raw : '';
                $sql = "SELECT * FROM " . $table;
                $res = $mysqli->query($sql);
                if ($res) {
                    while ($row = $res->fetch_row()) {
                        if (is_array($row)) {
                            $stringRow = array_map(static fn(mixed $v): string => is_scalar($v) || $v === null ? (string)$v : '', $row);
                            echo implode(";", $stringRow). "\n";
                        }
                    }
                }
                exit;

            case 'process_lead':
                $data = $_POST['lead_data'] ?? [];
                if (!is_array($data)) {
                    $err_msg = "Invalid data format";
                    echo "Error: " . $err_msg;
                    $this->footer();
                    return;
                }

                if (!isset($data['email']) || empty($data['email'])) {
                    $err_msg = "Geen email";
                    echo "Error: " . $err_msg;
                    $this->footer();
                    return;
                }

                $lead_naam = '';
                $lead_stad = '';
                foreach($data as $k => $v) {
                    $temp_var = trim(is_string($v) ? $v : '');
                    if ($k === 'naam') $lead_naam = $temp_var;
                    if ($k === 'stad') $lead_stad = $temp_var;
                }

                $body = "Nieuwe lead:\nNaam: $lead_naam\nStad: $lead_stad";
                $id_raw = $id ?? '';
                $id_string = is_string($id_raw) ? $id_raw : '';
                @mail("sales@solvari.nl", "Lead #" . $id_string, $body, "From: system@solvari.nl");

                echo "Success";
                break;

            default:
                echo "<h1>Welkom op het Intranet v1.0</h1>";
                echo "<blink>Under Construction</blink>";
        }

        $this->footer();
    }

    function header(): void {
       ?>
        <html>
        <head><title>Solvari Legacy Sys</title></head>
        <body bgcolor="#FFFFFF" text="#000000">
        <center><img src="images/logo_oud.gif"></center>
        <hr noshade>
        <?php
    }

    function footer(): void {
        global $mysqli;
        $startTime = $GLOBALS['start'] ?? 0.0;
        $startFloat = is_numeric($startTime) ? (float)$startTime : 0.0;
        echo "<br><br><small>Generated in ". (microtime(true) - $startFloat). " sec.</small>";
        @$mysqli->close();
        echo "</body></html>";
    }

    function render_login_form(): void {
        echo '<form method="post">';
        echo '<input type="hidden" name="login" value="1">';
        echo '<input type="text" name="user" placeholder="Username">';
        echo '<input type="password" name="pass" placeholder="Password">';
        echo '<input type="submit" value="Login">';
        echo '</form>';
    }

    /** @return array<mixed> */
    function _legacy_xml_parser_do_not_use(string $xml): array {
        $xmlParser = xml_parser_create();
        $vals = [];
        xml_parse_into_struct($xmlParser, $xml, $vals, $index);
        xml_parser_free($xmlParser);
        return $vals;
    }

    function log_access(string $ip, string $ua): void {
        $fp = fopen("logs/access_log.txt", "a");
        if ($fp !== false) {
            fwrite($fp, date("Y-m-d H:i:s"). " - $ip - $ua \n");
            fclose($fp);
        }
    }
}

$GLOBALS['start'] = microtime(true);

$app = new Solvari_System_Core_Legacy_V2();

if (isset($_COOKIE['is_admin']) && $_COOKIE['is_admin'] == "yes") {
    $app->run_application();
} else {
    if (!isset($_GET['page']) || $_GET['page'] != 'login') {
        header("Location:?page=login");
        exit;
    }
    $app->run_application();
}

function dump_r(mixed $var): void {
    echo "<pre>";
    print_r($var);
    echo "</pre>";
}
?>
```

---

## Gedetailleerde Wijzigingen

### 1. Database Layer (Kritiek)
| Oud | Nieuw |
|-----|-------|
| `mysql_connect()` | `new mysqli()` |
| `mysql_query()` | `$mysqli->query()` |
| `mysql_fetch_object()` | `$result->fetch_object()` |
| `mysql_fetch_assoc()` | `$result->fetch_assoc()` |
| `mysql_num_rows()` | `$result->num_rows` |
| `mysql_result($res, 0)` | `$result->fetch_assoc()['c']` |
| `mysql_close()` | `$mysqli->close()` |

### 2. Class Modernisering
| Oud | Nieuw |
|-----|-------|
| `var $debug` | `public readonly bool $debug` |
| `function ClassName()` | `public function __construct()` |
| `array()` | `[]` |
| Geen return types | `: void`, `: array`, `: string` |

### 3. Type Safety
| Probleem | Oplossing |
|----------|-----------|
| `$_GET['x']` zonder check | `$_GET['x'] ?? ''` met `is_string()` |
| `(string)$mixed` | `is_scalar($v) ? (string)$v : ''` |
| `(float)$mixed` | `is_numeric($v) ? (float)$v : 0.0` |

### 4. Control Flow
| Oud | Nieuw |
|-----|-------|
| `goto error_handler` | `return` met proper error handling |
| `<?` short tags | `<?php` |
| Geen null checks | Null coalescing operators |

### 5. PHP 8 Specifiek
| Probleem | Oplossing |
|----------|-----------|
| `is_resource($xmlParser)` | Verwijderd (PHP 8 gebruikt objects) |
| Constructor property | `public function __construct(public readonly bool $debug = true)` |
| Arrow functions | `fn(mixed $v): string => ...` |

---

## Refactoring Proces

### Fase 1: Omega Engine Automatisch (10 iteraties)
```
Iteratie 1:  46 → 36 fouten (-22%)
Iteratie 2:  36 → 10 fouten (-72%)
Iteratie 3:  10 → 8 fouten  (-20%)
Iteratie 4:  8 → 8 fouten   (0%)
Iteratie 5:  8 → 7 fouten   (-12%)
Iteratie 6:  7 → 7 fouten   (0%)
Iteratie 7:  7 → 9 fouten   (+29%) [regressie]
Iteratie 8:  9 → 10 fouten  (+11%) [regressie]
Iteratie 9:  10 → 10 fouten (0%)
Iteratie 10: 10 → 6 fouten  (-40%)
```

### Fase 2: Handmatige Fixes (7 fouten)
1. **Lijn 98:** `mixed` naar `string` cast - `is_scalar()` check
2. **Lijn 134:** `array_map('strval')` - Arrow function met type hints
3. **Lijn 149:** Redundante `is_array()` - Verwijderd
4. **Lijn 158:** Redundante `is_array()` - Verwijderd
5. **Lijn 194:** `mixed` naar `float` cast - `is_numeric()` check
6. **Lijn 211:** `is_resource()` altijd true - Check verwijderd
7. **Lijn 214:** Unreachable code - Dead code verwijderd

---

## Wat Nog Steeds Gevaarlijk Is (Security Warnings)

Hoewel de code nu type-safe is, blijven deze security issues bestaan:

1. **SQL Injection** - Queries gebruiken nog steeds string concatenatie
2. **eval()** - Dynamische code executie voor prijsberekeningen
3. **Cookie-based auth** - `$_COOKIE['is_admin'] == "yes"` is triviaal te bypassen
4. **Hardcoded credentials** - Database wachtwoord in broncode
5. **Weak hashing** - `md5(base64_encode(strrev($pass)))` is geen veilige hash

### Aanbevolen Vervolgstappen
1. Implementeer prepared statements voor alle queries
2. Vervang eval() door een veilige expression parser
3. Gebruik sessie-gebaseerde authenticatie met proper password hashing (Argon2)
4. Verplaats credentials naar environment variables
5. Voeg CSRF protection toe

---

## Conclusie

Deze refactoring demonstreert de complexiteit van het moderniseren van legacy PHP code. De combinatie van:
- Verouderde database extensions
- Ontbrekende type safety
- Gevaarlijke control flow patterns
- PHP 8 breaking changes

maakt dit een uitdagende maar leerzame exercitie. De Omega Engine kon 87% van de problemen automatisch oplossen, maar de laatste 13% vereiste handmatige interventie door een developer die de nuances van PHPStan Level 9 begrijpt.

---

*Gegenereerd door Omega Engine + Claude AI*
*AetherLink.AI Tech Engineering (C) 2024*
