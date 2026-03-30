<?php
session_start();
require_once 'includes/database.php'; // your existing DB file

if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'admin') {
    header('Location: login.php');
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
</style>
</head>
<body>
<div class="container-fluid py-4">
    <h2 class="text-center text-primary mb-4">🦠 Biosecurity Management Dashboard</h2>

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
        <h4 class="text-primary mb-3">➕ Add New Biosecurity Log</h4>
        <form method="POST" action="save_biosecurity.php">
            <div class="row g-3">
                <div class="col-md-3">
                    <label>Date</label>
                    <input type="date" name="log_date" class="form-control" required>
                </div>
                <div class="col-md-3">
                    <label>Doctor Visit</label>
                    <input type="text" name="doctor_name" class="form-control" placeholder="Dr. Name" required>
                </div>
                <div class="col-md-3">
                    <label>Disease Type</label>
                    <input type="text" name="disease_type" class="form-control" placeholder="Example: Bird Flu" required>
                </div>
                <div class="col-md-3">
                    <label>Medicine Provided</label>
                    <input type="text" name="medicine_given" class="form-control" placeholder="Medicine Name" required>
                </div>
                <div class="col-md-3 mt-2">
                    <label>Infected Count</label>
                    <input type="number" name="infected_count" class="form-control" min="0" required>
                </div>
                <div class="col-md-3 mt-2">
                    <label>Cured Count</label>
                    <input type="number" name="cured_count" class="form-control" min="0" required>
                </div>
                <div class="col-md-3 mt-2">
                    <label>Death Count</label>
                    <input type="number" name="death_count" class="form-control" min="0" required>
                </div>
                <div class="col-md-3 mt-2">
                    <label>Vaccinated Date</label>
                    <input type="date" name="date_vaccinated" class="form-control">
                </div>
                <div class="col-md-3 mt-2">
                    <label>Fertility Check (%)</label>
                    <input type="number" name="fertility_rate" class="form-control" min="0" max="100">
                </div>
                <div class="col-md-3 mt-2">
                    <label>Quarantine Check</label>
                    <select name="quarantine_check" class="form-select">
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                    </select>
                </div>
                <div class="col-md-3 mt-2">
                    <label>Cleaning Status</label>
                    <select name="cleaning_status" class="form-select">
                        <option value="Completed">Completed</option>
                        <option value="Pending">Pending</option>
                    </select>
                </div>
                <div class="col-md-6 mt-2">
                    <label>Remarks / Observations</label>
                    <textarea name="remarks" class="form-control" rows="2"></textarea>
                </div>
                <div class="col-md-12 mt-3 text-center">
                    <button class="btn btn-primary px-4" type="submit">Save Record</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Biosecurity Logs Table -->
    <div class="card p-3 mb-4">
        <h4 class="text-primary mb-3">📜 Biosecurity Reports History</h4>
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
                        <th>Remarks</th>
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
                        <td><?= htmlspecialchars($log['remarks']) ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="11" class="text-center text-muted">No records yet</td></tr>
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
</div>

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
                fill: false,
                tension: 0.3
            },
            {
                label: 'Cured',
                data: <?= json_encode(array_column($biosecurity, 'cured_count')) ?>,
                borderColor: '#10b981',
                fill: false,
                tension: 0.3
            },
            {
                label: 'Deaths',
                data: <?= json_encode(array_column($biosecurity, 'death_count')) ?>,
                borderColor: '#3b82f6',
                fill: false,
                tension: 0.3
            }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } },
        scales: {
            y: { beginAtZero: true }
        }
    }
});
</script>
</body>
</html>
