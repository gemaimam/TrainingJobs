<?php
session_start();
include '../config/database.php';

if ($_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['kodeujian'], $_POST['timer'], $_POST['kejuruan'])) {
        $kodeujian = $_POST['kodeujian'];
        $timer = $_POST['timer'] * 60; // Konversi ke detik
        $kejuruan = $_POST['kejuruan'];

        if (isset($_POST['id']) && !empty($_POST['id'])) {
            // Update data
            $id = $_POST['id'];
            $updateQuery = $conn->prepare("UPDATE ujian SET kodeujian = ?, timer = ?, kejuruan = ? WHERE id = ?");
            $updateQuery->bind_param('sisi', $kodeujian, $timer, $kejuruan, $id);
            if ($updateQuery->execute()) {
                header('Location: admin_setting.php');
                exit();
            } else {
                echo "Gagal memperbarui data.";
            }
        } else {
            // Tambah data
            $insertQuery = $conn->prepare("INSERT INTO ujian (kodeujian, timer, kejuruan) VALUES (?, ?, ?)");
            $insertQuery->bind_param('sis', $kodeujian, $timer, $kejuruan);
            if ($insertQuery->execute()) {
                header('Location: admin_setting.php');
                exit();
            } else {
                echo "Gagal menambahkan data.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../css/styleadmin.css">
    <script src="
    https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.all.min.js
    "></script>
    <link href="
    https://cdn.jsdelivr.net/npm/sweetalert2@11.14.5/dist/sweetalert2.min.css
    " rel="stylesheet">
    <style>
    .table-grid {
      grid-template-columns: 40px 1fr 1fr 1fr 1fr;
    }
    .action-btn{
      margin-top: 12px;
      margin-bottom: 2px;    
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
            <!-- div baru buat container di sini-->
            <div class="contain">
                <h2 style="padding-top:10px; padding-left: 10px;">Data Ujian</h2>
                <div class="action-container">
                    <button type="button" class="action-btn" onclick="tambah()">Tambah Data</button>
                    <button class="action-btn" onclick="update()">Update Data</button>
                </div>
                
                <div style="overflow-x: auto; height: auto; margin-top: 10px; font-size: 12px;">
                    <div class="table-grid">
                        <div class="table-header">No</div>
                        <div class="table-header">Kode Ujian</div>
                        <div class="table-header">Waktu (Menit)</div>
                        <div class="table-header">Kejuruan</div>
                        <div class="table-header">Aksi</div>
                        <?php
                        include '../config/database.php';
                        $ujian_data = $conn->query("SELECT * FROM ujian");
                        while ($row = $ujian_data->fetch_assoc()): ?>
                            <div class="table-row" onclick="selectRow(this, <?= $row['id'] ?>)">
                                <div class="table-cell"><?= $row['id'] ?></div>
                                <div class="table-cell"><?= htmlspecialchars($row['kodeujian']) ?></div>
                                <div class="table-cell"><?= $row['timer'] / 60 ?></div>
                                <div class="table-cell"><?= htmlspecialchars($row['kejuruan']) ?></div>
                                <div class="table-cell">
                                    <button onclick="hapusUjian(<?= $row['id'] ?>, '<?= htmlspecialchars($row['kejuruan']) ?>')" class="trashbtn"><span class="icon"><ion-icon name="trash-outline"></ion-icon></ion-icon></span></button>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

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
                    <a href="../logout.php">
                        <span class="icon"><ion-icon name="exit-outline"></ion-icon></span>
                        <span class="title">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
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
            let selectedRow = null;
    let selectedId = null;

    // Fungsi untuk menyorot baris yang diklik
    function selectRow(row, id) {
        if (selectedRow) {
            selectedRow.classList.remove('selected');
        }
        selectedRow = row;
        selectedRow.classList.add('selected');
        selectedId = id;
    }

    // Fungsi untuk mengirimkan data ke halaman edit_ujian.php
    function submitUpdate() {
        if (!selectedId) {
            alert('Pilih baris data yang ingin diupdate.');
            return;
        }
        // Pastikan ID dibawa ke form
        document.getElementById('selectedId').value = selectedId;
        document.getElementById('updateForm').submit();
    }

    document.addEventListener('click', function (e) {
        if (!e.target.closest('.table-row')) {
            // Jika klik di luar tabel, reset highlight
            if (selectedRow) {
                selectedRow.classList.remove('selected');
                selectedRow = null;
                selectedId = null;
            }
        }
    });

    function tambah() {
        Swal.fire({
            title: "<strong>Tambah Data</strong>",
            html: `
                <form id="tambahForm" method="POST" action="">
                    <div style="margin-bottom: 10px; text-align: left;">
                        <label for="kodeujian" style="display: block; font-weight: bold;">Kode Ujian</label>
                        <input type="text" id="kodeujian" name="kodeujian" style="width: 100%; padding: 8px; box-sizing: border-box;" required>
                    </div>
                    <div style="margin-bottom: 10px; text-align: left;">
                        <label for="timer" style="display: block; font-weight: bold;">Timer (menit)</label>
                        <input type="number" id="timer" name="timer" style="width: 100%; padding: 8px; box-sizing: border-box;" required>
                    </div>
                    <div style="margin-bottom: 10px; text-align: left;">
                        <label for="kejuruan" style="display: block; font-weight: bold;">Kejuruan</label>
                        <input type="text" id="kejuruan" name="kejuruan" style="width: 100%; padding: 8px; box-sizing: border-box;" required>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: "Tambah",
            preConfirm: () => {
                document.getElementById('tambahForm').submit();
            }
        });
    }


    function update() {
        if (!selectedId) {
            Swal.fire({
                icon: 'warning',
                title: 'Pilih Data!',
                text: 'Silakan pilih data yang ingin diubah.',
            });
            return;
        }

        const selectedRow = document.querySelector('.selected');
        const kodeujian = selectedRow.querySelectorAll('.table-cell')[1].innerText;
        const timer = selectedRow.querySelectorAll('.table-cell')[2].innerText;
        const kejuruan = selectedRow.querySelectorAll('.table-cell')[3].innerText;

        Swal.fire({
            title: "<strong>Edit Data Ujian</strong>",
            html: `
                <form id="updateForm" method="POST" action="">
                    <input type="hidden" name="id" value="${selectedId}">
                    <div style="margin-bottom: 10px; text-align: left;">
                        <label for="kodeujian" style="display: block; font-weight: bold;">Kode Ujian</label>
                        <input type="text" id="kodeujian" name="kodeujian" value="${kodeujian}" style="width: 100%; padding: 8px; box-sizing: border-box;" required>
                    </div>
                    <div style="margin-bottom: 10px; text-align: left;">
                        <label for="timer" style="display: block; font-weight: bold;">Timer (menit)</label>
                        <input type="number" id="timer" name="timer" value="${timer}" style="width: 100%; padding: 8px; box-sizing: border-box;" required>
                    </div>
                    <div style="margin-bottom: 10px; text-align: left;">
                        <label for="kejuruan" style="display: block; font-weight: bold;">Kejuruan</label>
                        <input type="text" id="kejuruan" name="kejuruan" value="${kejuruan}" style="width: 100%; padding: 8px; box-sizing: border-box;" required>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: "Simpan",
            preConfirm: () => {
                document.getElementById('updateForm').submit();
            }
        });
    }

    function hapusUjian(id, kejuruan) {
    Swal.fire({
        title: `Hapus Data Ujian`,
        html: `Apakah Anda yakin ingin menghapus ujian untuk kejuruan <b>${kejuruan}</b>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Kirimkan permintaan ke server untuk menghapus data
            fetch(`delete_ujian.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Berhasil!',
                            text: 'Data ujian berhasil dihapus.',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Gagal!',
                            text: data.message || 'Terjadi kesalahan.',
                        });
                    }
                })
                .catch(error => {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Tidak dapat menghapus data.',
                    });
                });
        }
    });
}

    </script>
</body>
</html>
