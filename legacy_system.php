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