<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'author') {
    header('Location: ../loginpage.php');
    exit;
}
require_once '../db_connect.php';

$author_id = $_SESSION['user_id'];

// Get author's full name
$stmt = $pdo->prepare("SELECT CONCAT(FirstName,' ',LastName) AS AuthorName FROM Author WHERE AuthorID = ?");
$stmt->execute([$author_id]);
$author = $stmt->fetch();

// Author dashboard queries
$queries = [
    "My Published Books" => "SELECT b.Title, b.PublishYear, p.Name AS PublisherName,
                             c.Name AS CategoryName
                             FROM Book b
                             JOIN BookAuthor ba ON b.BookID = ba.BookID
                             LEFT JOIN Publisher p ON b.PublisherID = p.PublisherID
                             LEFT JOIN Category c ON b.CategoryID = c.CategoryID
                             WHERE ba.AuthorID = ?",

    "Most Borrowed of My Books" => "SELECT b.Title, COUNT(l.LoanID) AS TimesBorrowed
                                    FROM Book b
                                    JOIN BookAuthor ba ON b.BookID = ba.BookID
                                    JOIN Copy cp ON b.BookID = cp.BookID
                                    JOIN Loan l ON cp.CopyID = l.CopyID
                                    WHERE ba.AuthorID = ?
                                    GROUP BY b.BookID, b.Title
                                    ORDER BY TimesBorrowed DESC
                                    LIMIT 10",

    "Total Copies of My Books" => "SELECT b.Title, COUNT(cp.CopyID) AS TotalCopies
                                   FROM Book b
                                   JOIN BookAuthor ba ON b.BookID = ba.BookID
                                   LEFT JOIN Copy cp ON b.BookID = cp.BookID
                                   WHERE ba.AuthorID = ?
                                   GROUP BY b.BookID, b.Title",

    "Available Copies of My Books" => "SELECT b.Title, COUNT(cp.CopyID) AS AvailableCopies
                                       FROM Book b
                                       JOIN BookAuthor ba ON b.BookID = ba.BookID
                                       JOIN Copy cp ON b.BookID = cp.BookID
                                       WHERE ba.AuthorID = ? AND cp.Status = 'Available'
                                       GROUP BY b.BookID, b.Title"
];

$results = [];
foreach ($queries as $title => $sql) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$author_id]);
    $results[$title] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Author Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Cairo:wght@400;700&display=swap');

        body{margin:0;padding:20px 15px;min-height:100vh;background:url('../assets/img/librarian-dashboard-img.png') center/cover fixed;font-family:'Cairo',sans-serif;color:white;}
        .container{max-width:1200px;margin:0 auto;}
        h1{text-align:center;font-family:'Playfair Display',serif;font-size:54px;color:#D2691E;text-shadow:0 0 30px rgba(210,105,30,0.6);margin-bottom:8px;}
        .welcome{text-align:center;font-size:28px;color:#FF8C00;margin-bottom:50px;}
        .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(460px,1fr));gap:32px;}
        .card{background:rgba(255,255,255,0.15);backdrop-filter:blur(20px);border-radius:24px;padding:32px;border:1px solid rgba(210,105,30,0.3);box-shadow:0 15px 45px rgba(0,0,0,0.7);transition:0.5s;}
        .card:hover{transform:translateY(-18px);box-shadow:0 30px 60px rgba(210,105,30,0.4);}
        .card h3{color:#D2691E;font-size:26px;text-align:center;margin-bottom:20px;font-weight:700;}
        .btn{width:100%;padding:18px;background:linear-gradient(135deg,#8B4513,#D2691E);border:none;border-radius:16px;color:white;font-size:20px;font-weight:bold;cursor:pointer;transition:0.4s;}
        .btn:hover{background:linear-gradient(135deg,#A0522D,#CD853F);transform:scale(1.05);}
        .result{display:none;margin-top:25px;background:rgba(0,0,0,0.5);border-radius:16px;overflow:hidden;max-height:500px;overflow-y:auto;}
        table{width:100%;border-collapse:collapse;}
        th,td{padding:15px;text-align:center;border-bottom:1px solid rgba(255,255,255,0.15);}
        th{background:rgba(139,69,19,0.9);color:#FFD700;font-weight:bold;}
        tr:hover{background:rgba(255,255,255,0.12);}
        .no-data{text-align:center;padding:50px;color:#ff6b6b;font-size:22px;}
        .logout{display:block;width:fit-content;margin:80px auto;padding:22px 70px;background:linear-gradient(45deg,#ff1744,#c62828);color:white;text-decoration:none;border-radius:60px;font-size:26px;font-weight:bold;transition:0.5s;}
        .logout:hover{transform:scale(1.15);box-shadow:0 0 50px rgba(255,0,0,0.8);}
    </style>
</head>
<body>

<div class="container">
    <h1>Author control panel</h1>
    <p class="welcome">Welcome <?=htmlspecialchars($author['AuthorName'] ?? 'Author')?></p>

    <div class="grid">
        <?php foreach ($queries as $title => $sql):
            $id = 'author-result-' . md5($title); ?>
            <div class="card">
                <h3><?=$title?></h3>
                <button class="btn" onclick="let el=document.getElementById('<?=$id?>'); el.style.display=el.style.display==='block'?'none':'block';">
                    View Details
                </button>

                <div class="result" id="<?=$id?>">
                    <?php if (!empty($results[$title])): ?>
                        <table>
                            <tr>
                                <?php foreach (array_keys($results[$title][0]) as $header): ?>
                                    <th><?=htmlspecialchars($header)?></th>
                                <?php endforeach; ?>
                            </tr>
                            <?php foreach ($results[$title] as $row): ?>
                                <tr>
                                    <?php foreach ($row as $cell): ?>
                                        <td><?=htmlspecialchars($cell)?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p class="no-data">No data now </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <a href="../logout.php" class="logout">Logout</a>
</div>

</body>
</html>