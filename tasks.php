<?php
require_once 'config.php';

function post_task($pdo, $title, $description, $category, $deadline, $payment, $requester_id) {
    try {
        $stmt = $pdo->prepare("INSERT INTO tasks (requester_id, title, description, category, deadline, payment) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$requester_id, $title, $description, $category, $deadline, $payment]);
        header('Location: index.php?page=marketplace');
        exit;
    } catch (PDOException $e) {
        echo "<p class='text-danger'>Task posting failed: " . $e->getMessage() . "</p>";
    }
}

function take_task($pdo, $task_id, $worker_id) {
    $stmt = $pdo->prepare("SELECT status FROM tasks WHERE id = ?");
    $stmt->execute([$task_id]);
    $status = $stmt->fetchColumn();
    if ($status === 'open') {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, worker_id) VALUES (?, ?)");
            $stmt->execute([$task_id, $worker_id]);
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'taken' WHERE id = ?");
            $stmt->execute([$task_id]);
            $pdo->commit();
            header('Location: index.php?page=dashboard');
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo "<p class='text-danger'>Taking task failed: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p class='text-danger'>Task is no longer available.</p>";
    }
}

function submit_completion($pdo, $assignment_id, $submission, $worker_id) {
    try {
        $stmt = $pdo->prepare("UPDATE task_assignments SET status = 'submitted', submission = ?, completed_at = NOW() WHERE id = ? AND worker_id = ? AND status = 'taken'");
        $stmt->execute([$submission, $assignment_id, $worker_id]);
        if ($stmt->rowCount() > 0) {
            $stmt = $pdo->prepare("UPDATE tasks SET status = 'completed' WHERE id = (SELECT task_id FROM task_assignments WHERE id = ?)");
            $stmt->execute([$assignment_id]);
        }
        header('Location: index.php?page=dashboard');
        exit;
    } catch (PDOException $e) {
        echo "<p class='text-danger'>Submission failed: " . $e->getMessage() . "</p>";
    }
}

function review_submission($pdo, $assignment_id, $rating, $review, $approve, $requester_id) {
    $stmt = $pdo->prepare("SELECT task_id FROM task_assignments WHERE id = ? AND status = 'submitted'");
    $stmt->execute([$assignment_id]);
    $task_id = $stmt->fetchColumn();
    if ($task_id) {
        $stmt = $pdo->prepare("SELECT requester_id FROM tasks WHERE id = ?");
        $stmt->execute([$task_id]);
        if ($stmt->fetchColumn() === $requester_id) {
            $status = ($approve === 'yes') ? 'approved' : 'rejected';
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare("UPDATE task_assignments SET status = ?, rating = ?, review = ? WHERE id = ?");
                $stmt->execute([$status, $rating, $review, $assignment_id]);
                if ($approve === 'yes') {
                    $stmt = $pdo->prepare("SELECT payment FROM tasks WHERE id = ?");
                    $stmt->execute([$task_id]);
                    $payment = $stmt->fetchColumn();
                    $stmt = $pdo->prepare("SELECT worker_id FROM task_assignments WHERE id = ?");
                    $stmt->execute([$assignment_id]);
                    $worker_id = $stmt->fetchColumn();
                    $stmt = $pdo->prepare("UPDATE users SET balance = balance + ? WHERE id = ?");
                    $stmt->execute([$payment, $worker_id]);
                    $stmt = $pdo->prepare("UPDATE tasks SET status = 'reviewed' WHERE id = ?");
                    $stmt->execute([$task_id]);
                }
                $pdo->commit();
                header('Location: index.php?page=dashboard');
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                echo "<p class='text-danger'>Review failed: " . $e->getMessage() . "</p>";
            }
        }
    }
}
?>
