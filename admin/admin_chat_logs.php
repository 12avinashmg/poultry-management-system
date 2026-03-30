<?php
// admin_chat_logs.php
session_start();
require_once 'includes/database.php';
if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'admin') { header('Location: login.php'); exit; }

$q = $_GET['q'] ?? '';
$limit = intval($_GET['limit'] ?? 200);

$sql = "SELECT * FROM chatbot_logs WHERE 1=1 ";
$params = [];
if ($q) { $sql .= " AND (question LIKE :q OR reply LIKE :q OR intent LIKE :q) "; $params[':q'] = '%'.$q.'%'; }
$sql .= " ORDER BY created_at DESC LIMIT :lim";
$stmt = $pdo->prepare($sql);
foreach ($params as $k=>$v) $stmt->bindValue($k, $v);
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Chatbot Logs - Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>pre{white-space:pre-wrap}</style>
</head>
<body class="p-4">
<div class="container">
  <div class="d-flex gap-2 mb-3">
    <a class="btn btn-outline-secondary" href="admin_dashboard.php">← Dashboard</a>
    <form class="d-flex" style="flex:1" method="get">
      <input class="form-control me-2" name="q" placeholder="Search question / reply / intent" value="<?= htmlspecialchars($q) ?>">
      <select name="limit" class="form-select me-2" style="max-width:120px">
        <option <?= $limit==50?'selected':'' ?>>50</option>
        <option <?= $limit==100?'selected':'' ?>>100</option>
        <option <?= $limit==200?'selected':'' ?>>200</option>
        <option <?= $limit==500?'selected':'' ?>>500</option>
      </select>
      <button class="btn btn-primary">Search</button>
    </form>
    <a class="btn btn-success" href="admin_chat_logs.php?download=1<?= $q? '&q='.urlencode($q):'' ?>">Export CSV</a>
  </div>

<?php
if (isset($_GET['download'])) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=chatbot_logs.csv');
    $out = fopen('php://output','w');
    fputcsv($out, ['id','user_id','session_id','question','reply','intent','created_at']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['id'],$r['user_id'],$r['session_id'],$r['question'],$r['reply'],$r['intent'],$r['created_at']]);
    }
    fclose($out);
    exit;
}
?>

<table class="table table-striped table-bordered">
  <thead><tr><th>ID</th><th>Question</th><th>Reply (preview)</th><th>Intent</th><th>Date</th><th>Actions</th></tr></thead>
  <tbody>
  <?php foreach($rows as $r): ?>
    <tr>
      <td><?= $r['id'] ?></td>
      <td><pre><?= htmlspecialchars($r['question']) ?></pre></td>
      <td style="max-width:360px"><pre><?= htmlspecialchars(mb_strimwidth($r['reply']??'','0',240,'...')) ?></pre></td>
      <td><?= htmlspecialchars($r['intent']) ?></td>
      <td><?= $r['created_at'] ?></td>
      <td style="white-space:nowrap">
        <a class="btn btn-sm btn-outline-primary" href="admin_chat_logs.php?q=<?= urlencode($r['question']) ?>">Search Similar</a>
        <a class="btn btn-sm btn-outline-secondary" href="admin_chat_logs.php?download=1&q=<?= urlencode($r['question']) ?>">Export</a>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
</div>
</body>
</html>
