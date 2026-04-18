<?php
session_start();
require_once(__DIR__ . '/../includes/database.php');

if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Handle form submission
$success_msg = '';
$error_msg = '';
$edit_id = null;
$edit_data = null;

// Check if editing
if (isset($_GET['edit_id'])) {
    $edit_id = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM biosecurity_logs WHERE id = :id");
    $stmt->execute([':id' => $edit_id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle form submission - Save or Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_biosecurity'])) {
    try {
        $log_date = $_POST['log_date'] ?? date('Y-m-d');
        $disease_type = $_POST['disease_type'] ?? '';
        $doctor_name = $_POST['doctor_name'] ?? '';
        $medicine_given = $_POST['medicine_given'] ?? '';
        $infected_count = (int)($_POST['infected_count'] ?? 0);
        $cured_count = (int)($_POST['cured_count'] ?? 0);
        $death_count = (int)($_POST['death_count'] ?? 0);
        $date_vaccinated = $_POST['date_vaccinated'] ?? null;
        $fertility_rate = $_POST['fertility_rate'] ?? null;
        $quarantine_check = $_POST['quarantine_check'] ?? 'No';
        $cleaning_status = $_POST['cleaning_status'] ?? 'Pending';
        $remarks = $_POST['remarks'] ?? '';
        $record_id = $_POST['record_id'] ?? null;

        if ($record_id) {
            // Update existing record
            $stmt = $pdo->prepare("
                UPDATE biosecurity_logs SET
                log_date = :log_date,
                disease_type = :disease_type,
                doctor_name = :doctor_name,
                medicine_given = :medicine_given,
                infected_count = :infected_count,
                cured_count = :cured_count,
                death_count = :death_count,
                date_vaccinated = :date_vaccinated,
                fertility_rate = :fertility_rate,
                quarantine_check = :quarantine_check,
                cleaning_status = :cleaning_status,
                remarks = :remarks
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $record_id,
                ':log_date' => $log_date,
                ':disease_type' => $disease_type,
                ':doctor_name' => $doctor_name,
                ':medicine_given' => $medicine_given,
                ':infected_count' => $infected_count,
                ':cured_count' => $cured_count,
                ':death_count' => $death_count,
                ':date_vaccinated' => $date_vaccinated ?: null,
                ':fertility_rate' => $fertility_rate ?: null,
                ':quarantine_check' => $quarantine_check,
                ':cleaning_status' => $cleaning_status,
                ':remarks' => $remarks
            ]);
            $success_msg = "✅ Record updated successfully!";
            header('Refresh: 1; url=admin_biosecurity.php');
        } else {
            // Insert new record
            $stmt = $pdo->prepare("
                INSERT INTO biosecurity_logs 
                (log_date, disease_type, doctor_name, medicine_given, infected_count, 
                 cured_count, death_count, date_vaccinated, fertility_rate, 
                 quarantine_check, cleaning_status, remarks)
                VALUES 
                (:log_date, :disease_type, :doctor_name, :medicine_given, :infected_count,
                 :cured_count, :death_count, :date_vaccinated, :fertility_rate,
                 :quarantine_check, :cleaning_status, :remarks)
            ");
            $stmt->execute([
                ':log_date' => $log_date,
                ':disease_type' => $disease_type,
                ':doctor_name' => $doctor_name,
                ':medicine_given' => $medicine_given,
                ':infected_count' => $infected_count,
                ':cured_count' => $cured_count,
                ':death_count' => $death_count,
                ':date_vaccinated' => $date_vaccinated ?: null,
                ':fertility_rate' => $fertility_rate ?: null,
                ':quarantine_check' => $quarantine_check,
                ':cleaning_status' => $cleaning_status,
                ':remarks' => $remarks
            ]);
            $success_msg = "✅ Biosecurity log saved successfully!";
            header('Refresh: 1; url=admin_biosecurity.php');
        }
    } catch (PDOException $e) {
        $error_msg = "❌ Error: " . $e->getMessage();
    }
}

// Handle Delete
if (isset($_GET['delete_id'])) {
    try {
        $delete_id = (int)$_GET['delete_id'];
        $stmt = $pdo->prepare("DELETE FROM biosecurity_logs WHERE id = :id");
        $stmt->execute([':id' => $delete_id]);
        $success_msg = "✅ Record deleted successfully!";
        header('Refresh: 1; url=admin_biosecurity.php');
    } catch (PDOException $e) {
        $error_msg = "❌ Error deleting record: " . $e->getMessage();
    }
}

// Handle Excel Export
if (isset($_GET['export_excel'])) {
    $biosecurity = $pdo->query("
        SELECT * FROM biosecurity_logs ORDER BY log_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="biosecurity_logs_' . date('Y-m-d') . '.xls"');
    
    echo '<table border="1">';
    echo '<tr style="background-color:#3b82f6; color:white; font-weight:bold;">';
    echo '<th>Date</th><th>Disease</th><th>Doctor</th><th>Infected</th><th>Cured</th><th>Deaths</th>';
    echo '<th>Medicine</th><th>Vaccinated</th><th>Cleaning</th><th>Quarantine</th><th>Fertility %</th><th>Remarks</th>';
    echo '</tr>';
    
    foreach ($biosecurity as $log) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($log['log_date']) . '</td>';
        echo '<td>' . htmlspecialchars($log['disease_type']) . '</td>';
        echo '<td>' . htmlspecialchars($log['doctor_name']) . '</td>';
        echo '<td>' . $log['infected_count'] . '</td>';
        echo '<td>' . $log['cured_count'] . '</td>';
        echo '<td>' . $log['death_count'] . '</td>';
        echo '<td>' . htmlspecialchars($log['medicine_given']) . '</td>';
        echo '<td>' . htmlspecialchars($log['date_vaccinated']) . '</td>';
        echo '<td>' . htmlspecialchars($log['cleaning_status']) . '</td>';
        echo '<td>' . htmlspecialchars($log['quarantine_check']) . '</td>';
        echo '<td>' . $log['fertility_rate'] . '</td>';
        echo '<td>' . htmlspecialchars($log['remarks']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    exit();
}

// Fetch all biosecurity-related data
$biosecurity = $pdo->query("
    SELECT * FROM biosecurity_logs ORDER BY log_date DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Dashboard Summary
$total_logs = count($biosecurity);
$total_deaths = array_sum(array_column($biosecurity, 'death_count'));
$total_cured = array_sum(array_column($biosecurity, 'cured_count'));
$total_infected = array_sum(array_column($biosecurity, 'infected_count'));
$total_alerts = $pdo->query("SELECT COUNT(*) FROM alerts WHERE status='Active'")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>🦠 Biosecurity Management | Poultry Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body {
    background: linear-gradient(135deg,#f0f7ff,#dbeafe);
    min-height: 100vh;
}
.card {
    border: none;
    border-radius: 15px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.08);
}
.table th {
    background: #3b82f6;
    color: white;
}
.badge-high { background:#ef4444; }
.badge-medium { background:#f59e0b; }
.badge-low { background:#10b981; }
.chart-container { width:100%; max-width:720px; margin:auto; }
.alert { border-radius: 10px; }
.action-buttons { white-space: nowrap; }
</style>
</head>
<body>
<div class="container-fluid py-4">
    <h2 class="text-center text-primary mb-4">🦠 Biosecurity Management Dashboard</h2>

    <!-- Success/Error Messages -->
    <?php if ($success_msg): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= $success_msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= $error_msg ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Dashboard Cards -->
    <div class="row g-4 mb-4 text-center">
        <div class="col-md-3">
            <div class="card p-3 bg-light">
                <h5>Total Reports</h5>
                <h3><?= $total_logs ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 bg-light">
                <h5>Infected Birds</h5>
                <h3 class="text-danger"><?= $total_infected ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 bg-light">
                <h5>Cured Birds</h5>
                <h3 class="text-success"><?= $total_cured ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card p-3 bg-light">
                <h5>Active Alerts</h5>
                <h3 class="text-warning"><?= $total_alerts ?></h3>
            </div>
        </div>
    </div>

    <!-- Add New Log Form -->
    <div class="card p-4 mb-4">
        <h4 class="text-primary mb-3">
            <?= $edit_data ? '✏️ Edit Biosecurity Log' : '➕ Add New Biosecurity Log' ?>
        </h4>
        <form method="POST">
            <?php if ($edit_data): ?>
                <input type="hidden" name="record_id" value="<?= $edit_data['id'] ?>">
            <?php endif; ?>
            
            <div class="row g-3">
                <div class="col-md-3">
                    <label>Date</label>
                    <input type="date" name="log_date" class="form-control" 
                           value="<?= $edit_data['log_date'] ?? date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label>Doctor Visit</label>
                    <input type="text" name="doctor_name" class="form-control" placeholder="Dr. Name" 
                           value="<?= htmlspecialchars($edit_data['doctor_name'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label>Disease Type</label>
                    <input type="text" name="disease_type" class="form-control" placeholder="Example: Bird Flu" 
                           value="<?= htmlspecialchars($edit_data['disease_type'] ?? '') ?>" required>
                </div>
                <div class="col-md-3">
                    <label>Medicine Provided</label>
                    <input type="text" name="medicine_given" class="form-control" placeholder="Medicine Name" 
                           value="<?= htmlspecialchars($edit_data['medicine_given'] ?? '') ?>" required>
                </div>
                <div class="col-md-3 mt-2">
                    <label>Infected Count</label>
                    <input type="number" name="infected_count" class="form-control" min="0" 
                           value="<?= $edit_data['infected_count'] ?? '0' ?>" required>
                </div>
                <div class="col-md-3 mt-2">
                    <label>Cured Count</label>
                    <input type="number" name="cured_count" class="form-control" min="0" 
                           value="<?= $edit_data['cured_count'] ?? '0' ?>" required>
                </div>
                <div class="col-md-3 mt-2">
                    <label>Death Count</label>
                    <input type="number" name="death_count" class="form-control" min="0" 
                           value="<?= $edit_data['death_count'] ?? '0' ?>" required>
                </div>
                <div class="col-md-3 mt-2">
                    <label>Vaccinated Date</label>
                    <input type="date" name="date_vaccinated" class="form-control" 
                           value="<?= $edit_data['date_vaccinated'] ?? '' ?>">
                </div>
                <div class="col-md-3 mt-2">
                    <label>Fertility Check (%)</label>
                    <input type="number" name="fertility_rate" class="form-control" min="0" max="100" step="0.01" 
                           value="<?= $edit_data['fertility_rate'] ?? '' ?>">
                </div>
                <div class="col-md-3 mt-2">
                    <label>Quarantine Check</label>
                    <select name="quarantine_check" class="form-select">
                        <option value="No" <?= ($edit_data['quarantine_check'] ?? '') === 'No' ? 'selected' : '' ?>>No</option>
                        <option value="Yes" <?= ($edit_data['quarantine_check'] ?? '') === 'Yes' ? 'selected' : '' ?>>Yes</option>
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <label>Cleaning Status</label>
                    <select name="cleaning_status" class="form-select">
                        <option value="Pending" <?= ($edit_data['cleaning_status'] ?? '') === 'Pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="Completed" <?= ($edit_data['cleaning_status'] ?? '') === 'Completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
                <div class="col-md-6 mt-2">
                    <label>Remarks / Observations</label>
                    <textarea name="remarks" class="form-control" rows="2" 
                              placeholder="Enter any additional remarks..."><?= htmlspecialchars($edit_data['remarks'] ?? '') ?></textarea>
                </div>
                <div class="col-md-12 mt-3 text-center">
                    <button class="btn btn-primary px-4" type="submit" name="save_biosecurity" value="1">
                        <?= $edit_data ? '✅ Update Record' : '💾 Save Record' ?>
                    </button>
                    <?php if ($edit_data): ?>
                        <a href="admin_biosecurity.php" class="btn btn-secondary px-4 ms-2">❌ Cancel</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

    <!-- Biosecurity Logs Table -->
    <div class="card p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="text-primary mb-0">📜 Biosecurity Reports History</h4>
            <a href="?export_excel=1" class="btn btn-success btn-sm">📥 Download Excel</a>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Disease</th>
                        <th>Doctor</th>
                        <th>Infected</th>
                        <th>Cured</th>
                        <th>Deaths</th>
                        <th>Medicine</th>
                        <th>Vaccinated</th>
                        <th>Cleaning</th>
                        <th>Quarantine</th>
                        <th>Fertility %</th>
                        <th>Remarks</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($biosecurity): foreach ($biosecurity as $log): ?>
                    <tr>
                        <td><?= htmlspecialchars($log['log_date']) ?></td>
                        <td><?= htmlspecialchars($log['disease_type']) ?></td>
                        <td><?= htmlspecialchars($log['doctor_name']) ?></td>
                        <td class="text-danger fw-bold"><?= $log['infected_count'] ?></td>
                        <td class="text-success fw-bold"><?= $log['cured_count'] ?></td>
                        <td class="text-danger fw-bold"><?= $log['death_count'] ?></td>
                        <td><?= htmlspecialchars($log['medicine_given']) ?></td>
                        <td><?= htmlspecialchars($log['date_vaccinated']) ?></td>
                        <td><?= htmlspecialchars($log['cleaning_status']) ?></td>
                        <td><?= htmlspecialchars($log['quarantine_check']) ?></td>
                        <td><?= $log['fertility_rate'] ?: '-' ?></td>
                        <td><?= htmlspecialchars($log['remarks']) ?></td>
                        <td class="action-buttons">
                            <a href="?edit_id=<?= $log['id'] ?>" class="btn btn-warning btn-sm" title="Edit">✏️</a>
                            <a href="?delete_id=<?= $log['id'] ?>" class="btn btn-danger btn-sm" 
                               onclick="return confirm('Are you sure you want to delete this record?');" title="Delete">🗑️</a>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="13" class="text-center text-muted">No records yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Analytics -->
    <div class="card p-4">
        <h4 class="text-primary text-center mb-3">📈 Biosecurity Analytics (Last 30 Days)</h4>
        <div class="chart-container">
            <canvas id="bioChart"></canvas>
        </div>
    </div>
    <div class="text-center mt-4">
        <a href="admin_dashboard.php" class="btn btn-link fs-5">← Back to Dashboard</a>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
const ctx = document.getElementById('bioChart').getContext('2d');
const chart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: <?= json_encode(array_column($biosecurity, 'log_date')) ?>,
        datasets: [
            {
                label: 'Infected',
                data: <?= json_encode(array_column($biosecurity, 'infected_count')) ?>,
                borderColor: '#ef4444',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: true,
                tension: 0.3,
                borderWidth: 2
            },
            {
                label: 'Cured',
                data: <?= json_encode(array_column($biosecurity, 'cured_count')) ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.3,
                borderWidth: 2
            },
            {
                label: 'Deaths',
                data: <?= json_encode(array_column($biosecurity, 'death_count')) ?>,
                borderColor: '#3b82f6',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: true,
                tension: 0.3,
                borderWidth: 2
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        plugins: { 
            legend: { position: 'bottom' }
        },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
</body>
</html>