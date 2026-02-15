<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'publisher') {
    header('Location: ../loginpage.php');
    exit;
}
require_once '../db_connect.php';

$publisher_id = $_SESSION['user_id'];

// استعلامات الناشر (قوية ومفيدة)
$queries = [

    // 1) كتبي المنشورة
    "Published Books" => "SELECT 
                             b.Title, 
                             b.PublishYear, 
                             cat.Name AS Category, 
                             COUNT(c.CopyID) AS Total_Copies
                          FROM Book b
                          JOIN Category cat ON b.CategoryID = cat.CategoryID
                          LEFT JOIN Copy c ON b.BookID = c.BookID
                          WHERE b.PublisherID = ?
                          GROUP BY b.BookID, b.Title, b.PublishYear, cat.Name",

    // 2) أكثر كتبي طلبًا
    "Most Borrowed My Books" => "SELECT 
                                    b.Title, 
                                    COUNT(l.LoanID) AS Times_Loaned
                                 FROM Book b
                                 JOIN Copy c ON b.BookID = c.BookID
                                 JOIN Loan l ON c.CopyID = l.CopyID
                                 WHERE b.PublisherID = ?
                                 GROUP BY b.BookID, b.Title
                                 ORDER BY Times_Loaned DESC
                                 LIMIT 5",

    // 3) إجمالي الإعارات والغرامات لكتبي
    "Total Loans And Fines For My Books" => "SELECT 
                                                COUNT(l.LoanID) AS Total_Loans, 
                                                SUM(l.FineAmount) AS Total_Fines
                                             FROM Book b
                                             JOIN Copy c ON b.BookID = c.BookID
                                             JOIN Loan l ON c.CopyID = l.CopyID
                                             WHERE b.PublisherID = ?",

    // 4) النسخ المتاحة من كتبي
    "Available Copies Of My Books" => "SELECT 
                                          b.Title, 
                                          COUNT(c.CopyID) AS Available_Copies
                                       FROM Book b
                                       JOIN Copy c ON b.BookID = c.BookID
                                       WHERE b.PublisherID = ? 
                                         AND c.Status = 'Available'
                                       GROUP BY b.BookID, b.Title",

    // 5) الكتب المتأخرة من كتبي
    "Late Loans Of My Books" => "SELECT 
                                    b.Title, 
                                    m.FullName, 
                                    l.DueDate, 
                                    DATEDIFF(CURDATE(), l.DueDate) AS Days_Late
                                 FROM Book b
                                 JOIN Copy c ON b.BookID = c.BookID
                                 JOIN Loan l ON c.CopyID = l.CopyID
                                 JOIN Member m ON l.MemberID = m.MemberID
                                 WHERE b.PublisherID = ? 
                                   AND l.Status = 'Active' 
                                   AND l.DueDate < CURDATE()
                                 ORDER BY Days_Late DESC"
];


$results = [];
foreach ($queries as $title => $sql) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$publisher_id]);
    $results[$title] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>publisher control panel </title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;800&family=Playfair+Display:wght@700&display=swap');
        
        body {margin:0;padding:20px 15px;min-height:100vh;background:url('../assets/img/librarian-dashboard-img.png') center/cover fixed;font-family:'Cairo',sans-serif;color:white;}
        .container {max-width:1100px;margin:0 auto;}
        h1 {text-align:center;font-family:'Playfair Display',serif;font-size:52px;color:#228B22;text-shadow:0 0 30px rgba(34,139,34,0.6);margin-bottom:10px;}
        .welcome {text-align:center;font-size:28px;color:#228B22;margin-bottom:50px;}
        .grid {display:grid;grid-template-columns:repeat(auto-fill,minmax(480px,1fr));gap:30px;}
        .card {background:rgba(255,255,255,0.15);backdrop-filter:blur(20px);border-radius:24px;padding:32px;border:1px solid rgba(34,139,34,0.3);box-shadow:0 15px 45px rgba(0,0,0,0.7);transition:0.5s;}
        .card:hover {transform:translateY(-18px);box-shadow:0 30px 60px rgba(34,139,34,0.4);}
        .card h3 {color:#228B22;font-size:26px;text-align:center;margin-bottom:20px;}
        .btn {width:100%;padding:18px;background:linear-gradient(135deg,#228B22,#006400);border:none;border-radius:16px;color:white;font-size:20px;font-weight:bold;cursor:pointer;transition:0.4s;}
        .btn:hover {background:linear-gradient(135deg,#006400,#228B22);transform:scale(1.05);}
        .result {display:none;margin-top:25px;background:rgba(0,0,0,0.5);border-radius:16px;overflow:hidden;max-height:500px;overflow-y:auto;}
        table {width:100%;border-collapse:collapse;}
        th,td {padding:15px;text-align:center;border-bottom:1px solid rgba(255,255,255,0.15);}
        th {background:rgba(34,139,34,0.9);color:white;font-weight:bold;}
        tr:hover {background:rgba(255,255,255,0.12);}
        .no-data {text-align:center;padding:50px;color:#ff6b6b;font-size:22px;}
        .high-sales {color:#228B22;font-weight:bold;}
        .logout {display:block;width:fit-content;margin:70px auto;padding:20px 60px;background:linear-gradient(45deg,#ff1744,#c62828);color:white;text-decoration:none;border-radius:60px;font-size:24px;font-weight:bold;transition:0.5s;}
        .logout:hover {transform:scale(1.15);box-shadow:0 0 40px rgba(255,0,0,0.8);}
    </style>
</head>
<body>

<div class="container">
    <h1>publisher control panel </h1>
    <p class="welcome">welcome back! <?=htmlspecialchars($_SESSION['name'])?></p>

    <div class="grid">
        <?php foreach ($queries as $title => $sql): 
            $id = 'publisher-result-' . md5($title); ?>
            <div class="card">
                <h3><?=$title?></h3>
                <button class="btn" onclick="let el=document.getElementById('<?=$id?>'); el.style.display=el.style.display==='block'?'none':'block';">
                    show details 
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
                                    <?php foreach ($row as $key => $cell): ?>
                                        <?php if ($key === 'Total_Loans' && $cell > 5): ?>
                                            <td class="high-sales"><?=$cell?> "high sales"</td>
                                        <?php else: ?>
                                            <td><?=htmlspecialchars($cell)?></td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p class="no-data">no data now </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <a href="../logout.php" class="logout">logout</a>
</div>

</body>
</html>