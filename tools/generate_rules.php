<?php
// tools/generate_rules.php
// Usage: from project root run: php tools/generate_rules.php
require_once __DIR__ . '/../includes/database.php';

$limit = intval($argv[1] ?? 200); // top N queries to analyze (default 200)

// fetch frequent raw queries
$sql = "SELECT question, COUNT(*) AS cnt
        FROM chatbot_logs
        WHERE question IS NOT NULL AND TRIM(question) <> ''
        GROUP BY question
        ORDER BY cnt DESC
        LIMIT :lim";
$stmt = $pdo->prepare($sql);
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$rules = [];
foreach ($rows as $r) {
    $q = trim($r['question']);
    if ($q === '') continue;

    // Normalize to create a candidate pattern:
    // - lowercase
    // - remove punctuation except percent, dash, slash (dates)
    // - collapse multiple spaces
    $norm = mb_strtolower($q);
    $norm = preg_replace('/[^\p{L}\p{N}\s%\-\/:]/u', ' ', $norm);
    $norm = preg_replace('/\s+/', ' ', $norm);
    $norm = trim($norm);

    // Create some lightweight meta (word tokens)
    $tokens = array_filter(explode(' ', $norm));
    $token_preview = array_slice($tokens, 0, 8);

    // Rule candidate
    $rules[] = [
        'example' => $q,
        'pattern' => $norm,
        'tokens' => $token_preview,
        'count' => intval($r['cnt'])
    ];
}


// Write JSON (pretty)
$outPath = __DIR__ . '/rules.json';
file_put_contents($outPath, json_encode($rules, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "Wrote {$outPath} with " . count($rules) . " candidates (top {$limit}).\n";
