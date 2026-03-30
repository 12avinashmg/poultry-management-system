<?php
session_start();
require_once 'includes/database.php';

if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// Fetch payroll data via PDO (safe)
$payroll_data = $pdo->query("SELECT Employee_ID, CONCAT(FirstName, ' ', LastName) AS name, Job AS job, Salary FROM employee")->fetchAll(PDO::FETCH_ASSOC);

// Build label/value arrays safely
$payroll_labels = [];
$payroll_values = [];
foreach ($payroll_data as $row) {
    $name = isset($row['name']) ? $row['name'] : 'Unknown';
    $job  = isset($row['job']) ? $row['job'] : '';
    $salary = 0;
    if (isset($row['Salary'])) $salary = (float)$row['Salary'];
    elseif (isset($row['salary'])) $salary = (float)$row['salary'];

    $payroll_labels[] = $name . ($job !== '' ? " ({$job})" : '');
    $payroll_values[] = $salary;
}
$total_payroll = array_sum($payroll_values);

// Handle Delete
if (isset($_GET['delete'])) {
    $emp_id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM employee WHERE Employee_ID = ?");
    $stmt->execute([$emp_id]);
    header("Location: payroll.php?msg=deleted");
    exit();
}

// Handle Add
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_employee'])) {
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $phone = trim($_POST['phone']);
    $job = trim($_POST['job']);
    $salary = floatval($_POST['salary']);
    $stmt = $pdo->prepare("INSERT INTO employee (FirstName, LastName, Phone, Job, Salary) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$fname, $lname, $phone, $job, $salary]);
    header("Location: payroll.php");
    exit();
}

// Handle Edit (Show Edit Form)
$edit_emp = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM employee WHERE Employee_ID = ?");
    $stmt->execute([$edit_id]);
    $edit_emp = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle Update
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['update_employee'])) {
    $emp_id = intval($_POST['emp_id']);
    $fname = trim($_POST['fname']);
    $lname = trim($_POST['lname']);
    $phone = trim($_POST['phone']);
    $job = trim($_POST['job']);
    $salary = floatval($_POST['salary']);
    $stmt = $pdo->prepare("UPDATE employee SET FirstName=?, LastName=?, Phone=?, Job=?, Salary=? WHERE Employee_ID=?");
    $stmt->execute([$fname, $lname, $phone, $job, $salary, $emp_id]);
    header("Location: payroll.php?msg=updated");
    exit();
}

$stmt = $pdo->query("SELECT * FROM employee ORDER BY Employee_ID DESC");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8" />
    <title>Payroll | Admin | Poultry Management</title>
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Chart container sizing - adjust as needed */
        .payroll-chart-container {
          width: 100%;
          max-width: 420px;    /* change to 280/360 for smaller charts */
          height: 360px;       /* canvas area height */
          margin: 0 auto 1.25rem;
          display: block;
        }
        #payrollPie {
          width: 100% !important;
          height: 100% !important;
          display: block;
        }
        .payroll-title {
          font-weight: 700;
          font-size: 1.15rem;
          margin-bottom: 0.5rem;
          display: flex;
          align-items: center;
          gap: 0.5rem;
        }
        .payroll-title img { vertical-align: middle; }
        .payroll-section { padding: 1rem; background:#fff; border-radius:8px; }
    </style>
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
    <div class="container">
        <a class="navbar-brand" href="admin_dashboard.php">Poultry Management</a>
        <div class="d-flex">
            <span class="navbar-text me-3">Hello, Admin <?= htmlspecialchars($_SESSION['Username'] ?? '') ?></span>
            <a href="logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
        </div>
    </div>
</nav>

<div class="container mt-5">
    <h2 class="mb-4 text-success">Employee Payroll</h2>

    <div class="card mb-4">
        <div class="card-body">
            <?php if ($edit_emp): ?>
                <h5>Edit Employee</h5>
                <form method="post" class="row g-3">
                    <input type="hidden" name="emp_id" value="<?= (int)$edit_emp['Employee_ID'] ?>">
                    <div class="col-md-2">
                        <input type="text" name="fname" class="form-control" value="<?= htmlspecialchars($edit_emp['FirstName']) ?>" placeholder="First Name" required>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="lname" class="form-control" value="<?= htmlspecialchars($edit_emp['LastName']) ?>" placeholder="Last Name" required>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($edit_emp['Phone']) ?>" placeholder="Phone" maxlength="10" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="job" class="form-control" value="<?= htmlspecialchars($edit_emp['Job']) ?>" placeholder="Job Title" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="salary" class="form-control" value="<?= htmlspecialchars($edit_emp['Salary']) ?>" placeholder="Salary" required>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" name="update_employee" class="btn btn-primary w-100">Update</button>
                    </div>
                </form>
                <div class="mt-2">
                    <a href="payroll.php" class="btn btn-link">Cancel Edit</a>
                </div>
            <?php else: ?>
                <h5>Add Employee</h5>
                <form method="post" class="row g-3">
                    <div class="col-md-2">
                        <input type="text" name="fname" class="form-control" placeholder="First Name" required>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="lname" class="form-control" placeholder="Last Name" required>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="phone" class="form-control" placeholder="Phone" maxlength="10" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="job" class="form-control" placeholder="Job Title" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="salary" class="form-control" placeholder="Salary" required>
                    </div>
                    <div class="col-md-1">
                        <button type="submit" name="add_employee" class="btn btn-success w-100">Add</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <h5>All Employees</h5>
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>Emp ID</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Job</th>
                        <th>Salary (₹)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($employees)): ?>
                        <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td><?= (int)$emp['Employee_ID'] ?></td>
                            <td><?= htmlspecialchars($emp['FirstName'] . " " . $emp['LastName']) ?></td>
                            <td><?= htmlspecialchars($emp['Phone']) ?></td>
                            <td><?= htmlspecialchars($emp['Job']) ?></td>
                            <td><?= number_format((float)$emp['Salary'], 2) ?></td>
                            <td>
                                <a href="payroll.php?edit=<?= (int)$emp['Employee_ID'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                <a href="payroll.php?delete=<?= (int)$emp['Employee_ID'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this employee?');">Delete</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No employees found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <!-- Payroll Chart Section -->
    <div class="row payroll-section mt-4 shadow-sm">
        <div class="col-12">
            <div class="payroll-title">
                <img src="https://img.icons8.com/fluency/48/000000/payroll.png" width="38" alt="payroll">
                Employee Payroll Breakdown
            </div>

            <div class="payroll-chart-container">
                <canvas id="payrollPie" aria-label="Payroll chart" role="img"></canvas>
            </div>

            <div class="text-center mt-3 mb-2 fw-semibold">
                <?php if (!empty($payroll_values)): ?>
                    <?php foreach ($payroll_labels as $idx => $lab): 
                        $salary = isset($payroll_values[$idx]) ? (float)$payroll_values[$idx] : 0;
                        $perc = $total_payroll > 0 ? round(($salary / $total_payroll) * 100, 1) : 0;
                    ?>
                        <div>
                            <span style="font-weight:bold;"><?= htmlspecialchars($lab) ?></span>
                            : ₹<?= number_format($salary) ?>
                            <span class="text-secondary">(<?= $perc ?>%)</span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div>No payroll data available.</div>
                <?php endif; ?>
            </div>

            <div class="text-center mt-1 mb-3 fw-bold fs-5 text-primary">
                Total Payroll: ₹<?= number_format($total_payroll, 2) ?>
            </div>
<a href="admin_dashboard.php" class="btn btn-link mt-2">← Back to Admin Dashboard</a>


<!-- Chart.js (loaded once) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function(){
    const ctx = document.getElementById('payrollPie').getContext('2d');

    const payrollLabels = <?= json_encode($payroll_labels ?? [], JSON_UNESCAPED_UNICODE) ?> || [];
    const payrollData   = <?= json_encode($payroll_values ?? []) ?> || [];

    const doughnutColors = [
        '#4f8cff','#fbbf24','#10b981','#ef4444','#a78bfa','#f472b6',
        '#22d3ee','#fde68a','#a3e635','#f87171','#c4b5fd','#fca5a5'
    ];
    const backgroundColors = payrollData.map((_, i) => doughnutColors[i % doughnutColors.length]);

    // destroy existing instance if present (safe re-init)
    if (window._payrollChart instanceof Chart) {
        try { window._payrollChart.destroy(); } catch(e) {}
    }

    window._payrollChart = new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: payrollLabels,
            datasets: [{
                data: payrollData,
                backgroundColor: backgroundColors,
                borderColor: '#fff',
                borderWidth: 2,
                hoverOffset: 12
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false, // lets CSS control size
            cutout: '40%',
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: { font: { size: 13, weight: 'bold' } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw || 0;
                            return `${label}: ₹${Number(value).toLocaleString()}`;
                        }
                    }
                }
            }
        }
    });
})();
</script>
</body>
</html>
