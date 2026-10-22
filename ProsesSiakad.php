<?php

session_start();


/* =====================================================
   KONEKSI DATABASE
===================================================== */

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_siakad";

$koneksi = new mysqli(
    $host,
    $user,
    $pass,
    $db
);

if ($koneksi->connect_error) {

    die(
        "Koneksi database gagal: " .
        $koneksi->connect_error
    );
}


/* =====================================================
   LOGIN
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["aksi"] ?? "") === "login"
) {

    $bp = trim($_POST["bp"] ?? "");
    $password = $_POST["password"] ?? "";


    if ($bp === "" || $password === "") {

        echo "
        <script>
            alert('Nomor BP dan password wajib diisi!');
            window.location='TampilanSiakad.php';
        </script>
        ";

        exit;
    }


    $stmt = $koneksi->prepare(
        "SELECT bp,nama
         FROM mahasiswa
         WHERE bp = ?
         AND password = ?
         LIMIT 1"
    );


    $stmt->bind_param(
        "ss",
        $bp,
        $password
    );


    $stmt->execute();

    $hasil = $stmt->get_result();


    if ($hasil->num_rows === 1) {

        $data =
            $hasil->fetch_assoc();


        session_regenerate_id(true);


        $_SESSION["login"] =
            true;

        $_SESSION["bp"] =
            $data["bp"];

        $_SESSION["nama"] =
            $data["nama"];


        header(
            "Location: TampilanSiakad.php"
        );

        exit;

    }


    echo "
    <script>
        alert('Nomor BP atau password salah!');
        window.location='TampilanSiakad.php';
    </script>
    ";

    exit;
}


/* =====================================================
   SIMPAN KRS
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["aksi"] ?? "") === "tambah_krs"
) {


    if (
        !isset($_SESSION["login"]) ||
        $_SESSION["login"] !== true
    ) {

        header(
            "Location: TampilanSiakad.php"
        );

        exit;
    }


    $bp =
        $_SESSION["bp"];


    /* Hapus KRS lama */

    $hapus =
        $koneksi->prepare(
            "DELETE FROM krs
             WHERE bp = ?"
        );


    $hapus->bind_param(
        "s",
        $bp
    );


    $hapus->execute();


    /* Kalau tidak memilih mata kuliah */

    if (
        empty($_POST["mk"])
    ) {

        $_SESSION["pesan"] =
            "KRS dikosongkan.";

        header(
            "Location: TampilanSiakad.php?halaman=krs"
        );

        exit;
    }


    /* Masukkan KRS baru */

    $simpan =
        $koneksi->prepare(
            "INSERT INTO krs
            (bp,kode_mk,nama_mk,sks)
            VALUES
            (?,?,?,?)"
        );


    foreach (
        $_POST["mk"] as $mk
    ) {


        $data =
            explode("|", $mk);


        if (
            count($data) < 3
        ) {
            continue;
        }


        $kode =
            trim($data[0]);

        $namaMk =
            trim($data[1]);

        $sks =
            intval($data[2]);


        $simpan->bind_param(
            "sssi",
            $bp,
            $kode,
            $namaMk,
            $sks
        );


        $simpan->execute();

    }


    $_SESSION["pesan"] =
        "KRS berhasil disimpan.";


    header(
        "Location: TampilanSiakad.php?halaman=krs"
    );

    exit;
}


/* =====================================================
   PEMBAYARAN UKT
===================================================== */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["aksi"] ?? "") === "bayar_ukt"
) {


    if (
        !isset($_SESSION["login"]) ||
        $_SESSION["login"] !== true
    ) {

        header(
            "Location: TampilanSiakad.php"
        );

        exit;
    }


    $bp =
        $_SESSION["bp"];


    $stmt =
        $koneksi->prepare(
            "UPDATE ukt
             SET status = 'Sudah Bayar'
             WHERE bp = ?"
        );


    $stmt->bind_param(
        "s",
        $bp
    );


    $stmt->execute();


    $_SESSION["pesan"] =
        "Status pembayaran UKT berhasil diperbarui.";


    header(
        "Location: TampilanSiakad.php?halaman=ukt"
    );

    exit;
}


/* =====================================================
   LOGOUT
===================================================== */

if (
    ($_GET["aksi"] ?? "") === "logout"
) {

    $_SESSION = [];

    session_destroy();


    header(
        "Location: TampilanSiakad.php"
    );

    exit;
}


/* =====================================================
   DEFAULT
===================================================== */

header(
    "Location: TampilanSiakad.php"
);

exit;

?>