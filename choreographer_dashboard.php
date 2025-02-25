<?php
session_start();
require_once 'config.php';

// Ensure user is a choreographer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'choreographer') {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch all competitions
$stmt = $pdo->prepare("SELECT * FROM competitions ORDER BY date DESC");
$stmt->execute();
$competitions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// If choreographer assigns a score
if (isset($_POST['assign_score'])) {
    $competition_id = $_POST['competition_id'];
    $participant_id = $_POST['participant_id'];
    $score          = $_POST['score'];
    $feedback       = $_POST['feedback'];

    // Insert or update results
    // Check if there's an existing record
    $checkStmt = $pdo->prepare("SELECT id FROM results 
                                WHERE competition_id=? AND participant_id=? AND choreographer_id=?");
    $checkStmt->execute([$competition_id, $participant_id, $user_id]);
    if ($checkStmt->rowCount() > 0) {
        // update existing
        $res = $checkStmt->fetch(PDO::FETCH_ASSOC);
        $updateStmt = $pdo->prepare("
            UPDATE results
            SET score=?, feedback=?
            WHERE id=?
        ");
        $updateStmt->execute([$score, $feedback, $res['id']]);
        $msg = "Score updated successfully!";
    } else {
        // insert new
        $insertStmt = $pdo->prepare("
            INSERT INTO results (competition_id, participant_id, choreographer_id, score, feedback)
            VALUES (?, ?, ?, ?, ?)
        ");
        $insertStmt->execute([$competition_id, $participant_id, $user_id, $score, $feedback]);
        $msg = "Score assigned successfully!";
    }
}

// Retrieve participants for a selected competition
$participants = [];
if (isset($_GET['competition_id'])) {
    $compId = $_GET['competition_id'];
    $stmtPart = $pdo->prepare("
        SELECT p.id as participant_id, u.name as dancer_name, p.payment_status
        FROM participants p
        JOIN users u ON p.user_id = u.id
        WHERE p.competition_id = ?
    ");
    $stmtPart->execute([$compId]);
    $participants = $stmtPart->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Choreographer Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container mt-4">
    <h1>Choreographer Dashboard</h1>
    <a href="logout.php" class="btn btn-danger mb-3">Logout</a>

    <?php if (isset($msg)): ?>
        <div class="alert alert-success"><?php echo $msg; ?></div>
    <?php endif; ?>

    <h3>Competitions</h3>
    <ul class="list-group mb-3">
        <?php foreach($competitions as $comp): ?>
            <li class="list-group-item d-flex justify-content-between">
                <div>
                    <strong><?php echo htmlspecialchars($comp['name']); ?></strong> 
                    (<?php echo $comp['date']; ?> - <?php echo htmlspecialchars($comp['location']); ?>)
                    <small class="ms-2">Status: <?php echo $comp['status']; ?></small>
                </div>
                <a href="?competition_id=<?php echo $comp['id']; ?>" class="btn btn-sm btn-primary">
                    View Participants
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <?php if (isset($_GET['competition_id'])): ?>
        <h3>Participants for Competition #<?php echo $_GET['competition_id']; ?></h3>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Dancer Name</th>
                    <th>Payment Status</th>
                    <th>Score/Feedback</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($participants as $part): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($part['dancer_name']); ?></td>
                        <td><?php echo $part['payment_status']; ?></td>
                        <td>
                            <!-- Score assignment form -->
                            <form method="post" class="d-flex flex-column flex-md-row align-items-md-center">
                                <input type="hidden" name="competition_id" value="<?php echo $_GET['competition_id']; ?>">
                                <input type="hidden" name="participant_id" value="<?php echo $part['participant_id']; ?>">
                                <input type="number" name="score" class="form-control me-2 mb-2 mb-md-0" placeholder="Score 0-100" required>
                                <input type="text" name="feedback" class="form-control me-2 mb-2 mb-md-0" placeholder="Feedback (optional)">
                                <button type="submit" name="assign_score" class="btn btn-primary">Submit</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</div>
</body>
</html>
