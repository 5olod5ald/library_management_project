<?php
session_start();

// لو مش عضو → ارجع لللوجن
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'member') {
    header('Location: ../loginpage.php');
    exit;
}

require_once '../db_connect.php';
$member_id = $_SESSION['user_id'];

// استعلامات العضو فقط (باستخدام Prepared Statements عشان الأمان)
$queries = [

    // 1) القروض الحالية
    "My Active Loans" => "SELECT 
                             b.Title, 
                             c.Barcode, 
                             l.IssueDate, 
                             l.DueDate,
                             DATEDIFF(l.DueDate, CURDATE()) AS Days_Left
                          FROM Loan l
                          JOIN Copy c ON l.CopyID = c.CopyID
                          JOIN Book b ON c.BookID = b.BookID
                          WHERE l.MemberID = ? 
                            AND l.Status = 'Active'
                          ORDER BY l.DueDate ASC",

    // 2) حجوزاتي
    "My Reservations" => "SELECT 
                             b.Title,
                             r.Status,
                             r.ReserveDate,
                             r.ExpiryDate,
                             CASE 
                                 WHEN r.Status = 'Ready' THEN 'Ready for pickup!'
                                 WHEN r.Status = 'Pending' THEN 'Waiting'
                                 ELSE r.Status 
                             END AS Status_Note
                          FROM Reservation r
                          JOIN Book b ON r.BookID = b.BookID
                          WHERE r.MemberID = ?
                          ORDER BY r.ReserveDate DESC",

    // 3) غراماتي غير المدفوعة
    "My Unpaid Fines" => "SELECT 
                             f.Amount,
                             b.Title AS Related_Book,
                             l.DueDate,
                             f.PaidDate AS Fine_Date
                          FROM Fine f
                          LEFT JOIN Loan l ON f.LoanID = l.LoanID
                          LEFT JOIN Copy c ON l.CopyID = c.CopyID
                          LEFT JOIN Book b ON c.BookID = b.BookID
                          WHERE f.MemberID = ? 
                            AND f.Status = 'Unpaid'",

    // 4) تاريخ قروضي السابقة
    "My Loan History" => "SELECT 
                             b.Title,
                             l.IssueDate,
                             l.ReturnDate,
                             IFNULL(l.FineAmount, 0) AS Fine_Paid
                          FROM Loan l
                          JOIN Copy c ON l.CopyID = c.CopyID
                          JOIN Book b ON c.BookID = b.BookID
                          WHERE l.MemberID = ? 
                            AND l.Status = 'Returned'
                          ORDER BY l.ReturnDate DESC
                          LIMIT 10"
];


$results = [];
foreach ($queries as $title => $sql) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$member_id]);
    $results[$title] = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;800&family=Playfair+Display:wght@700&display=swap');
        
        body {margin:0;padding:20px 15px;min-height:100vh;background:url('../assets/img/librarian-dashboard-img.png') center/cover fixed;font-family:'Cairo',sans-serif;color:white;}
        .container {max-width:1100px;margin:0 auto;}
        h1 {text-align:center;font-family:'Playfair Display',serif;font-size:52px;color:#FFD700;text-shadow:0 0 30px rgba(255,215,0,0.6);margin-bottom:10px;}
        .welcome {text-align:center;font-size:28px;color:#ff6b6b;margin-bottom:50px;}
        .grid {display:grid;grid-template-columns:repeat(auto-fill,minmax(480px,1fr));gap:30px;}
        .card {background:rgba(255,255,255,0.15);backdrop-filter:blur(20px);border-radius:24px;padding:32px;border:1px solid rgba(255,255,255,0.3);box-shadow:0 15px 45px rgba(0,0,0,0.7);transition:0.5s;}
        .card:hover {transform:translateY(-18px);box-shadow:0 30px 60px rgba(255,215,0,0.4);}
        .card h3 {color:#FFD700;font-size:26px;text-align:center;margin-bottom:20px;}
        .btn {width:100%;padding:18px;background:linear-gradient(135deg,#006400,#228B22);border:none;border-radius:16px;color:white;font-size:20px;font-weight:bold;cursor:pointer;transition:0.4s;}
        .btn:hover {background:linear-gradient(135deg,#008000,#32CD32);transform:scale(1.05);}
        .result {display:none;margin-top:25px;background:rgba(0,0,0,0.5);border-radius:16px;overflow:hidden;max-height:500px;overflow-y:auto;}
        table {width:100%;border-collapse:collapse;}
        th,td {padding:15px;text-align:center;border-bottom:1px solid rgba(255,255,255,0.15);}
        th {background:rgba(0,100,0,0.9);color:#FFD700;font-weight:bold;}
        tr:hover {background:rgba(255,255,255,0.12);}
        .no-data {text-align:center;padding:50px;color:#ff6b6b;font-size:22px;}
        .status-ready {color:#32CD32;font-weight:bold;}
        .status-pending {color:#FFD700;}
        .logout {display:block;width:fit-content;margin:70px auto;padding:20px 60px;background:linear-gradient(45deg,#ff1744,#c62828);color:white;text-decoration:none;border-radius:60px;font-size:24px;font-weight:bold;transition:0.5s;}
        .logout:hover {transform:scale(1.15);box-shadow:0 0 40px rgba(255,0,0,0.8);}
    </style>
</head>
<body>

<div class="container">
    <h1> my library </h1>
    <p class="welcome">welcome back  <?=htmlspecialchars($_SESSION['name'])?></p>

    <div class="grid">
        <?php foreach ($queries as $title => $sql): 
            $id = 'member-result-' . md5($title); ?>
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
                                        <?php if ($key === 'Status_Note' && $cell === 'جاهز للاستلام!'): ?>
                                            <td class="status-ready"><?=$cell?> <i class="fas fa-check-circle"></i></td>
                                        <?php elseif ($key === 'Days_Left' && $cell <= 3): ?>
                                            <td style="color:#ff4444;font-weight:bold;"><?=$cell?> days left </td>
                                        <?php else: ?>
                                            <td><?=htmlspecialchars($cell)?></td>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php else: ?>
                        <p class="no-data">no data now</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <a href="../logout.php" class="logout">logout </a>
</div>

</body>
</html>