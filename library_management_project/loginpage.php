<?php
session_start();
require_once 'db_connect.php'; 

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email']);
    $id    = trim($_POST['id']);

    if (empty($email) || empty($id)) {
        $errors[] = "Please enter your Email and ID.";
    } else {

        $user = null;

        // 1 — Staff (Admin / Librarian)
        $stmt = $pdo->prepare("
            SELECT StaffID AS user_id, FullName, Role, 'librarian' AS dashboard
            FROM Staff
            WHERE Email = ? AND StaffID = ?
        ");
        $stmt->execute([$email, $id]);
        $user = $stmt->fetch();

        // 2 — Member
        if (!$user) {
            $stmt = $pdo->prepare("
                SELECT MemberID AS user_id, FullName, 'member' AS dashboard
                FROM Member
                WHERE Email = ? AND MemberID = ? AND Status = 'Active'
            ");
            $stmt->execute([$email, $id]);
            $user = $stmt->fetch();
        }

        // 3 — Author
        if (!$user) {
            $stmt = $pdo->prepare("
                SELECT AuthorID AS user_id, CONCAT(FirstName,' ',LastName) AS FullName, 'author' AS dashboard
                FROM Author
                WHERE Email = ? AND AuthorID = ?
            ");
            $stmt->execute([$email, $id]);
            $user = $stmt->fetch();
        }

        // 4 — Publisher
        if (!$user) {
            $stmt = $pdo->prepare("
                SELECT PublisherID AS user_id, Name AS FullName, 'publisher' AS dashboard
                FROM Publisher
                WHERE Email = ? AND PublisherID = ?
            ");
            $stmt->execute([$email, $id]);
            $user = $stmt->fetch();
        }

        // If we found a user → login
        if ($user) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name']    = $user['FullName'];
            $_SESSION['role']    = $user['dashboard'];

            header("Location: dashboard/{$user['dashboard']}-dashboard.php");
            exit;
        } else {
            $errors[] = "Incorrect Email or ID. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap');

        * { margin:0; padding:0; box-sizing:border-box; }

        body {
            height: 100vh;
            background: url('assets/img/librarybackground.png') center/cover no-repeat fixed;
            font-family: 'Playfair Display', serif;
            display:flex;
            align-items:center;
            justify-content:center;
            color:white;
        }

        .glass {
            background: rgba(255,255,255,0.18);
            backdrop-filter: blur(18px);
            border-radius: 28px;
            padding: 60px 50px;
            width: 420px;
            text-align: center;
            border: 1px solid rgba(255,255,255,0.3);
            box-shadow: 0 15px 45px rgba(0,0,0,0.6);
        }

        h1 {
            color: darkred;
            font-size: 44px;
            margin-bottom: 10px;
            text-shadow: 0 3px 10px rgba(0,0,0,0.6);
        }

        .subtitle {
            color: #ffeb3b;
            font-size: 18px;
            margin-bottom: 28px;
        }

        input {
            width: 100%;
            padding: 16px;
            margin: 12px 0;
            border: none;
            border-radius: 16px;
            background: rgba(255,255,255,0.35);
            color: black;
            font-size: 17px;
        }

        .pass-box { position:relative; }

        #toggle {
            position:absolute;
            right:15px;
            top:50%;
            transform:translateY(-50%);
            cursor:pointer;
            color:#222;
            font-size:20px;
        }

        .error {
            background: rgba(255,0,0,0.35);
            border-left: 5px solid darkred;
            padding: 12px;
            margin-bottom: 12px;
            border-radius: 8px;
            font-size: 15px;
            text-align: left;
        }

        button {
            width: 100%;
            padding:16px;
            margin-top: 22px;
            border: none;
            border-radius: 30px;
            background:white;
            color:black;
            font-size:22px;
            font-weight:bold;
            cursor:pointer;
            transition: .4s;
        }

        button:hover {
            background:black;
            color:white;
            transform: scale(1.05);
        }

        .info { margin-top: 25px; font-size:14px; color:#eee; }
    </style>
</head>
<body>

<div class="glass">
    <h1>Library Portal</h1>
    <p class="subtitle">Welcome Back</p>

    <?php if($errors): ?>
        <?php foreach($errors as $e): ?>
            <div class="error">
                <i class="fa fa-exclamation-circle"></i> 
                <?= htmlspecialchars($e) ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="POST">
        <input type="email" name="email" placeholder="Email Address" required autofocus>

        <div class="pass-box">
            <input type="password" name="id" id="pass" placeholder="Your ID" required>
            <i class="far fa-eye" id="toggle"></i>
        </div>

        <button type="submit">Login</button>
    </form>

    <div class="info">
        Admins & Staff → Email + Staff ID<br>
        Members → Email + Member ID
    </div>
</div>

<script>
    document.getElementById('toggle').onclick = function () {
        const p = document.getElementById('pass');
        if (p.type === 'password') {
            p.type = 'text';
            this.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            p.type = 'password';
            this.classList.replace('fa-eye-slash', 'fa-eye');
        }
    };
</script>

</body>
</html>
