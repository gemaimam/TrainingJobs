<?php
session_start();
include '../config/database.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

$orderBy = isset($_GET['order_by']) ? $_GET['order_by'] : 'score_desc';
$filterKejuruan = isset($_GET['kejuruan']) ? $_GET['kejuruan'] : ''; 
$sql = "
    SELECT 
        ua.user_name, 
        u.kejuruan, 
        COUNT(DISTINCT ua.question_id) AS total_answered, 
        SUM(CASE WHEN c.is_correct = 1 THEN 1 ELSE 0 END) AS total_correct,
        (SELECT COUNT(*) FROM questions WHERE ujian_id = u.id) AS total_questions,
        (SUM(CASE WHEN c.is_correct = 1 THEN 1 ELSE 0 END) / (SELECT COUNT(*) FROM questions WHERE ujian_id = u.id)) * 100 AS score,
        ua.tanggal_pengerjaan 
    FROM user_answers ua
    JOIN questions q ON ua.question_id = q.id
    JOIN ujian u ON q.ujian_id = u.id
    JOIN choices c ON ua.answer = c.id
";

if ($filterKejuruan) {
    $sql .= " WHERE u.kejuruan = '$filterKejuruan'";
}

$sql .= " GROUP BY ua.user_name, u.kejuruan, ua.tanggal_pengerjaan"; 

switch ($orderBy) {
    case 'name_asc':
        $sql .= " ORDER BY ua.user_name ASC, score DESC"; 
        break;
    case 'name_desc':
        $sql .= " ORDER BY ua.user_name DESC, score DESC";
        break;
    case 'score_asc':
        $sql .= " ORDER BY score ASC, ua.user_name ASC"; 
        break;
    case 'score_desc':
    default:
        $sql .= " ORDER BY score DESC, ua.user_name ASC";
        break;
}


$users = $conn->query($sql);
$kejuruanOptions = $conn->query("SELECT DISTINCT kejuruan FROM ujian");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hasil Jawaban Semua User</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/styleadmin.css">
    <script src="
    https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js
    "></script>
    <link href="
    https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.min.css
    " rel="stylesheet">
    <style>
        a {
            text-decoration: none;
            color: black;   
        }
        .table-grid {
            grid-template-columns: 40px 1fr 1fr 1fr 1fr 1fr;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="main">
            <div class="topbar">
                <div class="toggle">
                    <ion-icon name="menu-outline"></ion-icon>
                </div>
            </div>

            <div class="contain">
                <h2 style="padding-left:10px; padding-top:10px;">Hasil Jawaban User</h2>
                <div class="action-container">
                <button id="export-excel-btn" class="action-btn">Export Excel</button>
                <button id="delete-results-btn" class="action-btn">Hapus Hasil</button>
                <form method="GET" action="view_results.php">
                    <label for="kejuruan" style="padding-left:10px;">Filter Kejuruan: </label>
                    <select name="kejuruan" id="kejuruan" onchange="this.form.submit()" class="filter_kejuruan">
                        <option value="">Semua Kejuruan</option>
                        <?php while ($row = $kejuruanOptions->fetch_assoc()): ?>
                            <option value="<?= htmlspecialchars($row['kejuruan']) ?>" 
                                <?= $filterKejuruan == $row['kejuruan'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($row['kejuruan']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </form>
                
                </div>
                
                <div style="overflow-x:auto; margin-top:10px; height:auto; font-size: 12px;">
                    <div class="table-grid">
                        <div class="table-header">No</div>
                        <div class="table-header sortable-header" id="sort-name">
                            Nama
                            <?php if ($orderBy == 'name_asc'): ?>
                                <span class="sort-icon">&#9650;</span> 
                            <?php elseif ($orderBy == 'name_desc'): ?>
                                <span class="sort-icon">&#9660;</span> <!-- Segitiga bawah -->
                            <?php endif; ?>
                        </div>
                        <div class="table-header">
                            Kejuruan
                        </div>
                        <div class="table-header">
                            Tanggal / Jam
                        </div>
                        <div class="table-header sortable-header" id="sort-score">
                            Nilai
                            <?php if ($orderBy == 'score_asc'): ?>
                                <span class="sort-icon">&#9650;</span>
                            <?php elseif ($orderBy == 'score_desc'): ?>
                                <span class="sort-icon">&#9660;</span>
                            <?php endif; ?>
                        </div>
                        <div class="table-header">Aksi</div>
                        <?php
                            $no = 1;
                            if ($users->num_rows > 0) {
                                while ($user = $users->fetch_assoc()) {
                                    $score = ($user['total_correct'] / $user['total_questions']) * 100;
                                    
                                    // Mengonversi tanggal pengerjaan ke WIB
                                    $tanggalPengerjaan = new DateTime($user['tanggal_pengerjaan']);
                                    $tanggalPengerjaan->setTimezone(new DateTimeZone('Asia/Jakarta')); // Menetapkan zona waktu Indonesia (WIB)
                                    $tanggalPengerjaanFormatted = $tanggalPengerjaan->format('Y-m-d H:i:s'); // Format ke 'YYYY-MM-DD HH:MM:SS'

                                    echo "<div class='table-cell'>". $no++ ."</div>";
                                    echo "<div class='table-cell'><a href='user_detail.php?user_name=" . urlencode($user['user_name']) . "'>" . htmlspecialchars($user['user_name']) . "</a></div>";
                                    echo "<div class='table-cell'>" . htmlspecialchars($user['kejuruan']) . "</div>"; 
                                    echo "<div class='table-cell'>" . htmlspecialchars($tanggalPengerjaanFormatted) . "</div>"; // Tampilkan waktu WIB
                                    echo "<div class='table-cell'>" . number_format($score, 2) . "</div>"; 
                                    echo "<div class='table-cell'> <button onclick=\"deleteUser('" . htmlspecialchars($user['user_name']) . "')\" class='trashbtn'><span class='icon'><ion-icon name='trash-outline'></ion-icon></ion-icon></span></button> </div>";
                                }
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Navigasi -->
        <div class="navigation">
            <ul>
                <li>
                    <a href="#">
                    <span class="icon"><img src="../img/logo-karawang.png" alt=""></span>
                    <span class="title" style="font-size:20px; margin-top:10px;">DISNAKERTRANS</span>
                    </a>
                </li>
                <li>
                    <a href="admin_dashboard.php">
                        <span class="icon"><ion-icon name="bar-chart-outline"></ion-icon></span>
                        <span class="title">Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="add_question.php">
                        <span class="icon"><ion-icon name="browsers-outline"></ion-icon></span>
                        <span class="title">Kumpulan Soal</span>
                    </a>
                </li>
                <li>
                    <a href="view_results.php">
                        <span class="icon"><ion-icon name="newspaper-outline"></ion-icon></span>
                        <span class="title">Hasil Jawaban</span>
                    </a>
                </li>
                <li>        
                    <a href="admin_setting.php">
                        <span class="icon"><ion-icon name="settings-outline"></ion-icon></span>
                        <span class="title">Setting Ujian</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <span class="icon"><ion-icon name="exit-outline"></ion-icon></span>
                        <span class="title">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
    <script>
    document.getElementById('export-excel-btn').addEventListener('click', function () {
        Swal.fire({
            title: 'Apakah Anda yakin ingin mengekspor data ke Excel?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, ekspor!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                const kejuruan = document.getElementById('kejuruan').value;
                let url = 'export_excel.php';
                if (kejuruan) {
                    url += `?kejuruan=${encodeURIComponent(kejuruan)}`;
                }
                window.location.href = url;
            }
        });
    });

    document.getElementById('delete-results-btn').addEventListener('click', function () {
        Swal.fire({
            title: 'Apakah Anda yakin ingin menghapus semua hasil jawaban?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'delete_results.php';
            }
        });
    });
    </script>
    <script>
    function deleteUser(userName) {
        Swal.fire({
            title: `Apakah Anda yakin ingin menghapus hasil jawaban dari ${userName}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Ya, hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `delete_user.php?user_name=${encodeURIComponent(userName)}`;
            }
        });
    }
    </script>
    <script src="../js/main.js"></script>
    <script
      type="module"
      src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"
    ></script>
    <script
      nomodule
      src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"
    ></script>
    <script>
       document.getElementById('sort-name').addEventListener('click', function() {
            let order = '<?= $orderBy ?>' === 'name_asc' ? 'name_desc' : 'name_asc';
            window.location.href = `?order_by=${order}&kejuruan=<?= $filterKejuruan ?>`;
        });

        document.getElementById('sort-score').addEventListener('click', function() {
            let order = '<?= $orderBy ?>' === 'score_asc' ? 'score_desc' : 'score_asc';
            window.location.href = `?order_by=${order}&kejuruan=<?= $filterKejuruan ?>`;
        });
    </script>
</body>
</html>
