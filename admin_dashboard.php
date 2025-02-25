<?php
session_start();
require_once 'config.php';

// Ensure user is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Handle creating a new competition
$msg = '';
if (isset($_POST['create_competition'])) {
    $name     = $_POST['name'];
    $date     = $_POST['date'];
    $location = $_POST['location'];
    $fee      = $_POST['entry_fee'];

    $stmt = $pdo->prepare("INSERT INTO competitions (name, date, location, entry_fee, status) 
                           VALUES (?,?,?,?, 'open')");
    $stmt->execute([$name, $date, $location, $fee]);
    $msg = "New competition created!";
}

// Handle closing or opening a competition
if (isset($_GET['toggle_id'])) {
    $compId = $_GET['toggle_id'];
    // Get current status
    $stmt = $pdo->prepare("SELECT status FROM competitions WHERE id=?");
    $stmt->execute([$compId]);
    $currentStatus = $stmt->fetchColumn();

    $newStatus = ($currentStatus === 'open') ? 'closed' : 'open';
    $update = $pdo->prepare("UPDATE competitions SET status=? WHERE id=?");
    $update->execute([$newStatus, $compId]);
    $msg = "Competition #$compId status changed to $newStatus.";
}

// Fetch all competitions
$stmt = $pdo->prepare("SELECT * FROM competitions ORDER BY date DESC");
$stmt->execute();
$competitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If admin wants to see participants or results for a competition
$participants = [];
$results = [];
if (isset($_GET['competition_id'])) {
    $compId = $_GET['competition_id'];

    // Get participants
    $stmtPart = $pdo->prepare("
        SELECT p.id, u.name, p.payment_status
        FROM participants p
        JOIN users u ON p.user_id = u.id
        WHERE p.competition_id=?
    ");
    $stmtPart->execute([$compId]);
    $participants = $stmtPart->fetchAll(PDO::FETCH_ASSOC);

    // Get results
    $stmtRes = $pdo->prepare("
        SELECT r.score, r.feedback, u.name AS choreographer_name, p.id AS participant_id
        FROM results r
        JOIN participants p ON r.participant_id = p.id
        JOIN users u ON r.choreographer_id = u.id
        WHERE r.competition_id=?
    ");
    $stmtRes->execute([$compId]);
    $results = $stmtRes->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - Dance Competition</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h1>Admin Dashboard</h1>
    <a href="logout.php" class="btn btn-danger mb-3">Logout</a>

    <?php if ($msg): ?>
        <div class="alert alert-info"><?php echo $msg; ?></div>
    <?php endif; ?>

    <!-- Create competition -->
    <div class="border rounded p-3 mb-4">
        <h4>Create New Competition</h4>
        <form method="post" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Competition Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" required>
            </div>
            <div class="col-md-3">
                <label class="form-label">Location</label>
                <input type="text" name="location" class="form-control" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Entry Fee</label>
                <input type="number" step="0.01" name="entry_fee" class="form-control" required>
            </div>
            <div class="col-12">
                <button type="submit" name="create_competition" class="btn btn-primary w-100">
                    Create Competition
                </button>
            </div>
        </form>
    </div>

    <!-- Competitions list -->
    <h4>All Competitions</h4>
    <ul class="list-group">
    <?php foreach($competitions as $comp): ?>
        <li class="list-group-item d-flex justify-content-between">
            <div>
                <strong><?php echo htmlspecialchars($comp['name']); ?></strong>
                <small>(<?php echo $comp['date']; ?> - <?php echo htmlspecialchars($comp['location']); ?>)</small>
                <span class="badge bg-info ms-2"><?php echo $comp['status']; ?></span>
            </div>
            <div>
                <a href="?competition_id=<?php echo $comp['id']; ?>" class="btn btn-sm btn-secondary me-2">
                    View Participants/Results
                </a>
                <a href="?toggle_id=<?php echo $comp['id']; ?>" class="btn btn-sm 
                    <?php echo $comp['status'] === 'open' ? 'btn-warning' : 'btn-success'; ?>">
                    <?php echo $comp['status'] === 'open' ? 'Close' : 'Open'; ?> Competition
                </a>
            </div>
        </li>
    <?php endforeach; ?>
    </ul>

    <?php if (isset($_GET['competition_id'])): ?>
        <hr>
        <h4>Participants in Competition #<?php echo $_GET['competition_id']; ?></h4>
        <table class="table table-bordered mb-3">
            <thead>
                <tr>
                    <th>Participant ID</th>
                    <th>Dancer Name</th>
                    <th>Payment Status</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($participants as $p): ?>
                <tr>
                    <td><?php echo $p['id']; ?></td>
                    <td><?php echo htmlspecialchars($p['name']); ?></td>
                    <td><?php echo $p['payment_status']; ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <h4>Results</h4>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Participant ID</th>
                    <th>Score</th>
                    <th>Feedback</th>
                    <th>Choreographer</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($results as $r): ?>
                <tr>
                    <td><?php echo $r['participant_id']; ?></td>
                    <td><?php echo $r['score']; ?></td>
                    <td><?php echo htmlspecialchars($r['feedback']); ?></td>
                    <td><?php echo htmlspecialchars($r['choreographer_name']); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
</body>
</html>
