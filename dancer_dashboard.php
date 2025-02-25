<?php
session_start();
require_once 'config.php';

// Verify session & role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dancer') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Dummy wallet balance (for display)
$wallet_balance = 100.00;

// Fetch all open competitions
$stmt = $pdo->prepare("SELECT * FROM competitions WHERE status = 'open'");
$stmt->execute();
$competitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Payment Success
// If the user returns with ?payment=success, record their participation.
if (isset($_GET['payment']) && $_GET['payment'] == 'success') {
    $competition_id = $_GET['competition_id'];

    // Insert participant record
    $stmt = $pdo->prepare("
        INSERT INTO participants (competition_id, user_id, payment_status)
        VALUES (?, ?, 'paid')
    ");
    $stmt->execute([$competition_id, $user_id]);

    $msg = "Participation confirmed! Payment received.";
}

// Fetch user's participations
$stmtPart = $pdo->prepare("
    SELECT c.name, c.date, c.location, p.payment_status
    FROM participants p
    JOIN competitions c ON p.competition_id = c.id
    WHERE p.user_id = ?
");
$stmtPart->execute([$user_id]);
$myParticipations = $stmtPart->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dancer Dashboard</title>
    <!-- Responsive meta tag -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    
    <style>
        /* Optional: Some minor styling */
        body {
            background-color: #f8f9fa;
        }
        .navbar {
            margin-bottom: 20px;
        }
        .card {
            margin-bottom: 20px;
        }
        .footer {
            text-align: center;
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 30px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg bg-primary">
    <div class="container">
        <a class="navbar-brand text-white" href="#">DANCE ON</a>
        <div class="ms-auto d-flex align-items-center">
            <div class="me-3">
                <strong>Wallet:</strong> $<?php echo number_format($wallet_balance, 2); ?>
            </div>
            <a href="support.php" class="nav-link text-white me-3">Support</a>
            <form method="post" action="logout.php" class="m-0 p-0">
                <button type="submit" class="btn text-white">Logout</button>
            </form>
        </div>
    </div>
</nav>

<!-- Main Container -->
<div class="container">

    <!-- Display message if payment was successful -->
    <?php if (isset($msg)): ?>
        <div class="alert alert-success text-center"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Open Competitions -->
    <h3>Open Competitions</h3>
    <div class="row">
        <?php foreach ($competitions as $comp): ?>
            <div class="col-md-4">
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($comp['name']); ?></h5>
                        <p><strong>Date:</strong> <?php echo $comp['date']; ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($comp['location']); ?></p>
                        <p><strong>Entry Fee:</strong> $<?php echo number_format($comp['entry_fee'], 2); ?></p>
                        
                        <?php 
                            // We'll attempt to include a return URL in the UPI link,
                            // but be aware that many UPI apps do not automatically redirect back.
                            $upiId    = 'hemu1@kotak';  // Your UPI ID
                            $amount   = number_format($comp['entry_fee'], 2);
                            
                            // URL-encode the page URL for possible callback
                            // e.g., ?payment=success&competition_id=...
                            $returnUrl = urlencode(
                                'http://yourdomain.com/dancer_dashboard.php?payment=success&competition_id=' . $comp['id']
                            );
                            
                            // Construct the UPI link
                            $upiLink = "upi://pay?pa={$upiId}&pn=DanceCompetition&tn=Competition+Entry+Fee&am={$amount}&cu=INR&url={$returnUrl}";
                        ?>
                        
                        <!-- Pay & Participate Button as a direct anchor -->
                        <a href="<?php echo $upiLink; ?>" 
                           class="btn btn-primary w-100"
                           target="_blank">
                           Pay &amp; Participate
                        </a>
                        
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- My Participations -->
    <h3 class="mt-4">My Participations</h3>
    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Competition</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Payment Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($myParticipations as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo $row['date']; ?></td>
                        <td><?php echo htmlspecialchars($row['location']); ?></td>
                        <td>
                            <span class="badge bg-success">
                                <?php echo htmlspecialchars($row['payment_status']); ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Footer -->
<div class="footer">
    <p>&copy; 2025 Dance Competition. All rights reserved.</p>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
