<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../loginpage.php");
    exit;
}
require_once '../db_connect.php';

// الـ 11 query اللي بعتهم بالظبط (مظبوطين ومختبرين)
$queries = [
    "All Active Reservations" => "SELECT ReservationID, BookID, MemberID, Status, ExpiryDate 
                                  FROM Reservation 
                                  WHERE Status IN ('Pending', 'Ready')",

    "Current Reservations with Names" => "SELECT b.Title, m.FullName, r.Status, r.ReserveDate AS ReserveDate
                                          FROM Reservation r
                                          JOIN Book b ON r.BookID = b.BookID
                                          JOIN Member m ON r.MemberID = m.MemberID",

    "Number of Copies per Book" => "SELECT b.Title, COUNT(c.CopyID) AS TotalCopies
                                    FROM Book b
                                    LEFT JOIN Copy c ON b.BookID = c.BookID
                                    GROUP BY b.BookID, b.Title",

    "Most Borrowed Books" => "SELECT b.Title, COUNT(*) AS Times_Loaned
                              FROM Loan l
                              JOIN Copy c ON l.CopyID = c.CopyID
                              JOIN Book b ON c.BookID = b.BookID
                              GROUP BY b.Title
                              ORDER BY Times_Loaned DESC",

    "Books Written by Each Author" => "SELECT a.AuthorID, 
                                       CONCAT(a.FirstName,' ', a.LastName) AS Author_Name,
                                       COUNT(ba.BookID) AS Total_Books
                                       FROM Author a
                                       JOIN BookAuthor ba ON a.AuthorID = ba.AuthorID
                                       GROUP BY a.AuthorID",

    "Books by Category & Publisher" => "SELECT b.Title, 
                                        IFNULL(cat.Name, 'No Category') AS Category,
                                        IFNULL(pub.Name, 'No Publisher') AS Publisher
                                        FROM Book b
                                        LEFT JOIN Category cat ON b.CategoryID = cat.CategoryID
                                        LEFT JOIN Publisher pub ON b.PublisherID = pub.PublisherID",

    "All Available Copies" => "SELECT c.CopyID, b.Title, c.Barcode, c.Status
                               FROM Copy c
                               JOIN Book b ON c.BookID = b.BookID
                               WHERE c.Status = 'Available'",

    "Staff Who Issued Most Loans" => "SELECT s.FullName, COUNT(*) AS IssuedLoans
                                      FROM Loan l
                                      JOIN Staff s ON l.StaffID_Issued = s.StaffID
                                      GROUP BY s.StaffID, s.FullName
                                      ORDER BY IssuedLoans DESC",

    "All Active Loans" => "SELECT l.LoanID, m.FullName, c.Barcode, b.Title, l.IssueDate, l.DueDate
                           FROM Loan l
                           JOIN Member m ON l.MemberID = m.MemberID
                           JOIN Copy c ON l.CopyID = c.CopyID
                           JOIN Book b ON c.BookID = b.BookID
                           WHERE l.Status = 'Active'",

    "Members with Unpaid Fines" => "SELECT m.FullName, f.Amount, f.Status
                                    FROM Fine f
                                    JOIN Member m ON f.MemberID = m.MemberID
                                    WHERE f.Status = 'Unpaid'",

    "General Library Statistics" => "SELECT 
        (SELECT COUNT(*) FROM Book) AS Total_Books,
        (SELECT COUNT(*) FROM Member) AS Total_Members,
        (SELECT COUNT(*) FROM Loan WHERE Status='Active') AS Active_Loans,
        (SELECT COUNT(*) FROM Copy WHERE Status='Available') AS Available_Copies,
        (SELECT SUM(Amount) FROM Fine WHERE Status='Unpaid') AS Total_Unpaid_Fines"
];

$results = [];
foreach ($queries as $title => $sql) {
    try {
        $stmt = $pdo->query($sql);
        $results[$title] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $results[$title] = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Control Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;700&family=Cairo:wght@400;600;800&display=swap');
        
        * { margin:0; padding:0; box-sizing:border-box; }
        body {
            min-height: 100vh;
            background: url('../assets/img/librarian-dashboard-img.png') center/cover fixed;
            font-family: 'Cairo', sans-serif;
            color: white;
            padding: 30px 15px;
        }
        .container { max-width: 1400px; margin: 0 auto; }
        h1 {
            text-align: center;
            font-family: 'Playfair Display', serif;
            font-size: 52px;
            color: #FFD700;
            text-shadow: 0 0 25px rgba(255,215,0,0.6);
            margin-bottom: 10px;
        }
        .welcome {
            text-align: center;
            font-size: 26px;
            color: #ff6b6b;
            margin-bottom: 50px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            gap: 28px;
        }
        .card {
            background: rgba(255,255,255,0.14);
            backdrop-filter: blur(18px);
            border-radius: 22px;
            padding: 28px;
            border: 1px solid rgba(255,255,255,0.25);
            box-shadow: 0 15px 40px rgba(0,0,0,0.6);
            transition: all 0.4s ease;
        }
        .card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 50px rgba(255,215,0,0.35);
        }
        .card h3 {
            color: #FFD700;
            font-size: 24px;
            text-align: center;
            margin-bottom: 18px;
            font-weight: 800;
        }
        .btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #8B4513, #D2691E);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 19px;
            font-weight: bold;
            cursor: pointer;
            transition: 0.4s;
            margin-top: 10px;
        }
        .btn:hover {
            background: linear-gradient(135deg, #A0522D, #CD853F);
            transform: translateY(-4px);
        }
        .result {
            display: none;
            margin-top: 22px;
            background: rgba(0,0,0,0.45);
            border-radius: 14px;
            overflow: hidden;
            max-height: 400px;
            overflow-y: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 14px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.15);
        }
        th {
            background: rgba(139,69,19,0.8);
            color: #FFD700;
            font-weight: bold;
        }
        tr:hover {
            background: rgba(255,255,255,0.12);
        }
        .no-data {
            text-align: center;
            padding: 30px;
            color: #ff6b6b;
            font-size: 18px;
        }
        .logout {
            display: block;
            width: fit-content;
            margin: 60px auto;
            padding: 18px 50px;
            background: linear-gradient(45deg, #ff1744, #d50000);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-size: 22px;
            font-weight: bold;
            transition: 0.4s;
        }
        .logout:hover {
            transform: scale(1.12);
            box-shadow: 0 0 30px rgba(255,0,0,0.7);
        }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(0,0,0,0.3); border-radius: 10px; }
        ::-webkit-scrollbar-thumb { background: #D2691E; border-radius: 10px; }
    </style>
</head>
<body>

<div class="container">
    <h1>Library Control Panel</h1>
    <p class="welcome">Welcome back, <?=htmlspecialchars($_SESSION['name'])?> • Full Access</p>

    <div class="grid">
        <?php foreach ($queries as $title => $sql): ?>
            <?php $id = 'result-' . md5($title); ?>
            <div class="card">
                <h3><?=$title?></h3>
                <button class="btn" onclick="document.getElementById('<?=$id?>').style.display = document.getElementById('<?=$id?>').style.display === 'block' ? 'none' : 'block'">
                   see result 
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