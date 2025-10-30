<?php
require_once 'auth.php';
require_once 'tasks.php';

if (isset($_POST['register'])) {
    register($pdo, trim($_POST['username']), trim($_POST['email']), trim($_POST['password']), $_POST['role']);
}

if (isset($_POST['login'])) {
    login($pdo, trim($_POST['username']), trim($_POST['password']));
}

if (isset($_POST['logout'])) {
    logout();
}

if (isset($_POST['post_task']) && isset($_SESSION['role']) && $_SESSION['role'] === 'requester') {
    post_task($pdo, trim($_POST['title']), trim($_POST['description']), $_POST['category'], $_POST['deadline'], (float) $_POST['payment'], $_SESSION['user_id']);
}

if (isset($_POST['take_task']) && isset($_SESSION['role']) && $_SESSION['role'] === 'worker') {
    take_task($pdo, (int) $_POST['task_id'], $_SESSION['user_id']);
}

if (isset($_POST['submit_completion']) && isset($_SESSION['role']) && $_SESSION['role'] === 'worker') {
    submit_completion($pdo, (int) $_POST['assignment_id'], trim($_POST['submission']), $_SESSION['user_id']);
}

if (isset($_POST['review_submission']) && isset($_SESSION['role']) && $_SESSION['role'] === 'requester') {
    review_submission($pdo, (int) $_POST['assignment_id'], (int) $_POST['rating'], trim($_POST['review']), $_POST['approve'], $_SESSION['user_id']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MicroTask Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="?">MicroTask</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="?page=home">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="?page=marketplace">Marketplace</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="nav-link" href="?page=dashboard">Dashboard</a></li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <form method="post" class="d-inline">
                                <button type="submit" name="logout" class="btn btn-outline-primary">Logout</button>
                            </form>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="?page=login">Login</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php
        $page = $_GET['page'] ?? 'home';

        if ($page === 'home') {
            echo '<h1>Welcome to MicroTask</h1>';
            echo '<p>A platform inspired by Amazon MTurk where workers complete small tasks for payments, and requesters post tasks.</p>';
            echo '<p>How it works: Requesters post tasks with descriptions, deadlines, and payments. Workers browse, take, and complete them to earn money.</p>';

            if (!isset($_SESSION['user_id'])) {
                echo '<div class="row">';
                echo '<div class="col-md-6">';
                echo '<h2>Sign Up as Requester</h2>';
                echo '<form method="post">';
                echo '<input type="hidden" name="role" value="requester">';
                echo '<div class="mb-3"><input class="form-control" name="username" placeholder="Username" required></div>';
                echo '<div class="mb-3"><input class="form-control" type="email" name="email" placeholder="Email" required></div>';
                echo '<div class="mb-3"><input class="form-control" type="password" name="password" placeholder="Password" required></div>';
                echo '<button class="btn btn-primary" name="register">Sign Up</button>';
                echo '</form>';
                echo '</div>';
                echo '<div class="col-md-6">';
                echo '<h2>Sign Up as Worker</h2>';
                echo '<form method="post">';
                echo '<input type="hidden" name="role" value="worker">';
                echo '<div class="mb-3"><input class="form-control" name="username" placeholder="Username" required></div>';
                echo '<div class="mb-3"><input class="form-control" type="email" name="email" placeholder="Email" required></div>';
                echo '<div class="mb-3"><input class="form-control" type="password" name="password" placeholder="Password" required></div>';
                echo '<button class="btn btn-primary" name="register">Sign Up</button>';
                echo '</form>';
                echo '</div>';
                echo '</div>';
            }

            echo '<h2 class="mt-4">Featured Tasks</h2>';
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE status = 'open' ORDER BY RAND() LIMIT 3");
            $stmt->execute();
            $featured = $stmt->fetchAll();
            if ($featured) {
                echo '<div class="row">';
                foreach ($featured as $task) {
                    echo '<div class="col-md-4">';
                    echo '<div class="card mb-3">';
                    echo '<div class="card-body">';
                    echo '<h5 class="card-title">' . htmlspecialchars($task['title']) . '</h5>';
                    echo '<p class="card-text">' . htmlspecialchars($task['description']) . '</p>';
                    echo '<p class="card-text"><small class="text-muted">Payment: $' . number_format($task['payment'], 2) . ' | Deadline: ' . $task['deadline'] . '</small></p>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                echo '<p>No featured tasks available.</p>';
            }
        } elseif ($page === 'login') {
            if (isset($_SESSION['user_id'])) {
                header('Location: index.php?page=dashboard');
                exit;
            }
            echo '<h1>Login</h1>';
            echo '<form method="post">';
            echo '<div class="mb-3"><input class="form-control" name="username" placeholder="Username" required></div>';
            echo '<div class="mb-3"><input class="form-control" type="password" name="password" placeholder="Password" required></div>';
            echo '<button class="btn btn-primary" name="login">Login</button>';
            echo '</form>';
        } elseif ($page === 'marketplace') {
            echo '<h1>Task Marketplace</h1>';

            if (isset($_SESSION['role']) && $_SESSION['role'] === 'requester') {
                echo '<h2>Post a New Task</h2>';
                echo '<form method="post">';
                echo '<div class="mb-3"><input class="form-control" name="title" placeholder="Title" required></div>';
                echo '<div class="mb-3"><textarea class="form-control" name="description" placeholder="Description" required></textarea></div>';
                echo '<div class="mb-3"><select class="form-select" name="category" required>';
                echo '<option value="Data Entry">Data Entry</option>';
                echo '<option value="Surveys">Surveys</option>';
                echo '<option value="Transcription">Transcription</option>';
                echo '</select></div>';
                echo '<div class="mb-3"><input class="form-control" type="date" name="deadline" required></div>';
                echo '<div class="mb-3"><input class="form-control" type="number" step="0.01" name="payment" placeholder="Payment (e.g., 0.50)" required></div>';
                echo '<button class="btn btn-primary" name="post_task">Post Task</button>';
                echo '</form>';
            }

            echo '<h2 class="mt-4">Available Tasks</h2>';
            $stmt = $pdo->prepare("SELECT * FROM tasks WHERE status = 'open'");
            $stmt->execute();
            $tasks = $stmt->fetchAll();
            if ($tasks) {
                foreach ($tasks as $task) {
                    echo '<div class="card mb-3">';
                    echo '<div class="card-body">';
                    echo '<h5 class="card-title">' . htmlspecialchars($task['title']) . '</h5>';
                    echo '<p class="card-text">' . htmlspecialchars($task['description']) . '</p>';
                    echo '<p class="card-text"><small class="text-muted">Category: ' . htmlspecialchars($task['category']) . ' | Deadline: ' . $task['deadline'] . ' | Payment: $' . number_format($task['payment'], 2) . '</small></p>';
                    if (isset($_SESSION['role']) && $_SESSION['role'] === 'worker') {
                        echo '<form method="post" class="d-inline">';
                        echo '<input type="hidden" name="task_id" value="' . $task['id'] . '">';
                        echo '<button class="btn btn-success" name="take_task">Take Task</button>';
                        echo '</form>';
                    }
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<p>No available tasks.</p>';
            }
        } elseif ($page === 'dashboard') {
            if (!isset($_SESSION['user_id'])) {
                header('Location: index.php?page=login');
                exit;
            }

            $role = $_SESSION['role'];
            if ($role === 'worker') {
                echo '<h1>Worker Dashboard</h1>';

                $stmt = $pdo->prepare("SELECT SUM(t.payment) AS total FROM task_assignments a JOIN tasks t ON a.task_id = t.id WHERE a.worker_id = ? AND a.status = 'approved'");
                $stmt->execute([$_SESSION['user_id']]);
                $earnings = $stmt->fetch()['total'] ?? 0.00;
                echo '<p><strong>Earnings Summary:</strong> $' . number_format($earnings, 2) . '</p>';

                echo '<h3>Withdrawal Options</h3>';
                echo '<p>This is a dummy withdrawal system. Imagine withdrawing to PayPal or bank account.</p>';
                echo '<button class="btn btn-outline-secondary" disabled>Withdraw (Dummy)</button>';

                echo '<h2 class="mt-4">Your Tasks</h2>';
                $stmt = $pdo->prepare("SELECT a.*, t.title, t.description, t.payment, t.deadline FROM task_assignments a JOIN tasks t ON a.task_id = t.id WHERE a.worker_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $assignments = $stmt->fetchAll();
                if ($assignments) {
                    foreach ($assignments as $assign) {
                        echo '<div class="card mb-3">';
                        echo '<div class="card-body">';
                        echo '<h5 class="card-title">' . htmlspecialchars($assign['title']) . '</h5>';
                        echo '<p class="card-text">' . htmlspecialchars($assign['description']) . '</p>';
                        echo '<p class="card-text"><small class="text-muted">Status: ' . ucfirst($assign['status']) . ' | Payment: $' . number_format($assign['payment'], 2) . ' | Deadline: ' . $assign['deadline'] . '</small></p>';
                        if ($assign['status'] === 'taken') {
                            echo '<form method="post">';
                            echo '<input type="hidden" name="assignment_id" value="' . $assign['id'] . '">';
                            echo '<div class="mb-3"><textarea class="form-control" name="submission" placeholder="Submit your work here (e.g., data entry results, survey answers)" required></textarea></div>';
                            echo '<button class="btn btn-primary" name="submit_completion">Submit Completion</button>';
                            echo '</form>';
                        } elseif ($assign['status'] === 'approved' && $assign['rating']) {
                            echo '<p><strong>Rating:</strong> ' . $assign['rating'] . '/5</p>';
                            echo '<p><strong>Review:</strong> ' . htmlspecialchars($assign['review'] ?? 'No review') . '</p>';
                        } elseif ($assign['status'] === 'rejected') {
                            echo '<p><strong>Review:</strong> ' . htmlspecialchars($assign['review'] ?? 'No review') . '</p>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No tasks assigned.</p>';
                }
            } elseif ($role === 'requester') {
                echo '<h1>Requester Dashboard</h1>';
                echo '<h2>Your Posted Tasks</h2>';
                $stmt = $pdo->prepare("SELECT * FROM tasks WHERE requester_id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $tasks = $stmt->fetchAll();
                if ($tasks) {
                    foreach ($tasks as $task) {
                        echo '<div class="card mb-3">';
                        echo '<div class="card-body">';
                        echo '<h5 class="card-title">' . htmlspecialchars($task['title']) . '</h5>';
                        echo '<p class="card-text">' . htmlspecialchars($task['description']) . '</p>';
                        echo '<p class="card-text"><small class="text-muted">Status: ' . ucfirst($task['status']) . ' | Payment: $' . number_format($task['payment'], 2) . ' | Deadline: ' . $task['deadline'] . '</small></p>';

                        $stmt2 = $pdo->prepare("SELECT a.*, u.username FROM task_assignments a JOIN users u ON a.worker_id = u.id WHERE a.task_id = ?");
                        $stmt2->execute([$task['id']]);
                        $assign = $stmt2->fetch();
                        if ($assign) {
                            echo '<p><strong>Worker:</strong> ' . htmlspecialchars($assign['username']) . '</p>';
                            if ($assign['status'] === 'submitted') {
                                echo '<p><strong>Submission:</strong> ' . htmlspecialchars($assign['submission']) . '</p>';
                                echo '<form method="post">';
                                echo '<input type="hidden" name="assignment_id" value="' . $assign['id'] . '">';
                                echo '<div class="mb-3"><select class="form-select" name="approve" required><option value="yes">Approve and Pay</option><option value="no">Reject</option></select></div>';
                                echo '<div class="mb-3"><input class="form-control" type="number" min="1" max="5" name="rating" placeholder="Rating (1-5)" required></div>';
                                echo '<div class="mb-3"><textarea class="form-control" name="review" placeholder="Review" required></textarea></div>';
                                echo '<button class="btn btn-primary" name="review_submission">Submit Review</button>';
                                echo '</form>';
                            } elseif (in_array($assign['status'], ['approved', 'rejected'])) {
                                echo '<p><strong>Status:</strong> ' . ucfirst($assign['status']) . '</p>';
                                echo '<p><strong>Rating Given:</strong> ' . $assign['rating'] . '/5</p>';
                                echo '<p><strong>Review:</strong> ' . htmlspecialchars($assign['review'] ?? 'No review') . '</p>';
                            }
                        } else {
                            echo '<p>No worker has taken this task yet.</p>';
                        }
                        echo '</div>';
                        echo '</div>';
                    }
                } else {
                    echo '<p>No tasks posted.</p>';
                }
            }
        } else {
            echo '<h1>Page Not Found</h1>';
        }
        ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
?>
