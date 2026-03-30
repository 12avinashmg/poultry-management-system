<?php
session_start();
require_once(__DIR__ . '/../includes/database.php');

if (!isset($_SESSION['User_ID']) || $_SESSION['Role'] !== 'user') { header('Location: login.php'); exit(); }

$user_id = $_SESSION['User_ID'];

// Handle address deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    // Only allow deleting own address
    $pdo->prepare("DELETE FROM addresses WHERE Address_ID = ? AND User_ID = ?")->execute([$del_id, $user_id]);
    header("Location: user_address.php");
    exit();
}

// Handle address edit fetch
$edit_address = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);
    $stmt = $pdo->prepare("SELECT * FROM addresses WHERE Address_ID = ? AND User_ID = ?");
    $stmt->execute([$edit_id, $user_id]);
    $edit_address = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Handle address add/edit submission
$address_msg = "";
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $a1 = trim($_POST['address1']);
    $city = trim($_POST['city']);
    $state = trim($_POST['state']);
    $pincode = trim($_POST['pincode']);
    $country = trim($_POST['country']);
    $phone = trim($_POST['phone']);
    $isDefault = isset($_POST['is_default']) ? 1 : 0;

    if ($a1 && $city && $state && $pincode && $country && $phone) {
        if ($isDefault) {
            $pdo->prepare("UPDATE addresses SET IsDefault = 0 WHERE User_ID = ?")->execute([$user_id]);
        }

        if (isset($_POST['edit_id']) && is_numeric($_POST['edit_id'])) {
            // Update existing address
            $stmt = $pdo->prepare("UPDATE addresses SET AddressLine1=?, City=?, State=?, Pincode=?, Country=?, Phone=?, IsDefault=? WHERE Address_ID=? AND User_ID=?");
            $stmt->execute([$a1, $city, $state, $pincode, $country, $phone, $isDefault, intval($_POST['edit_id']), $user_id]);
            $address_msg = "<div class='alert alert-success mb-2'>Address updated successfully!</div>";
        } else {
            // Insert new address
            $stmt = $pdo->prepare("INSERT INTO addresses (User_ID, AddressLine1, City, State, Pincode, Country, Phone, IsDefault) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $a1, $city, $state, $pincode, $country, $phone, $isDefault]);
            $address_msg = "<div class='alert alert-success mb-2'>Address added successfully!</div>";
        }
        // Refresh edit_address if it was just edited
        $edit_address = null;
    } else {
        $address_msg = "<div class='alert alert-danger mb-2'>Please fill all required fields!</div>";
    }
}

// Fetch all addresses for the user
$addresses = $pdo->prepare("SELECT * FROM addresses WHERE User_ID = ? ORDER BY IsDefault DESC, CreatedAt DESC");
$addresses->execute([$user_id]);
$addressList = $addresses->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>🏠 My Addresses | Poultry Shop</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@600;700&family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    <style>
        body {background: linear-gradient(120deg, #f6d365 0%, #fda085 100%); min-height: 100vh; font-family: 'Roboto',sans-serif;}
        .address-section {background: #f6f9ff; border-radius: 15px; box-shadow: 0 4px 18px rgba(34,49,63,0.09); padding: 1.7em 1.2em 1.5em 1.2em; margin-top: 3em; margin-bottom: 2em; max-width: 700px;}
        .address-header {font-family:'Montserrat',sans-serif;font-weight:600;font-size:1.3rem;color:#2980b9; margin-bottom:.8em; letter-spacing:.5px;}
        .address-card {border-radius: 11px; background: #fff; box-shadow: 0 2px 7px rgba(30,90,120,0.08); margin-bottom: 1em; padding: 1em 1.1em; font-size: 1.07em; text-align: left; position: relative;}
        .address-default-badge {background: #00b894; color: #fff; font-size: .88em; border-radius: 12px; padding: 2px 13px; margin-left: 8px;}
        .edit-link, .delete-link {font-size: .97em; margin-left: 6px; text-decoration: none;}
        .edit-link {color: #2980b9;}
        .edit-link:hover {text-decoration: underline; color: #144d78;}
        .delete-link {color: #e74c3c;}
        .delete-link:hover {text-decoration: underline; color: #c0392b;}
        .form-label {font-weight:500;}
        .back-dashboard-btn {background: #3cba92; color: #fff !important; border-radius: 12px; font-weight: 500; font-size: 1.1em;}
        .back-dashboard-btn:hover {background: #2d8659; color: #fff !important;}
        @media(max-width:767px){.address-section {padding:1em .5em 1.2em .5em;}}
    </style>
</head>
<body>
    <div class="container d-flex flex-column align-items-center">
        <div class="address-section w-100">
            <div class="address-header mb-3"><span style="font-size:1.3em;">🏠</span> My Delivery Addresses</div>
            <?= $address_msg ?>
            <?php if (count($addressList)): ?>
                <?php foreach ($addressList as $addr): ?>
                    <div class="address-card">
                        <?= htmlspecialchars($addr['AddressLine1']) ?>
                        <br><?= htmlspecialchars($addr['City']) ?>, <?= htmlspecialchars($addr['State']) ?>, <?= htmlspecialchars($addr['Pincode']) ?>
                        <br><?= htmlspecialchars($addr['Country']) ?>
                        <br>📞 <b><?= htmlspecialchars($addr['Phone']) ?></b>
                        <?php if ($addr['IsDefault']): ?>
                            <span class="address-default-badge">Default</span>
                        <?php endif; ?>
                        <div class="mt-2">
                            <a href="user_address.php?edit=<?= $addr['Address_ID'] ?>" class="edit-link">✏️ Edit</a>
                            <a href="user_address.php?delete=<?= $addr['Address_ID'] ?>" class="delete-link" onclick="return confirm('Are you sure you want to delete this address?');">🗑️ Delete</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-muted mb-2">No address added yet.</div>
            <?php endif; ?>

            <form method="post" class="row g-3 mt-3 border-top pt-3">
                <?php if ($edit_address): ?>
                    <input type="hidden" name="edit_id" value="<?= (int)$edit_address['Address_ID'] ?>">
                <?php endif; ?>
                <div class="col-md-6">
                    <label class="form-label">Address Line 1 *</label>
                    <input type="text" name="address1" class="form-control" required value="<?= $edit_address ? htmlspecialchars($edit_address['AddressLine1']) : '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">City *</label>
                    <input type="text" name="city" class="form-control" required value="<?= $edit_address ? htmlspecialchars($edit_address['City']) : '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">State *</label>
                    <input type="text" name="state" class="form-control" required value="<?= $edit_address ? htmlspecialchars($edit_address['State']) : '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Pincode *</label>
                    <input type="text" name="pincode" class="form-control" required value="<?= $edit_address ? htmlspecialchars($edit_address['Pincode']) : '' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Country *</label>
                    <input type="text" name="country" class="form-control" required value="<?= $edit_address ? htmlspecialchars($edit_address['Country']) : 'India' ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" name="phone" class="form-control" required pattern="[0-9+ -]{8,16}" value="<?= $edit_address ? htmlspecialchars($edit_address['Phone']) : '' ?>">
                </div>
                <div class="col-12 mt-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" id="is_default"
                        <?php
                            if ($edit_address && $edit_address['IsDefault']) echo 'checked';
                            elseif (!$edit_address && !count($addressList)) echo 'checked';
                        ?>>
                        <label class="form-check-label" for="is_default">Set as my default delivery address</label>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" name="add_address" class="btn btn-primary px-4"><?= $edit_address ? 'Update Address' : 'Add Address' ?></button>
                    <?php if ($edit_address): ?>
                        <a href="user_address.php" class="btn btn-secondary px-4 ms-2">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>
            <div class="text-center mt-4">
                <a href="user_dashboard.php" class="btn back-dashboard-btn">← Back to Dashboard</a>
            </div>
        </div>
    </div>
</body>
</html>