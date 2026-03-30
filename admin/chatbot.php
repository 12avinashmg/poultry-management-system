<?php
// chatbot.php - Menu-driven admin chatbot for Poultry Management
// Place in project root. Requires: includes/database.php (defines $pdo) and session-based admin auth.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/includes/database.php';   // $pdo PDO
require_once __DIR__ . '/includes/config.php'; 
require_once(__DIR__ . '/secret.php');    // optional GEMINI_* if you add AI later
session_start();

header('Content-Type: application/json; charset=utf-8');

// --- simple admin auth check ---
if (!isset($_SESSION['User_ID']) || ($_SESSION['Role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo json_encode(['ok'=>false,'reply'=>'Unauthorized']);
    exit;
}

// ---------- Logging helpers ----------
function insert_log($user_id, $session_id, $question, $reply = null, $intent = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO chatbot_logs (user_id, session_id, question, reply, intent) VALUES (:uid, :sid, :q, :r, :i)");
        $stmt->execute([':uid'=>$user_id, ':sid'=>$session_id, ':q'=>$question, ':r'=>$reply, ':i'=>$intent]);
        return $pdo->lastInsertId();
    } catch (Exception $e) {
        return null;
    }
}
function update_log_reply($log_id, $reply, $intent = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("UPDATE chatbot_logs SET reply = :r, intent = :i WHERE id = :id");
        $stmt->execute([':r'=>$reply, ':i'=>$intent, ':id'=>$log_id]);
    } catch (Exception $e) { }
}

// ---------- Utility DB helpers ----------
function safe_select($sql, $params = []) {
    global $pdo;
    try {
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            // numeric keys -> 1-based positional
            if (is_int($k)) $stmt->bindValue($k+1, $v);
            else $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return ['rows'=>$rows];
    } catch (Exception $e) {
        return ['error'=>$e->getMessage()];
    }
}

function get_available_dates($table, $date_column) {
    global $pdo;
    try {
        $sql = "SELECT DISTINCT {$date_column} AS dt FROM {$table} WHERE {$date_column} IS NOT NULL ORDER BY {$date_column} DESC LIMIT 50";
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);
        return $rows;
    } catch (Exception $e) {
        return [];
    }
}

// ---------- Read input ----------
$body = json_decode(file_get_contents('php://input'), true);
$message = trim($body['message'] ?? '');
$session_id = session_id();
$user_id = $_SESSION['User_ID'] ?? null;

if ($message === '') {
    echo json_encode(['ok'=>false,'reply'=>'Please type a question.']);
    exit;
}

// log question
$log_id = insert_log($user_id, $session_id, $message, null, null);

// ---------- Define static menu (IDs are used by INTENT: commands) ----------
$menu = [
    ['id'=>'mortality','title'=>'Birds Mortality'],
    ['id'=>'purchase_birds','title'=>'Birds Purchase'],
    ['id'=>'production','title'=>'Egg Production'],
    ['id'=>'sales','title'=>'Egg Sales'],
    ['id'=>'feed_purchase','title'=>'Feed Purchase'],
    ['id'=>'feed_consumption','title'=>'Feed Consumption'],
    ['id'=>'employees','title'=>'Employees & Salaries'],
    ['id'=>'alerts','title'=>'Alerts'],
    ['id'=>'orders','title'=>'Orders'],
    ['id'=>'prices','title'=>'Prices'],
    ['id'=>'biosecurity','title'=>'Biosecurity Logs'],
];

// ---------- Handle MENU request ----------
if ($message === '__MENU__') {
    update_log_reply($log_id, json_encode(['menu'=>$menu]), 'menu');
    echo json_encode(['ok'=>true,'reply'=>'Choose one of the options:','menu'=>$menu]);
    exit;
}

// ---------- Parse INTENT commands: INTENT:<id> or INTENT:<id>:<arg> ----------
if (stripos($message,'INTENT:') === 0) {
    $parts = explode(':', $message);
    // INTENT:ID  -> $parts[1]
    // INTENT:ID:YYYY-MM-DD -> $parts[2] contains the date
    $intent = $parts[1] ?? '';
    $arg = $parts[2] ?? null;

    // handle each intent
    if ($intent === 'mortality') {
        // return available dates
        $dates = get_available_dates('birdsmortality','Date');
        $reply = 'Select a date to view mortality records.';
        update_log_reply($log_id, $reply, 'mortality');
        echo json_encode(['ok'=>true,'reply'=>$reply,'suggestions'=> [ ['table'=>'birdsmortality','column'=>'Date','dates'=>$dates] ]]);
        exit;
    }
    if ($intent === 'mortality_by_date' && $arg) {
        $sql = "SELECT Date, Deaths FROM birdsmortality WHERE Date = :d";
        $res = safe_select($sql, [':d'=>$arg]);
        if (!empty($res['error'])) { update_log_reply($log_id, $res['error'], 'mortality_by_date'); echo json_encode(['ok'=>false,'reply'=>'SQL error: '.$res['error']]); exit; }
        update_log_reply($log_id, json_encode($res['rows']), 'mortality_by_date');
        echo json_encode(['ok'=>true,'reply'=>'Mortality records for ' . $arg,'rows'=>$res['rows']]);
        exit;
    }

    if ($intent === 'purchase_birds') {
        $dates = get_available_dates('birdspurchase','Date');
        $reply = 'Select a date to view bird purchase records.';
        update_log_reply($log_id, $reply, 'purchase_birds');
        echo json_encode(['ok'=>true,'reply'=>$reply,'suggestions'=>[ ['table'=>'birdspurchase','column'=>'Date','dates'=>$dates] ]]);
        exit;
    }
    if ($intent === 'purchase_birds_by_date' && $arg) {
        $res = safe_select("SELECT Date, NumberOfBirds, Price FROM birdspurchase WHERE Date = :d", [':d'=>$arg]);
        if (!empty($res['error'])) { update_log_reply($log_id,$res['error'],'purchase_birds_by_date'); echo json_encode(['ok'=>false,'reply'=>'SQL error']); exit; }
        update_log_reply($log_id, json_encode($res['rows']), 'purchase_birds_by_date');
        echo json_encode(['ok'=>true,'reply'=>"Bird purchases on {$arg}", 'rows'=>$res['rows']]);
        exit;
    }

    if ($intent === 'production') {
        $dates = get_available_dates('production','Date');
        $reply = 'Select a date to view production (eggs).';
        update_log_reply($log_id, $reply, 'production');
        echo json_encode(['ok'=>true,'reply'=>$reply,'suggestions'=>[ ['table'=>'production','column'=>'Date','dates'=>$dates] ]]);
        exit;
    }
    if ($intent === 'production_by_date' && $arg) {
        $res = safe_select("SELECT Date, NumberOfEggs FROM production WHERE Date = :d", [':d'=>$arg]);
        if (!empty($res['error'])) { update_log_reply($log_id,$res['error'],'production_by_date'); echo json_encode(['ok'=>false,'reply'=>'SQL error']); exit; }
        update_log_reply($log_id, json_encode($res['rows']), 'production_by_date');
        echo json_encode(['ok'=>true,'reply'=>"Production on {$arg}", 'rows'=>$res['rows']]);
        exit;
    }

    if ($intent === 'sales') {
        $dates = get_available_dates('sales','Date');
        $reply = 'Select a date to view sales records.';
        update_log_reply($log_id, $reply, 'sales');
        echo json_encode(['ok'=>true,'reply'=>$reply,'suggestions'=>[ ['table'=>'sales','column'=>'Date','dates'=>$dates] ]]);
        exit;
    }
    if ($intent === 'sales_by_date' && $arg) {
        $res = safe_select("SELECT Date, NumberOfEggs, Revenue FROM sales WHERE Date = :d", [':d'=>$arg]);
        if (!empty($res['error'])) { update_log_reply($log_id,$res['error'],'sales_by_date'); echo json_encode(['ok'=>false,'reply'=>'SQL error']); exit; }
        update_log_reply($log_id, json_encode($res['rows']), 'sales_by_date');
        echo json_encode(['ok'=>true,'reply'=>"Sales on {$arg}", 'rows'=>$res['rows']]);
        exit;
    }

    if ($intent === 'feed_purchase') {
        $dates = get_available_dates('feedpurchase','Date');
        $reply = 'Select a date to view feed purchases.';
        update_log_reply($log_id, $reply, 'feed_purchase');
        echo json_encode(['ok'=>true,'reply'=>$reply,'suggestions'=>[ ['table'=>'feedpurchase','column'=>'Date','dates'=>$dates] ]]);
        exit;
    }
    if ($intent === 'feed_purchase_by_date' && $arg) {
        $res = safe_select("SELECT Date, Quantity, Price FROM feedpurchase WHERE Date = :d", [':d'=>$arg]);
        update_log_reply($log_id, json_encode($res['rows']), 'feed_purchase_by_date');
        echo json_encode(['ok'=>true,'reply'=>"Feed purchases on {$arg}", 'rows'=>$res['rows']]);
        exit;
    }

    if ($intent === 'feed_consumption') {
        $dates = get_available_dates('feedconsumption','ConsDate');
        $reply = 'Select a date to view feed consumption.';
        update_log_reply($log_id, $reply, 'feed_consumption');
        echo json_encode(['ok'=>true,'reply'=>$reply,'suggestions'=>[ ['table'=>'feedconsumption','column'=>'ConsDate','dates'=>$dates] ]]);
        exit;
    }
    if ($intent === 'feed_consumption_by_date' && $arg) {
        $res = safe_select("SELECT ConsDate, Quantity, Price, Employee FROM feedconsumption WHERE ConsDate = :d", [':d'=>$arg]);
        update_log_reply($log_id, json_encode($res['rows']), 'feed_consumption_by_date');
        echo json_encode(['ok'=>true,'reply'=>"Feed consumption on {$arg}", 'rows'=>$res['rows']]);
        exit;
    }

    if ($intent === 'employees') {
        // return all employees with salary and total payroll
        $res = safe_select("SELECT Employee_ID, FirstName, LastName, Job, Salary FROM employee ORDER BY FirstName");
        if (!empty($res['error'])) { update_log_reply($log_id,$res['error'],'employees'); echo json_encode(['ok'=>false,'reply'=>'SQL error']); exit; }
        $total = 0;
        foreach ($res['rows'] as $r) { $total += floatval($r['Salary'] ?? 0); }
        update_log_reply($log_id,json_encode($res['rows']),'employees');
        echo json_encode(['ok'=>true,'reply'=>"Employees & total payroll: {$total}", 'rows'=>$res['rows'], 'meta'=>['total_payroll'=>$total]]);
        exit;
    }

    if ($intent === 'alerts') {
        $res = safe_select("SELECT id, alert_title, alert_message, severity, status, created_at FROM alerts ORDER BY created_at DESC LIMIT 50");
        update_log_reply($log_id,json_encode($res['rows']),'alerts');
        echo json_encode(['ok'=>true,'reply'=>'Recent alerts','rows'=>$res['rows']]);
        exit;
    }

    if ($intent === 'orders') {
        $res = safe_select("SELECT Order_ID, User_ID, Type, Item_ID, Quantity, TotalPrice, OrderDate FROM orders ORDER BY OrderDate DESC LIMIT 50");
        update_log_reply($log_id,json_encode($res['rows']),'orders');
        echo json_encode(['ok'=>true,'reply'=>'Recent orders','rows'=>$res['rows']]);
        exit;
    }

    if ($intent === 'prices') {
        $res = safe_select("SELECT item_type, price_per_unit, unit, updated_at FROM prices ORDER BY item_type");
        update_log_reply($log_id,json_encode($res['rows']),'prices');
        echo json_encode(['ok'=>true,'reply'=>'Current prices','rows'=>$res['rows']]);
        exit;
    }

    if ($intent === 'biosecurity') {
        $dates = get_available_dates('biosecurity_logs','log_date');
        update_log_reply($log_id,'biosecurity list','biosecurity');
        echo json_encode(['ok'=>true,'reply'=>'Select a date to view biosecurity logs','suggestions'=>[ ['table'=>'biosecurity_logs','column'=>'log_date','dates'=>$dates] ]]);
        exit;
    }
    if ($intent === 'biosecurity_by_date' && $arg) {
        $res = safe_select("SELECT * FROM biosecurity_logs WHERE log_date = :d", [':d'=>$arg]);
        update_log_reply($log_id,json_encode($res['rows']),'biosecurity_by_date');
        echo json_encode(['ok'=>true,'reply'=>"Biosecurity logs for {$arg}", 'rows'=>$res['rows']]);
        exit;
    }

    // unknown intent
    update_log_reply($log_id,'Unknown INTENT:'.$intent,'intent_error');
    echo json_encode(['ok'=>false,'reply'=>'Unknown intent: '.$intent]);
    exit;
}

// ---------- Free text simple rule-based handling ----------
$lc = mb_strtolower($message);

// mortality-like questions: try parse date in message
function parse_date_in_text($text) {
    if (preg_match('/\b(\d{4}-\d{1,2}-\d{1,2})\b/',$text,$m)) return $m[1];
    if (preg_match('/\b(\d{1,2}\/\d{1,2}\/\d{2,4})\b/',$text,$m)) {
        $p = str_replace('/','-',$m[1]); $parts = explode('-',$p);
        if (strlen($parts[2])==2) $parts[2] = '20'.$parts[2];
        return sprintf('%04d-%02d-%02d', intval($parts[2]), intval($parts[1]), intval($parts[0]));
    }
    if (strpos($text,'today')!==false) return (new DateTimeImmutable('today'))->format('Y-m-d');
    if (strpos($text,'yesterday')!==false) return (new DateTimeImmutable('today'))->sub(new DateInterval('P1D'))->format('Y-m-d');
    return null;
}

// If user asks about deaths
if (preg_match('/\bbird(s)?\b.*\b(die|died|death|dead|mortality)\b/',$lc) || strpos($lc,'how many died')!==false) {
    $dr = parse_date_in_text($lc);
    if ($dr) {
        $res = safe_select("SELECT Date, Deaths FROM birdsmortality WHERE Date = :d", [':d'=>$dr]);
        if (!empty($res['error'])) { update_log_reply($log_id,$res['error'],'mortality_free'); echo json_encode(['ok'=>false,'reply'=>'SQL error']); exit; }
        if (empty($res['rows'])) {
            $dates = get_available_dates('birdsmortality','Date');
            $reply = "No mortality records found for {$dr}.";
            if ($dates) $reply .= " Available dates: " . implode(', ',$dates);
            update_log_reply($log_id,$reply,'mortality_free');
            echo json_encode(['ok'=>true,'reply'=>$reply,'suggestions'=>[ ['table'=>'birdsmortality','dates'=>$dates] ]]);
            exit;
        } else {
            update_log_reply($log_id,json_encode($res['rows']),'mortality_free');
            echo json_encode(['ok'=>true,'reply'=>"Mortality on {$dr}", 'rows'=>$res['rows']]);
            exit;
        }
    } else {
        // no date found -> return suggestions (menu style)
        $dates = get_available_dates('birdsmortality','Date');
        $reply = 'When do you want to check mortality? Pick a date.';
        update_log_reply($log_id,$reply,'mortality_free_nodate');
        echo json_encode(['ok'=>true,'reply'=>$reply,'suggestions'=>[['table'=>'birdsmortality','dates'=>$dates]]]);
        exit;
    }
}

// fallback: return menu (encourage to pick one)
update_log_reply($log_id, 'Returned menu (fallback)', 'menu');
echo json_encode(['ok'=>true,'reply'=>'I can help with these areas:','menu'=>$menu]);
exit;
