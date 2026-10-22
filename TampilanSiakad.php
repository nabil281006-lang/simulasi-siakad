<?php
session_start();

/* =========================================================
   SIAKAD UTD - TAMPILAN LENGKAP
   FILE: TampilanSiakad.php
   CATATAN:
   - File ini hanya mengatur tampilan dan pembacaan data.
   - ProsesSiakad.php TIDAK perlu diubah.
   - Action lama tetap dipertahankan: login, tambah_krs,
     bayar_ukt, dan logout.
   ========================================================= */

$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_siakad";

$koneksi = new mysqli($host, $user, $pass, $db);

if ($koneksi->connect_error) {
    die("Koneksi database gagal: " . $koneksi->connect_error);
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function rupiah($value) {
    return 'Rp ' . number_format((int)$value, 0, ',', '.');
}

function initials($name) {
    $name = trim((string)$name);
    if ($name === '') return 'M';
    $parts = preg_split('/\s+/', $name);
    $out = '';
    foreach ($parts as $p) {
        if ($p !== '') $out .= strtoupper(substr($p, 0, 1));
        if (strlen($out) >= 2) break;
    }
    return $out ?: 'M';
}

$sudahLogin = isset($_SESSION['login']) && $_SESSION['login'] === true;
$mahasiswa = null;
$krs = [];
$ukt = null;
$pesan = '';

if ($sudahLogin) {
    $bpLogin = $_SESSION['bp'];

    $stmt = $koneksi->prepare("SELECT * FROM mahasiswa WHERE bp = ? LIMIT 1");
    $stmt->bind_param("s", $bpLogin);
    $stmt->execute();
    $result = $stmt->get_result();
    $mahasiswa = $result->fetch_assoc();

    if (!$mahasiswa) {
        $_SESSION = [];
        session_destroy();
        header('Location: TampilanSiakad.php');
        exit;
    }

    $stmt = $koneksi->prepare("SELECT kode_mk,nama_mk,sks FROM krs WHERE bp = ? ORDER BY id ASC");
    $stmt->bind_param("s", $bpLogin);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $krs[] = $row;
    }

    $stmt = $koneksi->prepare("SELECT jumlah,status FROM ukt WHERE bp = ? LIMIT 1");
    $stmt->bind_param("s", $bpLogin);
    $stmt->execute();
    $result = $stmt->get_result();
    $ukt = $result->fetch_assoc();

    if (isset($_SESSION['pesan'])) {
        $pesan = $_SESSION['pesan'];
        unset($_SESSION['pesan']);
    }
}

$catalogKrs = [
    '20260001'=>[['IF101','Pemrograman Web',3],['IF102','Basis Data',3],['IF103','Jaringan Komputer',3],['IF104','Pemrograman Java',3],['IF105','Kecerdasan Buatan',3],['IF106','Rekayasa Perangkat Lunak',3]],
    '20260002'=>[['SI201','Analisis Sistem',3],['SI202','Sistem Informasi',3],['SI203','Manajemen Data',3],['SI204','UI UX Design',3],['SI205','Manajemen Proyek',3],['SI206','Audit Sistem Informasi',3]],
    '20260003'=>[['TK301','Arsitektur Komputer',3],['TK302','Mikroprosesor',3],['TK303','Jaringan Lanjut',3],['TK304','Sistem Digital',3],['TK305','Embedded System',3],['TK306','Teknologi Perangkat Keras',3]],
    '20260004'=>[['RPL401','Rekayasa Perangkat Lunak',3],['RPL402','Pemrograman Mobile',3],['RPL403','Testing Software',3],['RPL404','Manajemen Proyek',3],['RPL405','DevOps',3],['RPL406','Software Architecture',3]],
    '20260005'=>[['MI501','Sistem Informasi Manajemen',3],['MI502','Pemrograman Database',3],['MI503','Administrasi Sistem',3],['MI504','Aplikasi Bisnis',3],['MI505','Analisis Bisnis',3],['MI506','Sistem Enterprise',3]],
    '20260006'=>[['TE601','Elektronika Dasar',3],['TE602','Sistem Kendali',3],['TE603','Rangkaian Digital',3],['TE604','Robotika',3],['TE605','Mikrokontroler',3],['TE606','Sistem Embedded',3]],
    '20260007'=>[['AK701','Akuntansi Keuangan',3],['AK702','Audit Sistem',3],['AK703','Perpajakan',3],['AK704','Akuntansi Manajemen',3],['AK705','Sistem Informasi Akuntansi',3],['AK706','Penganggaran',3]],
    '20260008'=>[['MJ801','Manajemen SDM',3],['MJ802','Manajemen Strategis',3],['MJ803','Kewirausahaan',3],['MJ804','Perilaku Organisasi',3],['MJ805','Manajemen Operasional',3],['MJ806','Manajemen Pemasaran',3]],
    '20260009'=>[['BD901','Digital Marketing',3],['BD902','E-Commerce',3],['BD903','Bisnis Online',3],['BD904','Analitik Bisnis',3],['BD905','Strategi Digital',3],['BD906','Content Marketing',3]],
    '20260010'=>[['SD101','Statistika Data',3],['SD102','Python Data Science',3],['SD103','Machine Learning',3],['SD104','Visualisasi Data',3],['SD105','Data Mining',3],['SD106','Big Data',3]],
    '20260011'=>[['DKV111','Desain Grafis',3],['DKV112','Fotografi Digital',3],['DKV113','Animasi Dasar',3],['DKV114','Branding Design',3],['DKV115','Ilustrasi Digital',3],['DKV116','Desain Media',3]],
    '20260012'=>[['IF121','Algoritma Lanjut',3],['IF122','Pemrograman Mobile',3],['IF123','Cloud Computing',3],['IF124','Keamanan Sistem',3],['IF125','Pemrograman Lanjut',3],['IF126','Internet of Things',3]],
    '20260013'=>[['TI131','Teknologi Cloud',3],['TI132','Internet of Things',3],['TI133','Keamanan Jaringan',3],['TI134','Administrasi Server',3],['TI135','Sistem Terdistribusi',3],['TI136','Infrastruktur TI',3]],
    '20260014'=>[['KA141','Akuntansi Komputer',3],['KA142','Database Akuntansi',3],['KA143','Audit Komputer',3],['KA144','Sistem Keuangan',3],['KA145','Aplikasi Akuntansi',3],['KA146','Sistem Informasi Keuangan',3]],
    '20260015'=>[['MN151','Manajemen Bisnis',3],['MN152','Strategi Bisnis',3],['MN153','Bisnis Internasional',3],['MN154','Kewirausahaan',3],['MN155','Manajemen Keuangan',3],['MN156','Perencanaan Bisnis',3]]
];

$dosenList = [
    'Dr. Andi Pratama, M.Kom.',
    'Rina S.Kom., M.Kom.',
    'Budi S.Kom., M.Kom.',
    'Siti S.Kom., M.Kom.',
    'Dr. Fajar Rahman, M.Kom.',
    'Dewi M.Kom.',
    'Dr. Rahman, M.Kom.',
    'Dr. Sari, M.M.',
    'Dr. Dedi, M.Kom.'
];

$hariList = ['Senin','Selasa','Rabu','Kamis','Jumat'];
$jamList = ['08:00 - 09:40','10:00 - 11:40','13:00 - 14:40','15:00 - 16:40'];
$ruangList = ['Ruang 201','Ruang 202','Ruang 203','Ruang 204','Lab Komputer 1'];

$nilaiMap = [
    '20260001'=>['A','A-','B+','A'], '20260002'=>['A-','B+','A','B+'], '20260003'=>['B+','B','A-','B+'],
    '20260004'=>['A','A-','A','B+'], '20260005'=>['B+','B+','A-','B'], '20260006'=>['A-','A','B+','A'],
    '20260007'=>['B','B+','B','B+'], '20260008'=>['A-','B+','B+','A-'], '20260009'=>['A','A-','B+','A'],
    '20260010'=>['A','A','A-','A'], '20260011'=>['A-','A','B+','A'], '20260012'=>['A','A','A-','A'],
    '20260013'=>['B+','A-','B+','A-'], '20260014'=>['B+','B+','B','A-'], '20260015'=>['A','A','A-','A']
];

$absensiMap = [
    '20260001'=>[95,92,100,90], '20260002'=>[92,88,95,90], '20260003'=>[90,92,88,95], '20260004'=>[100,95,92,96],
    '20260005'=>[90,88,92,90], '20260006'=>[95,96,94,92], '20260007'=>[88,90,86,92], '20260008'=>[94,90,92,95],
    '20260009'=>[96,94,98,92], '20260010'=>[100,98,95,100], '20260011'=>[95,92,96,94], '20260012'=>[100,95,97,96],
    '20260013'=>[92,90,95,93], '20260014'=>[90,94,92,95], '20260015'=>[100,96,98,100]
];

$bobotNilai = ['A'=>4.00,'A-'=>3.70,'B+'=>3.30,'B'=>3.00,'B-'=>2.70,'C+'=>2.30,'C'=>2.00];
$daftarNilai = [];
$totalSks = 0;
$totalMutu = 0;
$ipSemester = 0;

if ($sudahLogin && $mahasiswa) {
    $bp = $mahasiswa['bp'];
    $nilaiBp = $nilaiMap[$bp] ?? [];
    $absenBp = $absensiMap[$bp] ?? [];
    foreach ($krs as $i=>$mk) {
        $nilai = $nilaiBp[$i] ?? 'B';
        $absen = $absenBp[$i] ?? 90;
        $bobot = $bobotNilai[$nilai] ?? 0;
        $sks = (int)$mk['sks'];
        $totalSks += $sks;
        $totalMutu += $sks * $bobot;
        $daftarNilai[] = ['kode'=>$mk['kode_mk'],'nama'=>$mk['nama_mk'],'sks'=>$sks,'nilai'=>$nilai,'bobot'=>$bobot,'absen'=>$absen];
    }
    if ($totalSks > 0) $ipSemester = $totalMutu / $totalSks;
}

$halamanDiizinkan = ['dashboard','kalender','kurikulum','jadwal','krs','khs','absensi','transkrip','uts','uas','ujian','ukt','pengumuman','profil','dokumen','bantuan','pengaturan'];
$halaman = $_GET['halaman'] ?? 'dashboard';
if (!in_array($halaman, $halamanDiizinkan, true)) $halaman = 'dashboard';

$catalogMahasiswa = ($sudahLogin && $mahasiswa) ? ($catalogKrs[$mahasiswa['bp']] ?? []) : [];
$krsAktif = [];
foreach ($krs as $item) $krsAktif[$item['kode_mk']] = true;

function jadwalData($krs, $dosenList, $hariList, $jamList, $ruangList) {
    $rows = [];
    foreach ($krs as $i=>$mk) {
        $rows[] = [
            'no'=>$i+1,
            'hari'=>$hariList[$i % count($hariList)],
            'jam'=>$jamList[$i % count($jamList)],
            'kode'=>$mk['kode_mk'],
            'nama'=>$mk['nama_mk'],
            'dosen'=>$dosenList[$i % count($dosenList)],
            'ruang'=>$ruangList[$i % count($ruangList)]
        ];
    }
    return $rows;
}

$jadwalRows = jadwalData($krs, $dosenList, $hariList, $jamList, $ruangList);
$namaLengkap = $mahasiswa['nama'] ?? '';
$avatar = initials($namaLengkap);
$uktStatus = $ukt['status'] ?? 'Belum Bayar';
$uktJumlah = $ukt['jumlah'] ?? 0;

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#03122f">
<title>SIAKAD UTD - Sistem Informasi Akademik</title>
<style>
:root{
 --bg:#020817;
 --bg2:#07142d;
 --panel:rgba(5,18,45,.92);
 --panel2:rgba(8,24,58,.86);
 --line:rgba(70,200,255,.18);
 --line2:rgba(70,200,255,.08);
 --cyan:#00eaff;
 --cyan2:#64f3ff;
 --blue:#2d7bff;
 --text:#ecfbff;
 --muted:#83a8bc;
 --muted2:#53758a;
 --success:#60ffa2;
 --warning:#ffd56a;
 --danger:#ff7777;
 --shadow:0 18px 50px rgba(0,0,0,.28);
 --radius:18px;
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{font-family:Inter,Segoe UI,Arial,sans-serif;background:radial-gradient(circle at 75% -10%,rgba(0,234,255,.12),transparent 32%),radial-gradient(circle at 10% 100%,rgba(45,123,255,.12),transparent 34%),var(--bg);color:var(--text);min-height:100vh;line-height:1.5}
button,input,select,textarea{font:inherit}
a{text-decoration:none;color:inherit}
button{border:0}
.hidden{display:none!important}
.no-scroll{overflow:hidden}
.muted{color:var(--muted)}
.cyan{color:var(--cyan)!important}
.success{color:var(--success)!important}
.warning{color:var(--warning)!important}
.danger{color:var(--danger)!important}
.small{font-size:12px}
.tiny{font-size:10px}
.bold{font-weight:700}
.text-center{text-align:center}
.text-right{text-align:right}
.mt-1{margin-top:5px}.mt-2{margin-top:10px}.mt-3{margin-top:15px}.mt-4{margin-top:20px}.mt-5{margin-top:25px}
.mb-1{margin-bottom:5px}.mb-2{margin-bottom:10px}.mb-3{margin-bottom:15px}.mb-4{margin-bottom:20px}.mb-5{margin-bottom:25px}
.grid{display:grid;gap:18px}.g1{grid-template-columns:1fr}.g2{grid-template-columns:repeat(2,minmax(0,1fr))}.g3{grid-template-columns:repeat(3,minmax(0,1fr))}.g4{grid-template-columns:repeat(4,minmax(0,1fr))}.g5{grid-template-columns:repeat(5,minmax(0,1fr))}
.flex{display:flex}.center{display:flex;align-items:center;justify-content:center}.between{display:flex;align-items:center;justify-content:space-between}.gap1{gap:6px}.gap2{gap:10px}.gap3{gap:14px}.gap4{gap:18px}.gap5{gap:24px}
.w100{width:100%}.h100{height:100%}
.card{background:linear-gradient(145deg,rgba(7,22,52,.96),rgba(3,13,33,.93));border:1px solid var(--line);border-radius:var(--radius);box-shadow:var(--shadow);padding:20px;position:relative;overflow:hidden}
.card:before{content:"";position:absolute;inset:0;background:linear-gradient(120deg,rgba(0,234,255,.04),transparent 30%,transparent 70%,rgba(45,123,255,.04));pointer-events:none}
.card-title{font-size:17px;font-weight:800;color:var(--cyan);margin-bottom:14px;position:relative}
.card-subtitle{font-size:12px;color:var(--muted);margin-top:-8px;margin-bottom:16px;position:relative}
.section-head{display:flex;align-items:flex-end;justify-content:space-between;gap:15px;margin-bottom:20px}.section-head h1{font-size:27px;letter-spacing:-.5px}.section-head p{color:var(--muted);font-size:13px;margin-top:4px}.section-actions{display:flex;gap:8px;flex-wrap:wrap}
.btn{padding:10px 14px;border-radius:11px;cursor:pointer;font-weight:700;font-size:12px;transition:.2s;display:inline-flex;align-items:center;gap:7px;justify-content:center}.btn:hover{transform:translateY(-1px)}.btn-primary{background:linear-gradient(135deg,var(--cyan),#7cf8ff);color:#00131c;box-shadow:0 8px 25px rgba(0,234,255,.15)}.btn-secondary{background:rgba(0,234,255,.07);color:#bceefa;border:1px solid var(--line)}.btn-danger{background:rgba(255,119,119,.08);color:#ffadad;border:1px solid rgba(255,119,119,.18)}
.badge{display:inline-flex;align-items:center;gap:6px;padding:5px 9px;border-radius:999px;font-size:10px;font-weight:800}.badge-success{background:rgba(96,255,162,.08);color:var(--success);border:1px solid rgba(96,255,162,.16)}.badge-warning{background:rgba(255,213,106,.08);color:var(--warning);border:1px solid rgba(255,213,106,.16)}.badge-info{background:rgba(0,234,255,.08);color:var(--cyan2);border:1px solid var(--line)}.badge-danger{background:rgba(255,119,119,.08);color:#ffaaaA;border:1px solid rgba(255,119,119,.16)}

/* LOGIN */
.login-screen{min-height:100vh;display:grid;place-items:center;padding:25px;position:relative;overflow:hidden}.login-orb{position:absolute;width:380px;height:380px;border-radius:50%;filter:blur(8px);opacity:.22;pointer-events:none}.orb1{right:-100px;top:-130px;background:radial-gradient(circle,var(--cyan),transparent 62%)}.orb2{left:-120px;bottom:-150px;background:radial-gradient(circle,var(--blue),transparent 62%)}
.login-card{width:min(100%,1060px);display:grid;grid-template-columns:1.05fr .95fr;background:rgba(4,17,40,.94);border:1px solid var(--line);border-radius:28px;box-shadow:0 30px 100px rgba(0,0,0,.45);overflow:hidden;position:relative}.login-left{padding:46px;background:linear-gradient(140deg,rgba(0,234,255,.06),rgba(45,123,255,.03));border-right:1px solid var(--line)}.login-right{padding:46px}
.brand-mark{display:flex;align-items:center;gap:14px}.brand-circle{width:58px;height:58px;border-radius:18px;display:grid;place-items:center;border:1px solid var(--cyan);color:var(--cyan);font-weight:900;box-shadow:0 0 30px rgba(0,234,255,.18);background:rgba(0,234,255,.04)}.brand-name{font-size:18px;font-weight:900}.brand-caption{font-size:11px;color:var(--muted)}.login-headline{font-size:42px;line-height:1.05;margin-top:46px;letter-spacing:-1.6px}.login-headline span{color:var(--cyan);text-shadow:0 0 22px rgba(0,234,255,.18)}.login-copy{color:var(--muted);font-size:13px;max-width:470px;margin-top:16px}.feature-list{margin-top:28px;display:grid;gap:10px}.feature-item{display:flex;gap:10px;align-items:flex-start;padding:11px 12px;border:1px solid var(--line2);border-radius:12px;background:rgba(255,255,255,.015)}.feature-dot{width:8px;height:8px;border-radius:50%;background:var(--cyan);box-shadow:0 0 12px rgba(0,234,255,.7);margin-top:6px;flex:none}.feature-item strong{font-size:12px}.feature-item span{font-size:11px;color:var(--muted);display:block;margin-top:2px}
.login-form-title{font-size:25px;font-weight:900}.login-form-sub{color:var(--muted);font-size:12px;margin-top:6px;margin-bottom:26px}.field{margin-bottom:16px}.field label{display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;font-size:11px;color:#bde8f6;font-weight:700}.field input,.field select,.field textarea{width:100%;background:rgba(1,7,20,.8);border:1px solid var(--line);color:#eefcff;border-radius:12px;padding:12px 13px;outline:none;transition:.2s}.field input:focus,.field select:focus,.field textarea:focus{border-color:var(--cyan);box-shadow:0 0 0 3px rgba(0,234,255,.05)}.password-wrap{position:relative}.password-wrap input{padding-right:45px}.password-toggle{position:absolute;right:12px;top:50%;transform:translateY(-50%);cursor:pointer;color:#6eddeb;font-size:16px}.login-submit{width:100%;padding:13px;border-radius:12px;background:linear-gradient(135deg,var(--cyan),#9afaff);font-weight:900;color:#00151d;cursor:pointer;margin-top:6px;box-shadow:0 12px 28px rgba(0,234,255,.12)}.login-note{font-size:10px;color:var(--muted2);text-align:center;margin-top:18px}

/* APP SHELL */
.app{min-height:100vh}.sidebar{position:fixed;left:0;top:0;bottom:0;width:265px;background:linear-gradient(180deg,rgba(6,20,50,.99) 0%,rgba(4,22,56,.98) 42%,rgba(8,12,39,.99) 100%);border-right:1px solid rgba(0,234,255,.24);z-index:50;overflow-y:auto;box-shadow:18px 0 55px rgba(0,84,180,.10),inset -1px 0 0 rgba(255,255,255,.025);scrollbar-width:thin;scrollbar-color:rgba(0,234,255,.35) rgba(255,255,255,.03)}.sidebar::-webkit-scrollbar{width:7px}.sidebar::-webkit-scrollbar-track{background:rgba(255,255,255,.02)}.sidebar::-webkit-scrollbar-thumb{background:linear-gradient(180deg,rgba(0,234,255,.55),rgba(45,123,255,.45));border-radius:10px}.side-brand{padding:20px 18px;border-bottom:1px solid rgba(0,234,255,.14);background:linear-gradient(135deg,rgba(0,234,255,.10),rgba(45,123,255,.08) 48%,rgba(121,65,255,.07));position:relative;overflow:hidden}.side-brand:after{content:"";position:absolute;right:-45px;top:-55px;width:130px;height:130px;border-radius:50%;background:radial-gradient(circle,rgba(0,234,255,.20),transparent 68%);pointer-events:none}.side-brand-main{display:flex;align-items:center;gap:11px}.side-logo{width:44px;height:44px;border-radius:13px;display:grid;place-items:center;border:1px solid var(--cyan);color:#8ff9ff;font-weight:900;font-size:14px;background:linear-gradient(145deg,rgba(0,234,255,.15),rgba(45,123,255,.12) 55%,rgba(121,65,255,.12));box-shadow:0 0 20px rgba(0,234,255,.18),inset 0 0 16px rgba(0,234,255,.06)}.side-brand h2{font-size:16px}.side-brand p{font-size:10px;color:var(--muted2)}.side-user{margin:12px 14px;padding:13px;border-radius:13px;background:linear-gradient(135deg,rgba(0,234,255,.11),rgba(45,123,255,.07) 55%,rgba(121,65,255,.06));border:1px solid rgba(0,234,255,.16);display:flex;gap:10px;align-items:center;box-shadow:inset 0 0 20px rgba(0,234,255,.025),0 8px 24px rgba(0,67,150,.12)}.avatar{width:38px;height:38px;border-radius:11px;display:grid;place-items:center;background:linear-gradient(145deg,rgba(0,234,255,.20),rgba(45,123,255,.16) 58%,rgba(121,65,255,.14));border:1px solid rgba(0,234,255,.24);color:#8ff9ff;font-size:12px;font-weight:900;flex:none;box-shadow:0 0 14px rgba(0,234,255,.12)}.side-user strong{font-size:11px;display:block}.side-user span{display:block;color:var(--muted2);font-size:9px;margin-top:2px}.menu-title{padding:15px 17px 7px;color:#5d9db5;font-size:9px;font-weight:900;letter-spacing:1.5px;text-transform:uppercase;text-shadow:0 0 10px rgba(0,234,255,.08)}.side-menu{padding-bottom:18px}.side-menu a{display:flex;align-items:center;gap:10px;margin:3px 9px;padding:10px 10px;border-radius:11px;color:#a9ccda;font-size:11px;border:1px solid transparent;transition:.2s;position:relative;overflow:hidden}.side-menu a:before{content:"";position:absolute;left:0;top:0;bottom:0;width:3px;background:linear-gradient(180deg,var(--cyan),#6e7dff);transform:scaleY(0);transform-origin:center;transition:.2s;border-radius:0 3px 3px 0}.side-menu a:hover{color:#effeff;background:linear-gradient(90deg,rgba(0,234,255,.08),rgba(45,123,255,.055),rgba(121,65,255,.025));border-color:rgba(0,234,255,.14);box-shadow:inset 0 0 18px rgba(0,234,255,.025)}.side-menu a:hover:before{transform:scaleY(.58)}.side-menu a.active{color:#74f6ff;background:linear-gradient(90deg,rgba(0,234,255,.16),rgba(45,123,255,.09) 60%,rgba(121,65,255,.07));border-color:rgba(0,234,255,.26);box-shadow:0 7px 18px rgba(0,92,180,.10),inset 0 0 22px rgba(0,234,255,.035)}.side-menu a.active:before{transform:scaleY(1);box-shadow:0 0 13px rgba(0,234,255,.55)}.menu-ico{width:24px;text-align:center;font-size:14px;color:#79b7d6;transition:.2s;filter:drop-shadow(0 0 6px rgba(0,234,255,.05))}.side-menu a:hover .menu-ico,.side-menu a.active .menu-ico{color:var(--cyan);filter:drop-shadow(0 0 7px rgba(0,234,255,.28))}.menu-badge{margin-left:auto;font-size:8px;padding:3px 6px;border-radius:999px;color:#ffe28a;border:1px solid rgba(255,213,106,.22);background:linear-gradient(135deg,rgba(255,213,106,.10),rgba(255,150,40,.05));box-shadow:0 0 10px rgba(255,213,106,.05)}
.main{margin-left:265px;min-height:100vh}.topbar{position:sticky;top:0;z-index:40;height:68px;background:rgba(2,8,23,.86);backdrop-filter:blur(18px);border-bottom:1px solid var(--line2);display:flex;align-items:center;justify-content:space-between;padding:0 24px}.top-left{display:flex;align-items:center;gap:10px}.mobile-menu{display:none}.crumbs{font-size:11px;color:var(--muted2)}.crumbs strong{color:#dffbff}.top-actions{display:flex;align-items:center;gap:8px}.top-icon{width:35px;height:35px;border-radius:10px;background:rgba(0,234,255,.03);border:1px solid var(--line2);display:grid;place-items:center;cursor:pointer;color:#a7d6e4;position:relative}.notify-dot{position:absolute;width:6px;height:6px;border-radius:50%;background:var(--danger);right:7px;top:7px;box-shadow:0 0 9px rgba(255,119,119,.7)}.profile-mini{display:flex;align-items:center;gap:8px;padding:4px 7px 4px 4px;border:1px solid var(--line2);border-radius:11px}.profile-mini strong{font-size:10px}.profile-mini span{display:block;font-size:8px;color:var(--muted2)}.content{padding:24px;max-width:1800px;margin:0 auto}.mobile-overlay{display:none}

/* WIDGETS */
.hero{padding:26px;border-radius:22px;background:linear-gradient(135deg,rgba(0,234,255,.09),rgba(45,123,255,.07),rgba(3,13,33,.93));border:1px solid var(--line);position:relative;overflow:hidden}.hero:after{content:"";position:absolute;width:330px;height:330px;right:-120px;top:-160px;border-radius:50%;background:radial-gradient(circle,rgba(0,234,255,.18),transparent 65%);pointer-events:none}.hero-top{display:flex;align-items:flex-start;justify-content:space-between;gap:20px}.hero h1{font-size:27px;letter-spacing:-.8px}.hero p{font-size:12px;color:var(--muted);margin-top:5px}.hero-meta{margin-top:16px;display:flex;flex-wrap:wrap;gap:7px}.hero-pill{padding:6px 9px;border-radius:999px;background:rgba(0,0,0,.12);border:1px solid var(--line2);color:#a9d6e3;font-size:9px}.quick-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:14px;margin-top:16px}.stat{padding:17px 18px;border-radius:15px;background:rgba(5,18,45,.92);border:1px solid var(--line);min-height:116px}.stat-top{display:flex;justify-content:space-between;align-items:flex-start}.stat-icon{width:36px;height:36px;border-radius:11px;display:grid;place-items:center;background:rgba(0,234,255,.06);border:1px solid var(--line2);font-size:16px}.stat-label{font-size:9px;color:var(--muted2);text-transform:uppercase;letter-spacing:.7px;margin-top:14px}.stat-value{font-size:22px;font-weight:900;margin-top:4px}.stat-note{font-size:9px;color:var(--muted);margin-top:4px}
.list-stack{display:grid;gap:9px}.list-item{padding:12px;border:1px solid var(--line2);border-radius:12px;background:rgba(255,255,255,.012)}.list-item .title{font-size:11px;font-weight:800}.list-item .desc{font-size:10px;color:var(--muted);margin-top:3px}.list-item .date{font-size:9px;color:var(--muted2);margin-top:7px}
.progress{height:8px;border-radius:999px;background:rgba(255,255,255,.05);overflow:hidden}.progress > span{height:100%;display:block;background:linear-gradient(90deg,var(--blue),var(--cyan));border-radius:inherit;box-shadow:0 0 14px rgba(0,234,255,.25)}
.table-wrap{overflow:auto;border:1px solid var(--line2);border-radius:13px}.data-table{width:100%;border-collapse:collapse;min-width:680px}.data-table th{background:rgba(0,234,255,.035);color:var(--cyan);font-size:9px;text-transform:uppercase;letter-spacing:.6px;padding:11px 12px;border-bottom:1px solid var(--line2);text-align:left}.data-table td{padding:11px 12px;font-size:10px;color:#c4e0e9;border-bottom:1px solid rgba(255,255,255,.035)}.data-table tr:hover td{background:rgba(0,234,255,.017)}.data-table tr:last-child td{border-bottom:0}.code{color:var(--cyan);font-weight:800}.empty{padding:28px;text-align:center;color:var(--muted);font-size:11px}
.key-value{display:grid;grid-template-columns:160px 1fr;gap:0;border:1px solid var(--line2);border-radius:13px;overflow:hidden}.kv-row{display:contents}.kv-row div{padding:11px 12px;border-bottom:1px solid var(--line2);font-size:10px}.kv-row:nth-last-child(-n+1) div{border-bottom:0}.kv-key{background:rgba(0,234,255,.025);color:var(--muted)}.kv-value{color:#dff7fd}
.schedule-card{padding:12px;border:1px solid var(--line2);border-radius:13px;background:rgba(255,255,255,.012);transition:.2s}.schedule-card:hover{border-color:var(--line);transform:translateY(-1px)}.schedule-time{font-size:9px;color:var(--cyan);font-weight:800}.schedule-name{font-size:12px;font-weight:800;margin-top:5px}.schedule-meta{font-size:9px;color:var(--muted);margin-top:5px}
.calendar{display:grid;grid-template-columns:repeat(7,minmax(0,1fr));border:1px solid var(--line2);border-radius:15px;overflow:hidden}.cal-head{padding:10px;text-align:center;background:rgba(0,234,255,.035);font-size:9px;color:var(--cyan);font-weight:800;border-right:1px solid var(--line2);border-bottom:1px solid var(--line2)}.cal-day{min-height:96px;padding:8px;border-right:1px solid var(--line2);border-bottom:1px solid var(--line2);background:rgba(255,255,255,.009)}.cal-day:nth-child(7n){border-right:0}.cal-num{font-size:10px;font-weight:800;color:#bcd7e2}.cal-event{display:block;margin-top:8px;padding:5px 6px;border-radius:7px;background:rgba(0,234,255,.05);border:1px solid var(--line2);font-size:8px;color:#9adce7}.cal-today .cal-num{display:inline-grid;place-items:center;width:21px;height:21px;border-radius:7px;background:var(--cyan);color:#00151b}.checklist{display:grid;gap:8px}.check-row{display:flex;align-items:flex-start;gap:9px;padding:10px 11px;border:1px solid var(--line2);border-radius:11px}.check-row input{margin-top:3px;accent-color:var(--cyan)}.check-row strong{font-size:10px;display:block}.check-row span{font-size:9px;color:var(--muted);display:block;margin-top:2px}
.pay-card{padding:22px;border-radius:18px;border:1px solid var(--line);background:linear-gradient(140deg,rgba(0,234,255,.06),rgba(5,18,45,.9))}.pay-amount{font-size:34px;font-weight:900;color:var(--cyan);letter-spacing:-1px}.pay-label{font-size:9px;color:var(--muted2);text-transform:uppercase;letter-spacing:1px}.deadline-box{padding:13px;border-radius:12px;background:rgba(255,213,106,.05);border:1px solid rgba(255,213,106,.14);color:#f6dc84}.method{display:flex;gap:10px;align-items:center;padding:11px;border-radius:11px;border:1px solid var(--line2);background:rgba(255,255,255,.01);cursor:pointer}.method input{accent-color:var(--cyan)}.method strong{font-size:10px}.method span{font-size:9px;color:var(--muted);display:block;margin-top:2px}
.notice{padding:12px 14px;border-radius:12px;border:1px solid var(--line2);background:rgba(0,234,255,.035);font-size:10px;color:#c7e6ee}.notice strong{color:var(--cyan)}

/* MODALS / DROPDOWNS */
.modal-layer{position:fixed;inset:0;background:rgba(0,0,0,.62);backdrop-filter:blur(7px);display:none;align-items:center;justify-content:center;z-index:100;padding:20px}.modal-layer.show{display:flex}.modal{width:min(100%,680px);max-height:90vh;overflow:auto;background:linear-gradient(150deg,#081b3f,#031027);border:1px solid var(--line);border-radius:20px;box-shadow:0 40px 100px rgba(0,0,0,.48);padding:20px}.modal-head{display:flex;align-items:center;justify-content:space-between;gap:10px;margin-bottom:15px}.modal-head h3{font-size:16px}.close{width:30px;height:30px;border-radius:9px;display:grid;place-items:center;cursor:pointer;background:rgba(255,255,255,.03);border:1px solid var(--line2);color:#9ac9d8}.toast{position:fixed;right:20px;bottom:20px;z-index:200;min-width:260px;max-width:380px;padding:13px 15px;border-radius:13px;background:#071c3d;border:1px solid var(--line);box-shadow:0 20px 50px rgba(0,0,0,.35);display:none}.toast.show{display:block;animation:toastIn .25s ease}.toast-title{font-size:10px;color:var(--cyan);font-weight:800}.toast-text{font-size:10px;color:#cbe5ec;margin-top:3px}@keyframes toastIn{from{transform:translateY(12px);opacity:0}to{transform:translateY(0);opacity:1}}

/* PRINT */
.print-only{display:none}
@media print{body{background:#fff;color:#111}.sidebar,.topbar,.no-print{display:none!important}.main{margin-left:0}.content{padding:0}.card,.hero{box-shadow:none!important;border:1px solid #bbb!important;background:#fff!important;color:#111}.print-only{display:block!important}.data-table th,.data-table td{color:#111!important;background:#fff!important;border-color:#ccc!important}}

/* RESPONSIVE */
@media(max-width:1200px){.quick-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.g4{grid-template-columns:repeat(2,minmax(0,1fr))}.g5{grid-template-columns:repeat(3,minmax(0,1fr))}}
@media(max-width:950px){.login-card{grid-template-columns:1fr}.login-left{display:none}.main{margin-left:0}.sidebar{transform:translateX(-100%);transition:.25s;box-shadow:25px 0 70px rgba(0,0,0,.35)}body.menu-open .sidebar{transform:translateX(0)}.mobile-menu{display:grid;width:35px;height:35px;place-items:center;border:1px solid var(--line2);border-radius:10px;background:rgba(0,234,255,.03);color:var(--cyan);cursor:pointer}.mobile-overlay{position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:45}.mobile-overlay.show{display:block}.g3{grid-template-columns:1fr 1fr}.g2{grid-template-columns:1fr}}
@media(max-width:700px){.content{padding:15px}.topbar{padding:0 15px}.topbar .profile-mini strong,.topbar .profile-mini span{display:none}.section-head{align-items:flex-start;flex-direction:column}.hero h1{font-size:22px}.quick-grid{grid-template-columns:1fr}.g3,.g4,.g5{grid-template-columns:1fr}.key-value{grid-template-columns:1fr}.kv-row div{border-bottom:0}.kv-key{padding-bottom:4px}.kv-value{padding-top:4px;border-bottom:1px solid var(--line2)!important}.calendar{grid-template-columns:repeat(2,minmax(0,1fr))}.cal-head{display:none}.cal-day{min-height:80px}.login-right{padding:28px}.login-submit{padding:12px}}

/* Utility scale for future visual tuning */
.m-1{margin:1px}
.p-1{padding:1px}
.gap-1{gap:1px}
.r-1{border-radius:1px}
.m-2{margin:2px}
.p-2{padding:2px}
.gap-2{gap:2px}
.r-2{border-radius:2px}
.m-3{margin:3px}
.p-3{padding:3px}
.gap-3{gap:3px}
.r-3{border-radius:3px}
.m-4{margin:4px}
.p-4{padding:4px}
.gap-4{gap:4px}
.r-4{border-radius:4px}
.m-5{margin:5px}
.p-5{padding:5px}
.gap-5{gap:5px}
.r-5{border-radius:5px}
.m-6{margin:6px}
.p-6{padding:6px}
.gap-6{gap:6px}
.r-6{border-radius:6px}
.m-7{margin:7px}
.p-7{padding:7px}
.gap-7{gap:7px}
.r-7{border-radius:7px}
.m-8{margin:8px}
.p-8{padding:8px}
.gap-8{gap:8px}
.r-8{border-radius:8px}
.m-9{margin:9px}
.p-9{padding:9px}
.gap-9{gap:9px}
.r-9{border-radius:9px}
.m-10{margin:10px}
.p-10{padding:10px}
.gap-10{gap:10px}
.r-10{border-radius:10px}
.m-11{margin:11px}
.p-11{padding:11px}
.gap-11{gap:11px}
.r-11{border-radius:11px}
.m-12{margin:12px}
.p-12{padding:12px}
.gap-12{gap:12px}
.r-12{border-radius:12px}
.m-13{margin:13px}
.p-13{padding:13px}
.gap-13{gap:13px}
.r-13{border-radius:13px}
.m-14{margin:14px}
.p-14{padding:14px}
.gap-14{gap:14px}
.r-14{border-radius:14px}
.m-15{margin:15px}
.p-15{padding:15px}
.gap-15{gap:15px}
.r-15{border-radius:15px}
.m-16{margin:16px}
.p-16{padding:16px}
.gap-16{gap:16px}
.r-16{border-radius:16px}
.m-17{margin:17px}
.p-17{padding:17px}
.gap-17{gap:17px}
.r-17{border-radius:17px}
.m-18{margin:18px}
.p-18{padding:18px}
.gap-18{gap:18px}
.r-18{border-radius:18px}
.m-19{margin:19px}
.p-19{padding:19px}
.gap-19{gap:19px}
.r-19{border-radius:19px}
.m-20{margin:20px}
.p-20{padding:20px}
.gap-20{gap:20px}
.r-20{border-radius:20px}
.m-21{margin:21px}
.p-21{padding:21px}
.gap-21{gap:21px}
.r-21{border-radius:21px}
.m-22{margin:22px}
.p-22{padding:22px}
.gap-22{gap:22px}
.r-22{border-radius:22px}
.m-23{margin:23px}
.p-23{padding:23px}
.gap-23{gap:23px}
.r-23{border-radius:23px}
.m-24{margin:24px}
.p-24{padding:24px}
.gap-24{gap:24px}
.r-24{border-radius:24px}
.m-25{margin:25px}
.p-25{padding:25px}
.gap-25{gap:25px}
.r-25{border-radius:25px}
.m-26{margin:26px}
.p-26{padding:26px}
.gap-26{gap:26px}
.r-26{border-radius:26px}
.m-27{margin:27px}
.p-27{padding:27px}
.gap-27{gap:27px}
.r-27{border-radius:27px}
.m-28{margin:28px}
.p-28{padding:28px}
.gap-28{gap:28px}
.r-28{border-radius:28px}
.m-29{margin:29px}
.p-29{padding:29px}
.gap-29{gap:29px}
.r-29{border-radius:29px}
.m-30{margin:30px}
.p-30{padding:30px}
.gap-30{gap:30px}
.r-30{border-radius:30px}
.m-31{margin:31px}
.p-31{padding:31px}
.gap-31{gap:31px}
.r-31{border-radius:31px}
.m-32{margin:32px}
.p-32{padding:32px}
.gap-32{gap:32px}
.r-32{border-radius:32px}
.m-33{margin:33px}
.p-33{padding:33px}
.gap-33{gap:33px}
.r-33{border-radius:33px}
.m-34{margin:34px}
.p-34{padding:34px}
.gap-34{gap:34px}
.r-34{border-radius:34px}
.m-35{margin:35px}
.p-35{padding:35px}
.gap-35{gap:35px}
.r-35{border-radius:35px}
.m-36{margin:36px}
.p-36{padding:36px}
.gap-36{gap:36px}
.r-36{border-radius:36px}
.m-37{margin:37px}
.p-37{padding:37px}
.gap-37{gap:37px}
.r-37{border-radius:37px}
.m-38{margin:38px}
.p-38{padding:38px}
.gap-38{gap:38px}
.r-38{border-radius:38px}
.m-39{margin:39px}
.p-39{padding:39px}
.gap-39{gap:39px}
.r-39{border-radius:39px}
.m-40{margin:40px}
.p-40{padding:40px}
.gap-40{gap:40px}
.r-40{border-radius:40px}
.m-41{margin:20px}
.p-41{padding:20px}
.gap-41{gap:20px}
.r-41{border-radius:20px}
.m-42{margin:21px}
.p-42{padding:21px}
.gap-42{gap:21px}
.r-42{border-radius:21px}
.m-43{margin:21px}
.p-43{padding:21px}
.gap-43{gap:21px}
.r-43{border-radius:21px}
.m-44{margin:22px}
.p-44{padding:22px}
.gap-44{gap:22px}
.r-44{border-radius:22px}
.m-45{margin:22px}
.p-45{padding:22px}
.gap-45{gap:22px}
.r-45{border-radius:22px}
.m-46{margin:23px}
.p-46{padding:23px}
.gap-46{gap:23px}
.r-46{border-radius:23px}
.m-47{margin:23px}
.p-47{padding:23px}
.gap-47{gap:23px}
.r-47{border-radius:23px}
.m-48{margin:24px}
.p-48{padding:24px}
.gap-48{gap:24px}
.r-48{border-radius:24px}
.m-49{margin:24px}
.p-49{padding:24px}
.gap-49{gap:24px}
.r-49{border-radius:24px}
.m-50{margin:25px}
.p-50{padding:25px}
.gap-50{gap:25px}
.r-50{border-radius:25px}
.m-51{margin:25px}
.p-51{padding:25px}
.gap-51{gap:25px}
.r-51{border-radius:25px}
.m-52{margin:26px}
.p-52{padding:26px}
.gap-52{gap:26px}
.r-52{border-radius:26px}
.m-53{margin:26px}
.p-53{padding:26px}
.gap-53{gap:26px}
.r-53{border-radius:26px}
.m-54{margin:27px}
.p-54{padding:27px}
.gap-54{gap:27px}
.r-54{border-radius:27px}
.m-55{margin:27px}
.p-55{padding:27px}
.gap-55{gap:27px}
.r-55{border-radius:27px}
.m-56{margin:28px}
.p-56{padding:28px}
.gap-56{gap:28px}
.r-56{border-radius:28px}
.m-57{margin:28px}
.p-57{padding:28px}
.gap-57{gap:28px}
.r-57{border-radius:28px}
.m-58{margin:29px}
.p-58{padding:29px}
.gap-58{gap:29px}
.r-58{border-radius:29px}
.m-59{margin:29px}
.p-59{padding:29px}
.gap-59{gap:29px}
.r-59{border-radius:29px}
.m-60{margin:30px}
.p-60{padding:30px}
.gap-60{gap:30px}
.r-60{border-radius:30px}
.m-61{margin:30px}
.p-61{padding:30px}
.gap-61{gap:30px}
.r-61{border-radius:30px}
.m-62{margin:31px}
.p-62{padding:31px}
.gap-62{gap:31px}
.r-62{border-radius:31px}
.m-63{margin:31px}
.p-63{padding:31px}
.gap-63{gap:31px}
.r-63{border-radius:31px}
.m-64{margin:32px}
.p-64{padding:32px}
.gap-64{gap:32px}
.r-64{border-radius:32px}
.m-65{margin:32px}
.p-65{padding:32px}
.gap-65{gap:32px}
.r-65{border-radius:32px}
.m-66{margin:33px}
.p-66{padding:33px}
.gap-66{gap:33px}
.r-66{border-radius:33px}
.m-67{margin:33px}
.p-67{padding:33px}
.gap-67{gap:33px}
.r-67{border-radius:33px}
.m-68{margin:34px}
.p-68{padding:34px}
.gap-68{gap:34px}
.r-68{border-radius:34px}
.m-69{margin:34px}
.p-69{padding:34px}
.gap-69{gap:34px}
.r-69{border-radius:34px}
.m-70{margin:35px}
.p-70{padding:35px}
.gap-70{gap:35px}
.r-70{border-radius:35px}
.m-71{margin:35px}
.p-71{padding:35px}
.gap-71{gap:35px}
.r-71{border-radius:35px}
.m-72{margin:36px}
.p-72{padding:36px}
.gap-72{gap:36px}
.r-72{border-radius:36px}
.m-73{margin:36px}
.p-73{padding:36px}
.gap-73{gap:36px}
.r-73{border-radius:36px}
.m-74{margin:37px}
.p-74{padding:37px}
.gap-74{gap:37px}
.r-74{border-radius:37px}
.m-75{margin:37px}
.p-75{padding:37px}
.gap-75{gap:37px}
.r-75{border-radius:37px}
.m-76{margin:38px}
.p-76{padding:38px}
.gap-76{gap:38px}
.r-76{border-radius:38px}
.m-77{margin:38px}
.p-77{padding:38px}
.gap-77{gap:38px}
.r-77{border-radius:38px}
.m-78{margin:39px}
.p-78{padding:39px}
.gap-78{gap:39px}
.r-78{border-radius:39px}
.m-79{margin:39px}
.p-79{padding:39px}
.gap-79{gap:39px}
.r-79{border-radius:39px}
.m-80{margin:40px}
.p-80{padding:40px}
.gap-80{gap:40px}
.r-80{border-radius:40px}
.m-81{margin:40px}
.p-81{padding:40px}
.gap-81{gap:40px}
.r-81{border-radius:40px}
.m-82{margin:41px}
.p-82{padding:41px}
.gap-82{gap:41px}
.r-82{border-radius:41px}
.m-83{margin:41px}
.p-83{padding:41px}
.gap-83{gap:41px}
.r-83{border-radius:41px}
.m-84{margin:42px}
.p-84{padding:42px}
.gap-84{gap:42px}
.r-84{border-radius:42px}
.m-85{margin:42px}
.p-85{padding:42px}
.gap-85{gap:42px}
.r-85{border-radius:42px}
.m-86{margin:43px}
.p-86{padding:43px}
.gap-86{gap:43px}
.r-86{border-radius:43px}
.m-87{margin:43px}
.p-87{padding:43px}
.gap-87{gap:43px}
.r-87{border-radius:43px}
.m-88{margin:44px}
.p-88{padding:44px}
.gap-88{gap:44px}
.r-88{border-radius:44px}
.m-89{margin:44px}
.p-89{padding:44px}
.gap-89{gap:44px}
.r-89{border-radius:44px}
.m-90{margin:45px}
.p-90{padding:45px}
.gap-90{gap:45px}
.r-90{border-radius:45px}
.m-91{margin:45px}
.p-91{padding:45px}
.gap-91{gap:45px}
.r-91{border-radius:45px}
.m-92{margin:46px}
.p-92{padding:46px}
.gap-92{gap:46px}
.r-92{border-radius:46px}
.m-93{margin:46px}
.p-93{padding:46px}
.gap-93{gap:46px}
.r-93{border-radius:46px}
.m-94{margin:47px}
.p-94{padding:47px}
.gap-94{gap:47px}
.r-94{border-radius:47px}
.m-95{margin:47px}
.p-95{padding:47px}
.gap-95{gap:47px}
.r-95{border-radius:47px}
.m-96{margin:48px}
.p-96{padding:48px}
.gap-96{gap:48px}
.r-96{border-radius:48px}
.m-97{margin:48px}
.p-97{padding:48px}
.gap-97{gap:48px}
.r-97{border-radius:48px}
.m-98{margin:49px}
.p-98{padding:49px}
.gap-98{gap:49px}
.r-98{border-radius:49px}
.m-99{margin:49px}
.p-99{padding:49px}
.gap-99{gap:49px}
.r-99{border-radius:49px}
.m-100{margin:50px}
.p-100{padding:50px}
.gap-100{gap:50px}
.r-100{border-radius:50px}
.m-101{margin:50px}
.p-101{padding:50px}
.gap-101{gap:50px}
.r-101{border-radius:50px}
.m-102{margin:51px}
.p-102{padding:51px}
.gap-102{gap:51px}
.r-102{border-radius:50px}
.m-103{margin:51px}
.p-103{padding:51px}
.gap-103{gap:51px}
.r-103{border-radius:50px}
.m-104{margin:52px}
.p-104{padding:52px}
.gap-104{gap:52px}
.r-104{border-radius:50px}
.m-105{margin:52px}
.p-105{padding:52px}
.gap-105{gap:52px}
.r-105{border-radius:50px}
.m-106{margin:53px}
.p-106{padding:53px}
.gap-106{gap:53px}
.r-106{border-radius:50px}
.m-107{margin:53px}
.p-107{padding:53px}
.gap-107{gap:53px}
.r-107{border-radius:50px}
.m-108{margin:54px}
.p-108{padding:54px}
.gap-108{gap:54px}
.r-108{border-radius:50px}
.m-109{margin:54px}
.p-109{padding:54px}
.gap-109{gap:54px}
.r-109{border-radius:50px}
.m-110{margin:55px}
.p-110{padding:55px}
.gap-110{gap:55px}
.r-110{border-radius:50px}
.m-111{margin:55px}
.p-111{padding:55px}
.gap-111{gap:55px}
.r-111{border-radius:50px}
.m-112{margin:56px}
.p-112{padding:56px}
.gap-112{gap:56px}
.r-112{border-radius:50px}
.m-113{margin:56px}
.p-113{padding:56px}
.gap-113{gap:56px}
.r-113{border-radius:50px}
.m-114{margin:57px}
.p-114{padding:57px}
.gap-114{gap:57px}
.r-114{border-radius:50px}
.m-115{margin:57px}
.p-115{padding:57px}
.gap-115{gap:57px}
.r-115{border-radius:50px}
.m-116{margin:58px}
.p-116{padding:58px}
.gap-116{gap:58px}
.r-116{border-radius:50px}
.m-117{margin:58px}
.p-117{padding:58px}
.gap-117{gap:58px}
.r-117{border-radius:50px}
.m-118{margin:59px}
.p-118{padding:59px}
.gap-118{gap:59px}
.r-118{border-radius:50px}
.m-119{margin:59px}
.p-119{padding:59px}
.gap-119{gap:59px}
.r-119{border-radius:50px}
.m-120{margin:60px}
.p-120{padding:60px}
.gap-120{gap:60px}
.r-120{border-radius:50px}
.m-121{margin:60px}
.p-121{padding:60px}
.gap-121{gap:60px}
.r-121{border-radius:50px}
.m-122{margin:61px}
.p-122{padding:61px}
.gap-122{gap:61px}
.r-122{border-radius:50px}
.m-123{margin:61px}
.p-123{padding:61px}
.gap-123{gap:61px}
.r-123{border-radius:50px}
.m-124{margin:62px}
.p-124{padding:62px}
.gap-124{gap:62px}
.r-124{border-radius:50px}
.m-125{margin:62px}
.p-125{padding:62px}
.gap-125{gap:62px}
.r-125{border-radius:50px}
.m-126{margin:63px}
.p-126{padding:63px}
.gap-126{gap:63px}
.r-126{border-radius:50px}
.m-127{margin:63px}
.p-127{padding:63px}
.gap-127{gap:63px}
.r-127{border-radius:50px}
.m-128{margin:64px}
.p-128{padding:64px}
.gap-128{gap:64px}
.r-128{border-radius:50px}
.m-129{margin:64px}
.p-129{padding:64px}
.gap-129{gap:64px}
.r-129{border-radius:50px}
.m-130{margin:65px}
.p-130{padding:65px}
.gap-130{gap:65px}
.r-130{border-radius:50px}
.m-131{margin:65px}
.p-131{padding:65px}
.gap-131{gap:65px}
.r-131{border-radius:50px}
.m-132{margin:66px}
.p-132{padding:66px}
.gap-132{gap:66px}
.r-132{border-radius:50px}
.m-133{margin:66px}
.p-133{padding:66px}
.gap-133{gap:66px}
.r-133{border-radius:50px}
.m-134{margin:67px}
.p-134{padding:67px}
.gap-134{gap:67px}
.r-134{border-radius:50px}
.m-135{margin:67px}
.p-135{padding:67px}
.gap-135{gap:67px}
.r-135{border-radius:50px}
.m-136{margin:68px}
.p-136{padding:68px}
.gap-136{gap:68px}
.r-136{border-radius:50px}
.m-137{margin:68px}
.p-137{padding:68px}
.gap-137{gap:68px}
.r-137{border-radius:50px}
.m-138{margin:69px}
.p-138{padding:69px}
.gap-138{gap:69px}
.r-138{border-radius:50px}
.m-139{margin:69px}
.p-139{padding:69px}
.gap-139{gap:69px}
.r-139{border-radius:50px}
.m-140{margin:70px}
.p-140{padding:70px}
.gap-140{gap:70px}
.r-140{border-radius:50px}
.m-141{margin:70px}
.p-141{padding:70px}
.gap-141{gap:70px}
.r-141{border-radius:50px}
.m-142{margin:71px}
.p-142{padding:71px}
.gap-142{gap:71px}
.r-142{border-radius:50px}
.m-143{margin:71px}
.p-143{padding:71px}
.gap-143{gap:71px}
.r-143{border-radius:50px}
.m-144{margin:72px}
.p-144{padding:72px}
.gap-144{gap:72px}
.r-144{border-radius:50px}
.m-145{margin:72px}
.p-145{padding:72px}
.gap-145{gap:72px}
.r-145{border-radius:50px}
.m-146{margin:73px}
.p-146{padding:73px}
.gap-146{gap:73px}
.r-146{border-radius:50px}
.m-147{margin:73px}
.p-147{padding:73px}
.gap-147{gap:73px}
.r-147{border-radius:50px}
.m-148{margin:74px}
.p-148{padding:74px}
.gap-148{gap:74px}
.r-148{border-radius:50px}
.m-149{margin:74px}
.p-149{padding:74px}
.gap-149{gap:74px}
.r-149{border-radius:50px}
.m-150{margin:75px}
.p-150{padding:75px}
.gap-150{gap:75px}
.r-150{border-radius:50px}
.m-151{margin:75px}
.p-151{padding:75px}
.gap-151{gap:75px}
.r-151{border-radius:50px}
.m-152{margin:76px}
.p-152{padding:76px}
.gap-152{gap:76px}
.r-152{border-radius:50px}
.m-153{margin:76px}
.p-153{padding:76px}
.gap-153{gap:76px}
.r-153{border-radius:50px}
.m-154{margin:77px}
.p-154{padding:77px}
.gap-154{gap:77px}
.r-154{border-radius:50px}
.m-155{margin:77px}
.p-155{padding:77px}
.gap-155{gap:77px}
.r-155{border-radius:50px}
.m-156{margin:78px}
.p-156{padding:78px}
.gap-156{gap:78px}
.r-156{border-radius:50px}
.m-157{margin:78px}
.p-157{padding:78px}
.gap-157{gap:78px}
.r-157{border-radius:50px}
.m-158{margin:79px}
.p-158{padding:79px}
.gap-158{gap:79px}
.r-158{border-radius:50px}
.m-159{margin:79px}
.p-159{padding:79px}
.gap-159{gap:79px}
.r-159{border-radius:50px}
.m-160{margin:80px}
.p-160{padding:80px}
.gap-160{gap:80px}
.r-160{border-radius:50px}
.m-161{margin:80px}
.p-161{padding:80px}
.gap-161{gap:80px}
.r-161{border-radius:50px}
.m-162{margin:81px}
.p-162{padding:81px}
.gap-162{gap:81px}
.r-162{border-radius:50px}
.m-163{margin:81px}
.p-163{padding:81px}
.gap-163{gap:81px}
.r-163{border-radius:50px}
.m-164{margin:82px}
.p-164{padding:82px}
.gap-164{gap:82px}
.r-164{border-radius:50px}
.m-165{margin:82px}
.p-165{padding:82px}
.gap-165{gap:82px}
.r-165{border-radius:50px}
.m-166{margin:83px}
.p-166{padding:83px}
.gap-166{gap:83px}
.r-166{border-radius:50px}
.m-167{margin:83px}
.p-167{padding:83px}
.gap-167{gap:83px}
.r-167{border-radius:50px}
.m-168{margin:84px}
.p-168{padding:84px}
.gap-168{gap:84px}
.r-168{border-radius:50px}
.m-169{margin:84px}
.p-169{padding:84px}
.gap-169{gap:84px}
.r-169{border-radius:50px}
.m-170{margin:85px}
.p-170{padding:85px}
.gap-170{gap:85px}
.r-170{border-radius:50px}
.m-171{margin:85px}
.p-171{padding:85px}
.gap-171{gap:85px}
.r-171{border-radius:50px}
.m-172{margin:86px}
.p-172{padding:86px}
.gap-172{gap:86px}
.r-172{border-radius:50px}
.m-173{margin:86px}
.p-173{padding:86px}
.gap-173{gap:86px}
.r-173{border-radius:50px}
.m-174{margin:87px}
.p-174{padding:87px}
.gap-174{gap:87px}
.r-174{border-radius:50px}
.m-175{margin:87px}
.p-175{padding:87px}
.gap-175{gap:87px}
.r-175{border-radius:50px}
.m-176{margin:88px}
.p-176{padding:88px}
.gap-176{gap:88px}
.r-176{border-radius:50px}
.m-177{margin:88px}
.p-177{padding:88px}
.gap-177{gap:88px}
.r-177{border-radius:50px}
.m-178{margin:89px}
.p-178{padding:89px}
.gap-178{gap:89px}
.r-178{border-radius:50px}
.m-179{margin:89px}
.p-179{padding:89px}
.gap-179{gap:89px}
.r-179{border-radius:50px}
.m-180{margin:90px}
.p-180{padding:90px}
.gap-180{gap:90px}
.r-180{border-radius:50px}
.m-181{margin:90px}
.p-181{padding:90px}
.gap-181{gap:90px}
.r-181{border-radius:50px}
.m-182{margin:91px}
.p-182{padding:91px}
.gap-182{gap:91px}
.r-182{border-radius:50px}
.m-183{margin:91px}
.p-183{padding:91px}
.gap-183{gap:91px}
.r-183{border-radius:50px}
.m-184{margin:92px}
.p-184{padding:92px}
.gap-184{gap:92px}
.r-184{border-radius:50px}
.m-185{margin:92px}
.p-185{padding:92px}
.gap-185{gap:92px}
.r-185{border-radius:50px}
.m-186{margin:93px}
.p-186{padding:93px}
.gap-186{gap:93px}
.r-186{border-radius:50px}
.m-187{margin:93px}
.p-187{padding:93px}
.gap-187{gap:93px}
.r-187{border-radius:50px}
.m-188{margin:94px}
.p-188{padding:94px}
.gap-188{gap:94px}
.r-188{border-radius:50px}
.m-189{margin:94px}
.p-189{padding:94px}
.gap-189{gap:94px}
.r-189{border-radius:50px}
.m-190{margin:95px}
.p-190{padding:95px}
.gap-190{gap:95px}
.r-190{border-radius:50px}
.m-191{margin:95px}
.p-191{padding:95px}
.gap-191{gap:95px}
.r-191{border-radius:50px}
.m-192{margin:96px}
.p-192{padding:96px}
.gap-192{gap:96px}
.r-192{border-radius:50px}
.m-193{margin:96px}
.p-193{padding:96px}
.gap-193{gap:96px}
.r-193{border-radius:50px}
.m-194{margin:97px}
.p-194{padding:97px}
.gap-194{gap:97px}
.r-194{border-radius:50px}
.m-195{margin:97px}
.p-195{padding:97px}
.gap-195{gap:97px}
.r-195{border-radius:50px}
.m-196{margin:98px}
.p-196{padding:98px}
.gap-196{gap:98px}
.r-196{border-radius:50px}
.m-197{margin:98px}
.p-197{padding:98px}
.gap-197{gap:98px}
.r-197{border-radius:50px}
.m-198{margin:99px}
.p-198{padding:99px}
.gap-198{gap:99px}
.r-198{border-radius:50px}
.m-199{margin:99px}
.p-199{padding:99px}
.gap-199{gap:99px}
.r-199{border-radius:50px}
.m-200{margin:100px}
.p-200{padding:100px}
.gap-200{gap:100px}
.r-200{border-radius:50px}
.m-201{margin:100px}
.p-201{padding:100px}
.gap-201{gap:100px}
.r-201{border-radius:50px}
.m-202{margin:101px}
.p-202{padding:101px}
.gap-202{gap:101px}
.r-202{border-radius:50px}
.m-203{margin:101px}
.p-203{padding:101px}
.gap-203{gap:101px}
.r-203{border-radius:50px}
.m-204{margin:102px}
.p-204{padding:102px}
.gap-204{gap:102px}
.r-204{border-radius:50px}
.m-205{margin:102px}
.p-205{padding:102px}
.gap-205{gap:102px}
.r-205{border-radius:50px}
.m-206{margin:103px}
.p-206{padding:103px}
.gap-206{gap:103px}
.r-206{border-radius:50px}
.m-207{margin:103px}
.p-207{padding:103px}
.gap-207{gap:103px}
.r-207{border-radius:50px}
.m-208{margin:104px}
.p-208{padding:104px}
.gap-208{gap:104px}
.r-208{border-radius:50px}
.m-209{margin:104px}
.p-209{padding:104px}
.gap-209{gap:104px}
.r-209{border-radius:50px}
.m-210{margin:105px}
.p-210{padding:105px}
.gap-210{gap:105px}
.r-210{border-radius:50px}
.m-211{margin:105px}
.p-211{padding:105px}
.gap-211{gap:105px}
.r-211{border-radius:50px}
.m-212{margin:106px}
.p-212{padding:106px}
.gap-212{gap:106px}
.r-212{border-radius:50px}
.m-213{margin:106px}
.p-213{padding:106px}
.gap-213{gap:106px}
.r-213{border-radius:50px}
.m-214{margin:107px}
.p-214{padding:107px}
.gap-214{gap:107px}
.r-214{border-radius:50px}
.m-215{margin:107px}
.p-215{padding:107px}
.gap-215{gap:107px}
.r-215{border-radius:50px}
.m-216{margin:108px}
.p-216{padding:108px}
.gap-216{gap:108px}
.r-216{border-radius:50px}
.m-217{margin:108px}
.p-217{padding:108px}
.gap-217{gap:108px}
.r-217{border-radius:50px}
.m-218{margin:109px}
.p-218{padding:109px}
.gap-218{gap:109px}
.r-218{border-radius:50px}
.m-219{margin:109px}
.p-219{padding:109px}
.gap-219{gap:109px}
.r-219{border-radius:50px}
.m-220{margin:110px}
.p-220{padding:110px}
.gap-220{gap:110px}
.r-220{border-radius:50px}
.m-221{margin:110px}
.p-221{padding:110px}
.gap-221{gap:110px}
.r-221{border-radius:50px}
.m-222{margin:111px}
.p-222{padding:111px}
.gap-222{gap:111px}
.r-222{border-radius:50px}
.m-223{margin:111px}
.p-223{padding:111px}
.gap-223{gap:111px}
.r-223{border-radius:50px}
.m-224{margin:112px}
.p-224{padding:112px}
.gap-224{gap:112px}
.r-224{border-radius:50px}
.m-225{margin:112px}
.p-225{padding:112px}
.gap-225{gap:112px}
.r-225{border-radius:50px}
.m-226{margin:113px}
.p-226{padding:113px}
.gap-226{gap:113px}
.r-226{border-radius:50px}
.m-227{margin:113px}
.p-227{padding:113px}
.gap-227{gap:113px}
.r-227{border-radius:50px}
.m-228{margin:114px}
.p-228{padding:114px}
.gap-228{gap:114px}
.r-228{border-radius:50px}
.m-229{margin:114px}
.p-229{padding:114px}
.gap-229{gap:114px}
.r-229{border-radius:50px}
.m-230{margin:115px}
.p-230{padding:115px}
.gap-230{gap:115px}
.r-230{border-radius:50px}
.m-231{margin:115px}
.p-231{padding:115px}
.gap-231{gap:115px}
.r-231{border-radius:50px}
.m-232{margin:116px}
.p-232{padding:116px}
.gap-232{gap:116px}
.r-232{border-radius:50px}
.m-233{margin:116px}
.p-233{padding:116px}
.gap-233{gap:116px}
.r-233{border-radius:50px}
.m-234{margin:117px}
.p-234{padding:117px}
.gap-234{gap:117px}
.r-234{border-radius:50px}
.m-235{margin:117px}
.p-235{padding:117px}
.gap-235{gap:117px}
.r-235{border-radius:50px}
.m-236{margin:118px}
.p-236{padding:118px}
.gap-236{gap:118px}
.r-236{border-radius:50px}
.m-237{margin:118px}
.p-237{padding:118px}
.gap-237{gap:118px}
.r-237{border-radius:50px}
.m-238{margin:119px}
.p-238{padding:119px}
.gap-238{gap:119px}
.r-238{border-radius:50px}
.m-239{margin:119px}
.p-239{padding:119px}
.gap-239{gap:119px}
.r-239{border-radius:50px}
.m-240{margin:120px}
.p-240{padding:120px}
.gap-240{gap:120px}
.r-240{border-radius:50px}
.m-241{margin:120px}
.p-241{padding:120px}
.gap-241{gap:120px}
.r-241{border-radius:50px}
.m-242{margin:121px}
.p-242{padding:121px}
.gap-242{gap:121px}
.r-242{border-radius:50px}
.m-243{margin:121px}
.p-243{padding:121px}
.gap-243{gap:121px}
.r-243{border-radius:50px}
.m-244{margin:122px}
.p-244{padding:122px}
.gap-244{gap:122px}
.r-244{border-radius:50px}
.m-245{margin:122px}
.p-245{padding:122px}
.gap-245{gap:122px}
.r-245{border-radius:50px}
.m-246{margin:123px}
.p-246{padding:123px}
.gap-246{gap:123px}
.r-246{border-radius:50px}
.m-247{margin:123px}
.p-247{padding:123px}
.gap-247{gap:123px}
.r-247{border-radius:50px}
.m-248{margin:124px}
.p-248{padding:124px}
.gap-248{gap:124px}
.r-248{border-radius:50px}
.m-249{margin:124px}
.p-249{padding:124px}
.gap-249{gap:124px}
.r-249{border-radius:50px}
.m-250{margin:125px}
.p-250{padding:125px}
.gap-250{gap:125px}
.r-250{border-radius:50px}
.m-251{margin:125px}
.p-251{padding:125px}
.gap-251{gap:125px}
.r-251{border-radius:50px}
.m-252{margin:126px}
.p-252{padding:126px}
.gap-252{gap:126px}
.r-252{border-radius:50px}
.m-253{margin:126px}
.p-253{padding:126px}
.gap-253{gap:126px}
.r-253{border-radius:50px}
.m-254{margin:127px}
.p-254{padding:127px}
.gap-254{gap:127px}
.r-254{border-radius:50px}
.m-255{margin:127px}
.p-255{padding:127px}
.gap-255{gap:127px}
.r-255{border-radius:50px}
.m-256{margin:128px}
.p-256{padding:128px}
.gap-256{gap:128px}
.r-256{border-radius:50px}
.m-257{margin:128px}
.p-257{padding:128px}
.gap-257{gap:128px}
.r-257{border-radius:50px}
.m-258{margin:129px}
.p-258{padding:129px}
.gap-258{gap:129px}
.r-258{border-radius:50px}
.m-259{margin:129px}
.p-259{padding:129px}
.gap-259{gap:129px}
.r-259{border-radius:50px}
.m-260{margin:130px}
.p-260{padding:130px}
.gap-260{gap:130px}
.r-260{border-radius:50px}
.m-261{margin:130px}
.p-261{padding:130px}
.gap-261{gap:130px}
.r-261{border-radius:50px}
.m-262{margin:131px}
.p-262{padding:131px}
.gap-262{gap:131px}
.r-262{border-radius:50px}
.m-263{margin:131px}
.p-263{padding:131px}
.gap-263{gap:131px}
.r-263{border-radius:50px}
.m-264{margin:132px}
.p-264{padding:132px}
.gap-264{gap:132px}
.r-264{border-radius:50px}
.m-265{margin:132px}
.p-265{padding:132px}
.gap-265{gap:132px}
.r-265{border-radius:50px}
.m-266{margin:133px}
.p-266{padding:133px}
.gap-266{gap:133px}
.r-266{border-radius:50px}
.m-267{margin:133px}
.p-267{padding:133px}
.gap-267{gap:133px}
.r-267{border-radius:50px}
.m-268{margin:134px}
.p-268{padding:134px}
.gap-268{gap:134px}
.r-268{border-radius:50px}
.m-269{margin:134px}
.p-269{padding:134px}
.gap-269{gap:134px}
.r-269{border-radius:50px}
.m-270{margin:135px}
.p-270{padding:135px}
.gap-270{gap:135px}
.r-270{border-radius:50px}
.m-271{margin:135px}
.p-271{padding:135px}
.gap-271{gap:135px}
.r-271{border-radius:50px}
.m-272{margin:136px}
.p-272{padding:136px}
.gap-272{gap:136px}
.r-272{border-radius:50px}
.m-273{margin:136px}
.p-273{padding:136px}
.gap-273{gap:136px}
.r-273{border-radius:50px}
.m-274{margin:137px}
.p-274{padding:137px}
.gap-274{gap:137px}
.r-274{border-radius:50px}
.m-275{margin:137px}
.p-275{padding:137px}
.gap-275{gap:137px}
.r-275{border-radius:50px}
.m-276{margin:138px}
.p-276{padding:138px}
.gap-276{gap:138px}
.r-276{border-radius:50px}
.m-277{margin:138px}
.p-277{padding:138px}
.gap-277{gap:138px}
.r-277{border-radius:50px}
.m-278{margin:139px}
.p-278{padding:139px}
.gap-278{gap:139px}
.r-278{border-radius:50px}
.m-279{margin:139px}
.p-279{padding:139px}
.gap-279{gap:139px}
.r-279{border-radius:50px}
.m-280{margin:140px}
.p-280{padding:140px}
.gap-280{gap:140px}
.r-280{border-radius:50px}
.m-281{margin:140px}
.p-281{padding:140px}
.gap-281{gap:140px}
.r-281{border-radius:50px}
.m-282{margin:141px}
.p-282{padding:141px}
.gap-282{gap:141px}
.r-282{border-radius:50px}
.m-283{margin:141px}
.p-283{padding:141px}
.gap-283{gap:141px}
.r-283{border-radius:50px}
.m-284{margin:142px}
.p-284{padding:142px}
.gap-284{gap:142px}
.r-284{border-radius:50px}
.m-285{margin:142px}
.p-285{padding:142px}
.gap-285{gap:142px}
.r-285{border-radius:50px}
.m-286{margin:143px}
.p-286{padding:143px}
.gap-286{gap:143px}
.r-286{border-radius:50px}
.m-287{margin:143px}
.p-287{padding:143px}
.gap-287{gap:143px}
.r-287{border-radius:50px}
.m-288{margin:144px}
.p-288{padding:144px}
.gap-288{gap:144px}
.r-288{border-radius:50px}
.m-289{margin:144px}
.p-289{padding:144px}
.gap-289{gap:144px}
.r-289{border-radius:50px}
.m-290{margin:145px}
.p-290{padding:145px}
.gap-290{gap:145px}
.r-290{border-radius:50px}
.m-291{margin:145px}
.p-291{padding:145px}
.gap-291{gap:145px}
.r-291{border-radius:50px}
.m-292{margin:146px}
.p-292{padding:146px}
.gap-292{gap:146px}
.r-292{border-radius:50px}
.m-293{margin:146px}
.p-293{padding:146px}
.gap-293{gap:146px}
.r-293{border-radius:50px}
.m-294{margin:147px}
.p-294{padding:147px}
.gap-294{gap:147px}
.r-294{border-radius:50px}
.m-295{margin:147px}
.p-295{padding:147px}
.gap-295{gap:147px}
.r-295{border-radius:50px}
.m-296{margin:148px}
.p-296{padding:148px}
.gap-296{gap:148px}
.r-296{border-radius:50px}
.m-297{margin:148px}
.p-297{padding:148px}
.gap-297{gap:148px}
.r-297{border-radius:50px}
.m-298{margin:149px}
.p-298{padding:149px}
.gap-298{gap:149px}
.r-298{border-radius:50px}
.m-299{margin:149px}
.p-299{padding:149px}
.gap-299{gap:149px}
.r-299{border-radius:50px}
.m-300{margin:150px}
.p-300{padding:150px}
.gap-300{gap:150px}
.r-300{border-radius:50px}
.m-301{margin:150px}
.p-301{padding:150px}
.gap-301{gap:150px}
.r-301{border-radius:50px}
.m-302{margin:151px}
.p-302{padding:151px}
.gap-302{gap:151px}
.r-302{border-radius:50px}
.m-303{margin:151px}
.p-303{padding:151px}
.gap-303{gap:151px}
.r-303{border-radius:50px}
.m-304{margin:152px}
.p-304{padding:152px}
.gap-304{gap:152px}
.r-304{border-radius:50px}
.m-305{margin:152px}
.p-305{padding:152px}
.gap-305{gap:152px}
.r-305{border-radius:50px}
.m-306{margin:153px}
.p-306{padding:153px}
.gap-306{gap:153px}
.r-306{border-radius:50px}
.m-307{margin:153px}
.p-307{padding:153px}
.gap-307{gap:153px}
.r-307{border-radius:50px}
.m-308{margin:154px}
.p-308{padding:154px}
.gap-308{gap:154px}
.r-308{border-radius:50px}
.m-309{margin:154px}
.p-309{padding:154px}
.gap-309{gap:154px}
.r-309{border-radius:50px}
.m-310{margin:155px}
.p-310{padding:155px}
.gap-310{gap:155px}
.r-310{border-radius:50px}
.m-311{margin:155px}
.p-311{padding:155px}
.gap-311{gap:155px}
.r-311{border-radius:50px}
.m-312{margin:156px}
.p-312{padding:156px}
.gap-312{gap:156px}
.r-312{border-radius:50px}
.m-313{margin:156px}
.p-313{padding:156px}
.gap-313{gap:156px}
.r-313{border-radius:50px}
.m-314{margin:157px}
.p-314{padding:157px}
.gap-314{gap:157px}
.r-314{border-radius:50px}
.m-315{margin:157px}
.p-315{padding:157px}
.gap-315{gap:157px}
.r-315{border-radius:50px}
.m-316{margin:158px}
.p-316{padding:158px}
.gap-316{gap:158px}
.r-316{border-radius:50px}
.m-317{margin:158px}
.p-317{padding:158px}
.gap-317{gap:158px}
.r-317{border-radius:50px}
.m-318{margin:159px}
.p-318{padding:159px}
.gap-318{gap:159px}
.r-318{border-radius:50px}
.m-319{margin:159px}
.p-319{padding:159px}
.gap-319{gap:159px}
.r-319{border-radius:50px}
.m-320{margin:160px}
.p-320{padding:160px}
.gap-320{gap:160px}
.r-320{border-radius:50px}
.m-321{margin:160px}
.p-321{padding:160px}
.gap-321{gap:160px}
.r-321{border-radius:50px}
.m-322{margin:161px}
.p-322{padding:161px}
.gap-322{gap:161px}
.r-322{border-radius:50px}
.m-323{margin:161px}
.p-323{padding:161px}
.gap-323{gap:161px}
.r-323{border-radius:50px}
.m-324{margin:162px}
.p-324{padding:162px}
.gap-324{gap:162px}
.r-324{border-radius:50px}
.m-325{margin:162px}
.p-325{padding:162px}
.gap-325{gap:162px}
.r-325{border-radius:50px}
.m-326{margin:163px}
.p-326{padding:163px}
.gap-326{gap:163px}
.r-326{border-radius:50px}
.m-327{margin:163px}
.p-327{padding:163px}
.gap-327{gap:163px}
.r-327{border-radius:50px}
.m-328{margin:164px}
.p-328{padding:164px}
.gap-328{gap:164px}
.r-328{border-radius:50px}
.m-329{margin:164px}
.p-329{padding:164px}
.gap-329{gap:164px}
.r-329{border-radius:50px}
.m-330{margin:165px}
.p-330{padding:165px}
.gap-330{gap:165px}
.r-330{border-radius:50px}
.m-331{margin:165px}
.p-331{padding:165px}
.gap-331{gap:165px}
.r-331{border-radius:50px}
.m-332{margin:166px}
.p-332{padding:166px}
.gap-332{gap:166px}
.r-332{border-radius:50px}
.m-333{margin:166px}
.p-333{padding:166px}
.gap-333{gap:166px}
.r-333{border-radius:50px}
.m-334{margin:167px}
.p-334{padding:167px}
.gap-334{gap:167px}
.r-334{border-radius:50px}
.m-335{margin:167px}
.p-335{padding:167px}
.gap-335{gap:167px}
.r-335{border-radius:50px}
.m-336{margin:168px}
.p-336{padding:168px}
.gap-336{gap:168px}
.r-336{border-radius:50px}
.m-337{margin:168px}
.p-337{padding:168px}
.gap-337{gap:168px}
.r-337{border-radius:50px}
.m-338{margin:169px}
.p-338{padding:169px}
.gap-338{gap:169px}
.r-338{border-radius:50px}
.m-339{margin:169px}
.p-339{padding:169px}
.gap-339{gap:169px}
.r-339{border-radius:50px}
.m-340{margin:170px}
.p-340{padding:170px}
.gap-340{gap:170px}
.r-340{border-radius:50px}
.m-341{margin:170px}
.p-341{padding:170px}
.gap-341{gap:170px}
.r-341{border-radius:50px}
.m-342{margin:171px}
.p-342{padding:171px}
.gap-342{gap:171px}
.r-342{border-radius:50px}
.m-343{margin:171px}
.p-343{padding:171px}
.gap-343{gap:171px}
.r-343{border-radius:50px}
.m-344{margin:172px}
.p-344{padding:172px}
.gap-344{gap:172px}
.r-344{border-radius:50px}
.m-345{margin:172px}
.p-345{padding:172px}
.gap-345{gap:172px}
.r-345{border-radius:50px}
.m-346{margin:173px}
.p-346{padding:173px}
.gap-346{gap:173px}
.r-346{border-radius:50px}
.m-347{margin:173px}
.p-347{padding:173px}
.gap-347{gap:173px}
.r-347{border-radius:50px}
.m-348{margin:174px}
.p-348{padding:174px}
.gap-348{gap:174px}
.r-348{border-radius:50px}
.m-349{margin:174px}
.p-349{padding:174px}
.gap-349{gap:174px}
.r-349{border-radius:50px}
.m-350{margin:175px}
.p-350{padding:175px}
.gap-350{gap:175px}
.r-350{border-radius:50px}
.m-351{margin:175px}
.p-351{padding:175px}
.gap-351{gap:175px}
.r-351{border-radius:50px}
.m-352{margin:176px}
.p-352{padding:176px}
.gap-352{gap:176px}
.r-352{border-radius:50px}
.m-353{margin:176px}
.p-353{padding:176px}
.gap-353{gap:176px}
.r-353{border-radius:50px}
.m-354{margin:177px}
.p-354{padding:177px}
.gap-354{gap:177px}
.r-354{border-radius:50px}
.m-355{margin:177px}
.p-355{padding:177px}
.gap-355{gap:177px}
.r-355{border-radius:50px}
.m-356{margin:178px}
.p-356{padding:178px}
.gap-356{gap:178px}
.r-356{border-radius:50px}
.m-357{margin:178px}
.p-357{padding:178px}
.gap-357{gap:178px}
.r-357{border-radius:50px}
.m-358{margin:179px}
.p-358{padding:179px}
.gap-358{gap:179px}
.r-358{border-radius:50px}
.m-359{margin:179px}
.p-359{padding:179px}
.gap-359{gap:179px}
.r-359{border-radius:50px}
.m-360{margin:180px}
.p-360{padding:180px}
.gap-360{gap:180px}
.r-360{border-radius:50px}
.m-361{margin:180px}
.p-361{padding:180px}
.gap-361{gap:180px}
.r-361{border-radius:50px}
.m-362{margin:181px}
.p-362{padding:181px}
.gap-362{gap:181px}
.r-362{border-radius:50px}
.m-363{margin:181px}
.p-363{padding:181px}
.gap-363{gap:181px}
.r-363{border-radius:50px}
.m-364{margin:182px}
.p-364{padding:182px}
.gap-364{gap:182px}
.r-364{border-radius:50px}
.m-365{margin:182px}
.p-365{padding:182px}
.gap-365{gap:182px}
.r-365{border-radius:50px}
.m-366{margin:183px}
.p-366{padding:183px}
.gap-366{gap:183px}
.r-366{border-radius:50px}
.m-367{margin:183px}
.p-367{padding:183px}
.gap-367{gap:183px}
.r-367{border-radius:50px}
.m-368{margin:184px}
.p-368{padding:184px}
.gap-368{gap:184px}
.r-368{border-radius:50px}
.m-369{margin:184px}
.p-369{padding:184px}
.gap-369{gap:184px}
.r-369{border-radius:50px}
.m-370{margin:185px}
.p-370{padding:185px}
.gap-370{gap:185px}
.r-370{border-radius:50px}
.m-371{margin:185px}
.p-371{padding:185px}
.gap-371{gap:185px}
.r-371{border-radius:50px}
.m-372{margin:186px}
.p-372{padding:186px}
.gap-372{gap:186px}
.r-372{border-radius:50px}
.m-373{margin:186px}
.p-373{padding:186px}
.gap-373{gap:186px}
.r-373{border-radius:50px}
.m-374{margin:187px}
.p-374{padding:187px}
.gap-374{gap:187px}
.r-374{border-radius:50px}
.m-375{margin:187px}
.p-375{padding:187px}
.gap-375{gap:187px}
.r-375{border-radius:50px}
.m-376{margin:188px}
.p-376{padding:188px}
.gap-376{gap:188px}
.r-376{border-radius:50px}
.m-377{margin:188px}
.p-377{padding:188px}
.gap-377{gap:188px}
.r-377{border-radius:50px}
.m-378{margin:189px}
.p-378{padding:189px}
.gap-378{gap:189px}
.r-378{border-radius:50px}
.m-379{margin:189px}
.p-379{padding:189px}
.gap-379{gap:189px}
.r-379{border-radius:50px}
.m-380{margin:190px}
.p-380{padding:190px}
.gap-380{gap:190px}
.r-380{border-radius:50px}
.m-381{margin:190px}
.p-381{padding:190px}
.gap-381{gap:190px}
.r-381{border-radius:50px}
.m-382{margin:191px}
.p-382{padding:191px}
.gap-382{gap:191px}
.r-382{border-radius:50px}
.m-383{margin:191px}
.p-383{padding:191px}
.gap-383{gap:191px}
.r-383{border-radius:50px}
.m-384{margin:192px}
.p-384{padding:192px}
.gap-384{gap:192px}
.r-384{border-radius:50px}
.m-385{margin:192px}
.p-385{padding:192px}
.gap-385{gap:192px}
.r-385{border-radius:50px}
.m-386{margin:193px}
.p-386{padding:193px}
.gap-386{gap:193px}
.r-386{border-radius:50px}
.m-387{margin:193px}
.p-387{padding:193px}
.gap-387{gap:193px}
.r-387{border-radius:50px}
.m-388{margin:194px}
.p-388{padding:194px}
.gap-388{gap:194px}
.r-388{border-radius:50px}
.m-389{margin:194px}
.p-389{padding:194px}
.gap-389{gap:194px}
.r-389{border-radius:50px}
.m-390{margin:195px}
.p-390{padding:195px}
.gap-390{gap:195px}
.r-390{border-radius:50px}
.m-391{margin:195px}
.p-391{padding:195px}
.gap-391{gap:195px}
.r-391{border-radius:50px}
.m-392{margin:196px}
.p-392{padding:196px}
.gap-392{gap:196px}
.r-392{border-radius:50px}
.m-393{margin:196px}
.p-393{padding:196px}
.gap-393{gap:196px}
.r-393{border-radius:50px}
.m-394{margin:197px}
.p-394{padding:197px}
.gap-394{gap:197px}
.r-394{border-radius:50px}
.m-395{margin:197px}
.p-395{padding:197px}
.gap-395{gap:197px}
.r-395{border-radius:50px}
.m-396{margin:198px}
.p-396{padding:198px}
.gap-396{gap:198px}
.r-396{border-radius:50px}
.m-397{margin:198px}
.p-397{padding:198px}
.gap-397{gap:198px}
.r-397{border-radius:50px}
.m-398{margin:199px}
.p-398{padding:199px}
.gap-398{gap:199px}
.r-398{border-radius:50px}
.m-399{margin:199px}
.p-399{padding:199px}
.gap-399{gap:199px}
.r-399{border-radius:50px}
.m-400{margin:200px}
.p-400{padding:200px}
.gap-400{gap:200px}
.r-400{border-radius:50px}
.m-401{margin:200px}
.p-401{padding:200px}
.gap-401{gap:200px}
.r-401{border-radius:50px}
.m-402{margin:201px}
.p-402{padding:201px}
.gap-402{gap:201px}
.r-402{border-radius:50px}
.m-403{margin:201px}
.p-403{padding:201px}
.gap-403{gap:201px}
.r-403{border-radius:50px}
.m-404{margin:202px}
.p-404{padding:202px}
.gap-404{gap:202px}
.r-404{border-radius:50px}
.m-405{margin:202px}
.p-405{padding:202px}
.gap-405{gap:202px}
.r-405{border-radius:50px}
.m-406{margin:203px}
.p-406{padding:203px}
.gap-406{gap:203px}
.r-406{border-radius:50px}
.m-407{margin:203px}
.p-407{padding:203px}
.gap-407{gap:203px}
.r-407{border-radius:50px}
.m-408{margin:204px}
.p-408{padding:204px}
.gap-408{gap:204px}
.r-408{border-radius:50px}
.m-409{margin:204px}
.p-409{padding:204px}
.gap-409{gap:204px}
.r-409{border-radius:50px}
.m-410{margin:205px}
.p-410{padding:205px}
.gap-410{gap:205px}
.r-410{border-radius:50px}
.m-411{margin:205px}
.p-411{padding:205px}
.gap-411{gap:205px}
.r-411{border-radius:50px}
.m-412{margin:206px}
.p-412{padding:206px}
.gap-412{gap:206px}
.r-412{border-radius:50px}
.m-413{margin:206px}
.p-413{padding:206px}
.gap-413{gap:206px}
.r-413{border-radius:50px}
.m-414{margin:207px}
.p-414{padding:207px}
.gap-414{gap:207px}
.r-414{border-radius:50px}
.m-415{margin:207px}
.p-415{padding:207px}
.gap-415{gap:207px}
.r-415{border-radius:50px}
.m-416{margin:208px}
.p-416{padding:208px}
.gap-416{gap:208px}
.r-416{border-radius:50px}
.m-417{margin:208px}
.p-417{padding:208px}
.gap-417{gap:208px}
.r-417{border-radius:50px}
.m-418{margin:209px}
.p-418{padding:209px}
.gap-418{gap:209px}
.r-418{border-radius:50px}
.m-419{margin:209px}
.p-419{padding:209px}
.gap-419{gap:209px}
.r-419{border-radius:50px}
.m-420{margin:210px}
.p-420{padding:210px}
.gap-420{gap:210px}
.r-420{border-radius:50px}
.m-421{margin:210px}
.p-421{padding:210px}
.gap-421{gap:210px}
.r-421{border-radius:50px}
.m-422{margin:211px}
.p-422{padding:211px}
.gap-422{gap:211px}
.r-422{border-radius:50px}
.m-423{margin:211px}
.p-423{padding:211px}
.gap-423{gap:211px}
.r-423{border-radius:50px}
.m-424{margin:212px}
.p-424{padding:212px}
.gap-424{gap:212px}
.r-424{border-radius:50px}
.m-425{margin:212px}
.p-425{padding:212px}
.gap-425{gap:212px}
.r-425{border-radius:50px}
.m-426{margin:213px}
.p-426{padding:213px}
.gap-426{gap:213px}
.r-426{border-radius:50px}
.m-427{margin:213px}
.p-427{padding:213px}
.gap-427{gap:213px}
.r-427{border-radius:50px}
.m-428{margin:214px}
.p-428{padding:214px}
.gap-428{gap:214px}
.r-428{border-radius:50px}
.m-429{margin:214px}
.p-429{padding:214px}
.gap-429{gap:214px}
.r-429{border-radius:50px}
.m-430{margin:215px}
.p-430{padding:215px}
.gap-430{gap:215px}
.r-430{border-radius:50px}
.m-431{margin:215px}
.p-431{padding:215px}
.gap-431{gap:215px}
.r-431{border-radius:50px}
.m-432{margin:216px}
.p-432{padding:216px}
.gap-432{gap:216px}
.r-432{border-radius:50px}
.m-433{margin:216px}
.p-433{padding:216px}
.gap-433{gap:216px}
.r-433{border-radius:50px}
.m-434{margin:217px}
.p-434{padding:217px}
.gap-434{gap:217px}
.r-434{border-radius:50px}
.m-435{margin:217px}
.p-435{padding:217px}
.gap-435{gap:217px}
.r-435{border-radius:50px}
.m-436{margin:218px}
.p-436{padding:218px}
.gap-436{gap:218px}
.r-436{border-radius:50px}
.m-437{margin:218px}
.p-437{padding:218px}
.gap-437{gap:218px}
.r-437{border-radius:50px}
.m-438{margin:219px}
.p-438{padding:219px}
.gap-438{gap:219px}
.r-438{border-radius:50px}
.m-439{margin:219px}
.p-439{padding:219px}
.gap-439{gap:219px}
.r-439{border-radius:50px}
.m-440{margin:220px}
.p-440{padding:220px}
.gap-440{gap:220px}
.r-440{border-radius:50px}
.m-441{margin:220px}
.p-441{padding:220px}
.gap-441{gap:220px}
.r-441{border-radius:50px}
.m-442{margin:221px}
.p-442{padding:221px}
.gap-442{gap:221px}
.r-442{border-radius:50px}
.m-443{margin:221px}
.p-443{padding:221px}
.gap-443{gap:221px}
.r-443{border-radius:50px}
.m-444{margin:222px}
.p-444{padding:222px}
.gap-444{gap:222px}
.r-444{border-radius:50px}
.m-445{margin:222px}
.p-445{padding:222px}
.gap-445{gap:222px}
.r-445{border-radius:50px}
.m-446{margin:223px}
.p-446{padding:223px}
.gap-446{gap:223px}
.r-446{border-radius:50px}
.m-447{margin:223px}
.p-447{padding:223px}
.gap-447{gap:223px}
.r-447{border-radius:50px}
.m-448{margin:224px}
.p-448{padding:224px}
.gap-448{gap:224px}
.r-448{border-radius:50px}
.m-449{margin:224px}
.p-449{padding:224px}
.gap-449{gap:224px}
.r-449{border-radius:50px}
.m-450{margin:225px}
.p-450{padding:225px}
.gap-450{gap:225px}
.r-450{border-radius:50px}
.m-451{margin:225px}
.p-451{padding:225px}
.gap-451{gap:225px}
.r-451{border-radius:50px}
.m-452{margin:226px}
.p-452{padding:226px}
.gap-452{gap:226px}
.r-452{border-radius:50px}
.m-453{margin:226px}
.p-453{padding:226px}
.gap-453{gap:226px}
.r-453{border-radius:50px}
.m-454{margin:227px}
.p-454{padding:227px}
.gap-454{gap:227px}
.r-454{border-radius:50px}
.m-455{margin:227px}
.p-455{padding:227px}
.gap-455{gap:227px}
.r-455{border-radius:50px}
.m-456{margin:228px}
.p-456{padding:228px}
.gap-456{gap:228px}
.r-456{border-radius:50px}
.m-457{margin:228px}
.p-457{padding:228px}
.gap-457{gap:228px}
.r-457{border-radius:50px}
.m-458{margin:229px}
.p-458{padding:229px}
.gap-458{gap:229px}
.r-458{border-radius:50px}
.m-459{margin:229px}
.p-459{padding:229px}
.gap-459{gap:229px}
.r-459{border-radius:50px}
.m-460{margin:230px}
.p-460{padding:230px}
.gap-460{gap:230px}
.r-460{border-radius:50px}
.m-461{margin:230px}
.p-461{padding:230px}
.gap-461{gap:230px}
.r-461{border-radius:50px}
.m-462{margin:231px}
.p-462{padding:231px}
.gap-462{gap:231px}
.r-462{border-radius:50px}
.m-463{margin:231px}
.p-463{padding:231px}
.gap-463{gap:231px}
.r-463{border-radius:50px}
.m-464{margin:232px}
.p-464{padding:232px}
.gap-464{gap:232px}
.r-464{border-radius:50px}
.m-465{margin:232px}
.p-465{padding:232px}
.gap-465{gap:232px}
.r-465{border-radius:50px}
.m-466{margin:233px}
.p-466{padding:233px}
.gap-466{gap:233px}
.r-466{border-radius:50px}
.m-467{margin:233px}
.p-467{padding:233px}
.gap-467{gap:233px}
.r-467{border-radius:50px}
.m-468{margin:234px}
.p-468{padding:234px}
.gap-468{gap:234px}
.r-468{border-radius:50px}
.m-469{margin:234px}
.p-469{padding:234px}
.gap-469{gap:234px}
.r-469{border-radius:50px}
.m-470{margin:235px}
.p-470{padding:235px}
.gap-470{gap:235px}
.r-470{border-radius:50px}
.m-471{margin:235px}
.p-471{padding:235px}
.gap-471{gap:235px}
.r-471{border-radius:50px}
.m-472{margin:236px}
.p-472{padding:236px}
.gap-472{gap:236px}
.r-472{border-radius:50px}
.m-473{margin:236px}
.p-473{padding:236px}
.gap-473{gap:236px}
.r-473{border-radius:50px}
.m-474{margin:237px}
.p-474{padding:237px}
.gap-474{gap:237px}
.r-474{border-radius:50px}
.m-475{margin:237px}
.p-475{padding:237px}
.gap-475{gap:237px}
.r-475{border-radius:50px}
.m-476{margin:238px}
.p-476{padding:238px}
.gap-476{gap:238px}
.r-476{border-radius:50px}
.m-477{margin:238px}
.p-477{padding:238px}
.gap-477{gap:238px}
.r-477{border-radius:50px}
.m-478{margin:239px}
.p-478{padding:239px}
.gap-478{gap:239px}
.r-478{border-radius:50px}
.m-479{margin:239px}
.p-479{padding:239px}
.gap-479{gap:239px}
.r-479{border-radius:50px}
.m-480{margin:240px}
.p-480{padding:240px}
.gap-480{gap:240px}
.r-480{border-radius:50px}
.m-481{margin:240px}
.p-481{padding:240px}
.gap-481{gap:240px}
.r-481{border-radius:50px}
.m-482{margin:241px}
.p-482{padding:241px}
.gap-482{gap:241px}
.r-482{border-radius:50px}
.m-483{margin:241px}
.p-483{padding:241px}
.gap-483{gap:241px}
.r-483{border-radius:50px}
.m-484{margin:242px}
.p-484{padding:242px}
.gap-484{gap:242px}
.r-484{border-radius:50px}
.m-485{margin:242px}
.p-485{padding:242px}
.gap-485{gap:242px}
.r-485{border-radius:50px}
.m-486{margin:243px}
.p-486{padding:243px}
.gap-486{gap:243px}
.r-486{border-radius:50px}
.m-487{margin:243px}
.p-487{padding:243px}
.gap-487{gap:243px}
.r-487{border-radius:50px}
.m-488{margin:244px}
.p-488{padding:244px}
.gap-488{gap:244px}
.r-488{border-radius:50px}
.m-489{margin:244px}
.p-489{padding:244px}
.gap-489{gap:244px}
.r-489{border-radius:50px}
.m-490{margin:245px}
.p-490{padding:245px}
.gap-490{gap:245px}
.r-490{border-radius:50px}
.m-491{margin:245px}
.p-491{padding:245px}
.gap-491{gap:245px}
.r-491{border-radius:50px}
.m-492{margin:246px}
.p-492{padding:246px}
.gap-492{gap:246px}
.r-492{border-radius:50px}
.m-493{margin:246px}
.p-493{padding:246px}
.gap-493{gap:246px}
.r-493{border-radius:50px}
.m-494{margin:247px}
.p-494{padding:247px}
.gap-494{gap:247px}
.r-494{border-radius:50px}
.m-495{margin:247px}
.p-495{padding:247px}
.gap-495{gap:247px}
.r-495{border-radius:50px}
.m-496{margin:248px}
.p-496{padding:248px}
.gap-496{gap:248px}
.r-496{border-radius:50px}
.m-497{margin:248px}
.p-497{padding:248px}
.gap-497{gap:248px}
.r-497{border-radius:50px}
.m-498{margin:249px}
.p-498{padding:249px}
.gap-498{gap:249px}
.r-498{border-radius:50px}
.m-499{margin:249px}
.p-499{padding:249px}
.gap-499{gap:249px}
.r-499{border-radius:50px}
.m-500{margin:250px}
.p-500{padding:250px}
.gap-500{gap:250px}
.r-500{border-radius:50px}

.compact-mode .card{padding:16px}.compact-mode .content{padding:18px}.compact-mode .list-item,.compact-mode .schedule-card{padding:9px}

@media(max-width:700px){.sm-m-1{margin:1px}.sm-p-1{padding:1px}}
@media(max-width:700px){.sm-m-2{margin:2px}.sm-p-2{padding:2px}}
@media(max-width:700px){.sm-m-3{margin:3px}.sm-p-3{padding:3px}}
@media(max-width:700px){.sm-m-4{margin:4px}.sm-p-4{padding:4px}}
@media(max-width:700px){.sm-m-5{margin:5px}.sm-p-5{padding:5px}}
@media(max-width:700px){.sm-m-6{margin:6px}.sm-p-6{padding:6px}}
@media(max-width:700px){.sm-m-7{margin:7px}.sm-p-7{padding:7px}}
@media(max-width:700px){.sm-m-8{margin:8px}.sm-p-8{padding:8px}}
@media(max-width:700px){.sm-m-9{margin:9px}.sm-p-9{padding:9px}}
@media(max-width:700px){.sm-m-10{margin:10px}.sm-p-10{padding:10px}}
@media(max-width:700px){.sm-m-11{margin:11px}.sm-p-11{padding:11px}}
@media(max-width:700px){.sm-m-12{margin:12px}.sm-p-12{padding:12px}}
@media(max-width:700px){.sm-m-13{margin:13px}.sm-p-13{padding:13px}}
@media(max-width:700px){.sm-m-14{margin:14px}.sm-p-14{padding:14px}}
@media(max-width:700px){.sm-m-15{margin:15px}.sm-p-15{padding:15px}}
@media(max-width:700px){.sm-m-16{margin:16px}.sm-p-16{padding:16px}}
@media(max-width:700px){.sm-m-17{margin:17px}.sm-p-17{padding:17px}}
@media(max-width:700px){.sm-m-18{margin:18px}.sm-p-18{padding:18px}}
@media(max-width:700px){.sm-m-19{margin:19px}.sm-p-19{padding:19px}}
@media(max-width:700px){.sm-m-20{margin:20px}.sm-p-20{padding:20px}}
@media(max-width:700px){.sm-m-21{margin:10px}.sm-p-21{padding:10px}}
@media(max-width:700px){.sm-m-22{margin:11px}.sm-p-22{padding:11px}}
@media(max-width:700px){.sm-m-23{margin:11px}.sm-p-23{padding:11px}}
@media(max-width:700px){.sm-m-24{margin:12px}.sm-p-24{padding:12px}}
@media(max-width:700px){.sm-m-25{margin:12px}.sm-p-25{padding:12px}}
@media(max-width:700px){.sm-m-26{margin:13px}.sm-p-26{padding:13px}}
@media(max-width:700px){.sm-m-27{margin:13px}.sm-p-27{padding:13px}}
@media(max-width:700px){.sm-m-28{margin:14px}.sm-p-28{padding:14px}}
@media(max-width:700px){.sm-m-29{margin:14px}.sm-p-29{padding:14px}}
@media(max-width:700px){.sm-m-30{margin:15px}.sm-p-30{padding:15px}}
@media(max-width:700px){.sm-m-31{margin:15px}.sm-p-31{padding:15px}}
@media(max-width:700px){.sm-m-32{margin:16px}.sm-p-32{padding:16px}}
@media(max-width:700px){.sm-m-33{margin:16px}.sm-p-33{padding:16px}}
@media(max-width:700px){.sm-m-34{margin:17px}.sm-p-34{padding:17px}}
@media(max-width:700px){.sm-m-35{margin:17px}.sm-p-35{padding:17px}}
@media(max-width:700px){.sm-m-36{margin:18px}.sm-p-36{padding:18px}}
@media(max-width:700px){.sm-m-37{margin:18px}.sm-p-37{padding:18px}}
@media(max-width:700px){.sm-m-38{margin:19px}.sm-p-38{padding:19px}}
@media(max-width:700px){.sm-m-39{margin:19px}.sm-p-39{padding:19px}}
@media(max-width:700px){.sm-m-40{margin:20px}.sm-p-40{padding:20px}}
@media(max-width:700px){.sm-m-41{margin:20px}.sm-p-41{padding:20px}}
@media(max-width:700px){.sm-m-42{margin:21px}.sm-p-42{padding:21px}}
@media(max-width:700px){.sm-m-43{margin:21px}.sm-p-43{padding:21px}}
@media(max-width:700px){.sm-m-44{margin:22px}.sm-p-44{padding:22px}}
@media(max-width:700px){.sm-m-45{margin:22px}.sm-p-45{padding:22px}}
@media(max-width:700px){.sm-m-46{margin:23px}.sm-p-46{padding:23px}}
@media(max-width:700px){.sm-m-47{margin:23px}.sm-p-47{padding:23px}}
@media(max-width:700px){.sm-m-48{margin:24px}.sm-p-48{padding:24px}}
@media(max-width:700px){.sm-m-49{margin:24px}.sm-p-49{padding:24px}}
@media(max-width:700px){.sm-m-50{margin:25px}.sm-p-50{padding:25px}}
@media(max-width:700px){.sm-m-51{margin:25px}.sm-p-51{padding:25px}}
@media(max-width:700px){.sm-m-52{margin:26px}.sm-p-52{padding:26px}}
@media(max-width:700px){.sm-m-53{margin:26px}.sm-p-53{padding:26px}}
@media(max-width:700px){.sm-m-54{margin:27px}.sm-p-54{padding:27px}}
@media(max-width:700px){.sm-m-55{margin:27px}.sm-p-55{padding:27px}}
@media(max-width:700px){.sm-m-56{margin:28px}.sm-p-56{padding:28px}}
@media(max-width:700px){.sm-m-57{margin:28px}.sm-p-57{padding:28px}}
@media(max-width:700px){.sm-m-58{margin:29px}.sm-p-58{padding:29px}}
@media(max-width:700px){.sm-m-59{margin:29px}.sm-p-59{padding:29px}}
@media(max-width:700px){.sm-m-60{margin:30px}.sm-p-60{padding:30px}}
@media(max-width:700px){.sm-m-61{margin:30px}.sm-p-61{padding:30px}}
@media(max-width:700px){.sm-m-62{margin:31px}.sm-p-62{padding:31px}}
@media(max-width:700px){.sm-m-63{margin:31px}.sm-p-63{padding:31px}}
@media(max-width:700px){.sm-m-64{margin:32px}.sm-p-64{padding:32px}}
@media(max-width:700px){.sm-m-65{margin:32px}.sm-p-65{padding:32px}}
@media(max-width:700px){.sm-m-66{margin:33px}.sm-p-66{padding:33px}}
@media(max-width:700px){.sm-m-67{margin:33px}.sm-p-67{padding:33px}}
@media(max-width:700px){.sm-m-68{margin:34px}.sm-p-68{padding:34px}}
@media(max-width:700px){.sm-m-69{margin:34px}.sm-p-69{padding:34px}}
@media(max-width:700px){.sm-m-70{margin:35px}.sm-p-70{padding:35px}}
@media(max-width:700px){.sm-m-71{margin:35px}.sm-p-71{padding:35px}}
@media(max-width:700px){.sm-m-72{margin:36px}.sm-p-72{padding:36px}}
@media(max-width:700px){.sm-m-73{margin:36px}.sm-p-73{padding:36px}}
@media(max-width:700px){.sm-m-74{margin:37px}.sm-p-74{padding:37px}}
@media(max-width:700px){.sm-m-75{margin:37px}.sm-p-75{padding:37px}}
@media(max-width:700px){.sm-m-76{margin:38px}.sm-p-76{padding:38px}}
@media(max-width:700px){.sm-m-77{margin:38px}.sm-p-77{padding:38px}}
@media(max-width:700px){.sm-m-78{margin:39px}.sm-p-78{padding:39px}}
@media(max-width:700px){.sm-m-79{margin:39px}.sm-p-79{padding:39px}}
@media(max-width:700px){.sm-m-80{margin:40px}.sm-p-80{padding:40px}}
@media(max-width:700px){.sm-m-81{margin:40px}.sm-p-81{padding:40px}}
@media(max-width:700px){.sm-m-82{margin:41px}.sm-p-82{padding:41px}}
@media(max-width:700px){.sm-m-83{margin:41px}.sm-p-83{padding:41px}}
@media(max-width:700px){.sm-m-84{margin:42px}.sm-p-84{padding:42px}}
@media(max-width:700px){.sm-m-85{margin:42px}.sm-p-85{padding:42px}}
@media(max-width:700px){.sm-m-86{margin:43px}.sm-p-86{padding:43px}}
@media(max-width:700px){.sm-m-87{margin:43px}.sm-p-87{padding:43px}}
@media(max-width:700px){.sm-m-88{margin:44px}.sm-p-88{padding:44px}}
@media(max-width:700px){.sm-m-89{margin:44px}.sm-p-89{padding:44px}}
@media(max-width:700px){.sm-m-90{margin:45px}.sm-p-90{padding:45px}}
@media(max-width:700px){.sm-m-91{margin:45px}.sm-p-91{padding:45px}}
@media(max-width:700px){.sm-m-92{margin:46px}.sm-p-92{padding:46px}}
@media(max-width:700px){.sm-m-93{margin:46px}.sm-p-93{padding:46px}}
@media(max-width:700px){.sm-m-94{margin:47px}.sm-p-94{padding:47px}}
@media(max-width:700px){.sm-m-95{margin:47px}.sm-p-95{padding:47px}}
@media(max-width:700px){.sm-m-96{margin:48px}.sm-p-96{padding:48px}}
@media(max-width:700px){.sm-m-97{margin:48px}.sm-p-97{padding:48px}}
@media(max-width:700px){.sm-m-98{margin:49px}.sm-p-98{padding:49px}}
@media(max-width:700px){.sm-m-99{margin:49px}.sm-p-99{padding:49px}}
@media(max-width:700px){.sm-m-100{margin:50px}.sm-p-100{padding:50px}}
@media(max-width:700px){.sm-m-101{margin:50px}.sm-p-101{padding:50px}}
@media(max-width:700px){.sm-m-102{margin:51px}.sm-p-102{padding:51px}}
@media(max-width:700px){.sm-m-103{margin:51px}.sm-p-103{padding:51px}}
@media(max-width:700px){.sm-m-104{margin:52px}.sm-p-104{padding:52px}}
@media(max-width:700px){.sm-m-105{margin:52px}.sm-p-105{padding:52px}}
@media(max-width:700px){.sm-m-106{margin:53px}.sm-p-106{padding:53px}}
@media(max-width:700px){.sm-m-107{margin:53px}.sm-p-107{padding:53px}}
@media(max-width:700px){.sm-m-108{margin:54px}.sm-p-108{padding:54px}}
@media(max-width:700px){.sm-m-109{margin:54px}.sm-p-109{padding:54px}}
@media(max-width:700px){.sm-m-110{margin:55px}.sm-p-110{padding:55px}}
@media(max-width:700px){.sm-m-111{margin:55px}.sm-p-111{padding:55px}}
@media(max-width:700px){.sm-m-112{margin:56px}.sm-p-112{padding:56px}}
@media(max-width:700px){.sm-m-113{margin:56px}.sm-p-113{padding:56px}}
@media(max-width:700px){.sm-m-114{margin:57px}.sm-p-114{padding:57px}}
@media(max-width:700px){.sm-m-115{margin:57px}.sm-p-115{padding:57px}}
@media(max-width:700px){.sm-m-116{margin:58px}.sm-p-116{padding:58px}}
@media(max-width:700px){.sm-m-117{margin:58px}.sm-p-117{padding:58px}}
@media(max-width:700px){.sm-m-118{margin:59px}.sm-p-118{padding:59px}}
@media(max-width:700px){.sm-m-119{margin:59px}.sm-p-119{padding:59px}}
@media(max-width:700px){.sm-m-120{margin:60px}.sm-p-120{padding:60px}}
@media(max-width:700px){.sm-m-121{margin:60px}.sm-p-121{padding:60px}}
@media(max-width:700px){.sm-m-122{margin:61px}.sm-p-122{padding:61px}}
@media(max-width:700px){.sm-m-123{margin:61px}.sm-p-123{padding:61px}}
@media(max-width:700px){.sm-m-124{margin:62px}.sm-p-124{padding:62px}}
@media(max-width:700px){.sm-m-125{margin:62px}.sm-p-125{padding:62px}}
@media(max-width:700px){.sm-m-126{margin:63px}.sm-p-126{padding:63px}}
@media(max-width:700px){.sm-m-127{margin:63px}.sm-p-127{padding:63px}}
@media(max-width:700px){.sm-m-128{margin:64px}.sm-p-128{padding:64px}}
@media(max-width:700px){.sm-m-129{margin:64px}.sm-p-129{padding:64px}}
@media(max-width:700px){.sm-m-130{margin:65px}.sm-p-130{padding:65px}}
@media(max-width:700px){.sm-m-131{margin:65px}.sm-p-131{padding:65px}}
@media(max-width:700px){.sm-m-132{margin:66px}.sm-p-132{padding:66px}}
@media(max-width:700px){.sm-m-133{margin:66px}.sm-p-133{padding:66px}}
@media(max-width:700px){.sm-m-134{margin:67px}.sm-p-134{padding:67px}}
@media(max-width:700px){.sm-m-135{margin:67px}.sm-p-135{padding:67px}}
@media(max-width:700px){.sm-m-136{margin:68px}.sm-p-136{padding:68px}}
@media(max-width:700px){.sm-m-137{margin:68px}.sm-p-137{padding:68px}}
@media(max-width:700px){.sm-m-138{margin:69px}.sm-p-138{padding:69px}}
@media(max-width:700px){.sm-m-139{margin:69px}.sm-p-139{padding:69px}}
@media(max-width:700px){.sm-m-140{margin:70px}.sm-p-140{padding:70px}}
@media(max-width:700px){.sm-m-141{margin:70px}.sm-p-141{padding:70px}}
@media(max-width:700px){.sm-m-142{margin:71px}.sm-p-142{padding:71px}}
@media(max-width:700px){.sm-m-143{margin:71px}.sm-p-143{padding:71px}}
@media(max-width:700px){.sm-m-144{margin:72px}.sm-p-144{padding:72px}}
@media(max-width:700px){.sm-m-145{margin:72px}.sm-p-145{padding:72px}}
@media(max-width:700px){.sm-m-146{margin:73px}.sm-p-146{padding:73px}}
@media(max-width:700px){.sm-m-147{margin:73px}.sm-p-147{padding:73px}}
@media(max-width:700px){.sm-m-148{margin:74px}.sm-p-148{padding:74px}}
@media(max-width:700px){.sm-m-149{margin:74px}.sm-p-149{padding:74px}}
@media(max-width:700px){.sm-m-150{margin:75px}.sm-p-150{padding:75px}}
@media(max-width:700px){.sm-m-151{margin:75px}.sm-p-151{padding:75px}}
@media(max-width:700px){.sm-m-152{margin:76px}.sm-p-152{padding:76px}}
@media(max-width:700px){.sm-m-153{margin:76px}.sm-p-153{padding:76px}}
@media(max-width:700px){.sm-m-154{margin:77px}.sm-p-154{padding:77px}}
@media(max-width:700px){.sm-m-155{margin:77px}.sm-p-155{padding:77px}}
@media(max-width:700px){.sm-m-156{margin:78px}.sm-p-156{padding:78px}}
@media(max-width:700px){.sm-m-157{margin:78px}.sm-p-157{padding:78px}}
@media(max-width:700px){.sm-m-158{margin:79px}.sm-p-158{padding:79px}}
@media(max-width:700px){.sm-m-159{margin:79px}.sm-p-159{padding:79px}}
@media(max-width:700px){.sm-m-160{margin:80px}.sm-p-160{padding:80px}}
@media(max-width:700px){.sm-m-161{margin:80px}.sm-p-161{padding:80px}}
@media(max-width:700px){.sm-m-162{margin:81px}.sm-p-162{padding:81px}}
@media(max-width:700px){.sm-m-163{margin:81px}.sm-p-163{padding:81px}}
@media(max-width:700px){.sm-m-164{margin:82px}.sm-p-164{padding:82px}}
@media(max-width:700px){.sm-m-165{margin:82px}.sm-p-165{padding:82px}}
@media(max-width:700px){.sm-m-166{margin:83px}.sm-p-166{padding:83px}}
@media(max-width:700px){.sm-m-167{margin:83px}.sm-p-167{padding:83px}}
@media(max-width:700px){.sm-m-168{margin:84px}.sm-p-168{padding:84px}}
@media(max-width:700px){.sm-m-169{margin:84px}.sm-p-169{padding:84px}}
@media(max-width:700px){.sm-m-170{margin:85px}.sm-p-170{padding:85px}}
@media(max-width:700px){.sm-m-171{margin:85px}.sm-p-171{padding:85px}}
@media(max-width:700px){.sm-m-172{margin:86px}.sm-p-172{padding:86px}}
@media(max-width:700px){.sm-m-173{margin:86px}.sm-p-173{padding:86px}}
@media(max-width:700px){.sm-m-174{margin:87px}.sm-p-174{padding:87px}}
@media(max-width:700px){.sm-m-175{margin:87px}.sm-p-175{padding:87px}}
@media(max-width:700px){.sm-m-176{margin:88px}.sm-p-176{padding:88px}}
@media(max-width:700px){.sm-m-177{margin:88px}.sm-p-177{padding:88px}}
@media(max-width:700px){.sm-m-178{margin:89px}.sm-p-178{padding:89px}}
@media(max-width:700px){.sm-m-179{margin:89px}.sm-p-179{padding:89px}}
@media(max-width:700px){.sm-m-180{margin:90px}.sm-p-180{padding:90px}}
@media(max-width:700px){.sm-m-181{margin:90px}.sm-p-181{padding:90px}}
@media(max-width:700px){.sm-m-182{margin:91px}.sm-p-182{padding:91px}}
@media(max-width:700px){.sm-m-183{margin:91px}.sm-p-183{padding:91px}}
@media(max-width:700px){.sm-m-184{margin:92px}.sm-p-184{padding:92px}}
@media(max-width:700px){.sm-m-185{margin:92px}.sm-p-185{padding:92px}}
@media(max-width:700px){.sm-m-186{margin:93px}.sm-p-186{padding:93px}}
@media(max-width:700px){.sm-m-187{margin:93px}.sm-p-187{padding:93px}}
@media(max-width:700px){.sm-m-188{margin:94px}.sm-p-188{padding:94px}}
@media(max-width:700px){.sm-m-189{margin:94px}.sm-p-189{padding:94px}}
@media(max-width:700px){.sm-m-190{margin:95px}.sm-p-190{padding:95px}}
@media(max-width:700px){.sm-m-191{margin:95px}.sm-p-191{padding:95px}}
@media(max-width:700px){.sm-m-192{margin:96px}.sm-p-192{padding:96px}}
@media(max-width:700px){.sm-m-193{margin:96px}.sm-p-193{padding:96px}}
@media(max-width:700px){.sm-m-194{margin:97px}.sm-p-194{padding:97px}}
@media(max-width:700px){.sm-m-195{margin:97px}.sm-p-195{padding:97px}}
@media(max-width:700px){.sm-m-196{margin:98px}.sm-p-196{padding:98px}}
@media(max-width:700px){.sm-m-197{margin:98px}.sm-p-197{padding:98px}}
@media(max-width:700px){.sm-m-198{margin:99px}.sm-p-198{padding:99px}}
@media(max-width:700px){.sm-m-199{margin:99px}.sm-p-199{padding:99px}}
@media(max-width:700px){.sm-m-200{margin:100px}.sm-p-200{padding:100px}}
@media(max-width:700px){.sm-m-201{margin:100px}.sm-p-201{padding:100px}}
@media(max-width:700px){.sm-m-202{margin:101px}.sm-p-202{padding:101px}}
@media(max-width:700px){.sm-m-203{margin:101px}.sm-p-203{padding:101px}}
@media(max-width:700px){.sm-m-204{margin:102px}.sm-p-204{padding:102px}}
@media(max-width:700px){.sm-m-205{margin:102px}.sm-p-205{padding:102px}}
@media(max-width:700px){.sm-m-206{margin:103px}.sm-p-206{padding:103px}}
@media(max-width:700px){.sm-m-207{margin:103px}.sm-p-207{padding:103px}}
@media(max-width:700px){.sm-m-208{margin:104px}.sm-p-208{padding:104px}}
@media(max-width:700px){.sm-m-209{margin:104px}.sm-p-209{padding:104px}}
@media(max-width:700px){.sm-m-210{margin:105px}.sm-p-210{padding:105px}}
@media(max-width:700px){.sm-m-211{margin:105px}.sm-p-211{padding:105px}}
@media(max-width:700px){.sm-m-212{margin:106px}.sm-p-212{padding:106px}}
@media(max-width:700px){.sm-m-213{margin:106px}.sm-p-213{padding:106px}}
@media(max-width:700px){.sm-m-214{margin:107px}.sm-p-214{padding:107px}}
@media(max-width:700px){.sm-m-215{margin:107px}.sm-p-215{padding:107px}}
@media(max-width:700px){.sm-m-216{margin:108px}.sm-p-216{padding:108px}}
@media(max-width:700px){.sm-m-217{margin:108px}.sm-p-217{padding:108px}}
@media(max-width:700px){.sm-m-218{margin:109px}.sm-p-218{padding:109px}}
@media(max-width:700px){.sm-m-219{margin:109px}.sm-p-219{padding:109px}}
@media(max-width:700px){.sm-m-220{margin:110px}.sm-p-220{padding:110px}}
@media(max-width:700px){.sm-m-221{margin:110px}.sm-p-221{padding:110px}}
@media(max-width:700px){.sm-m-222{margin:111px}.sm-p-222{padding:111px}}
@media(max-width:700px){.sm-m-223{margin:111px}.sm-p-223{padding:111px}}
@media(max-width:700px){.sm-m-224{margin:112px}.sm-p-224{padding:112px}}
@media(max-width:700px){.sm-m-225{margin:112px}.sm-p-225{padding:112px}}
@media(max-width:700px){.sm-m-226{margin:113px}.sm-p-226{padding:113px}}
@media(max-width:700px){.sm-m-227{margin:113px}.sm-p-227{padding:113px}}
@media(max-width:700px){.sm-m-228{margin:114px}.sm-p-228{padding:114px}}
@media(max-width:700px){.sm-m-229{margin:114px}.sm-p-229{padding:114px}}
@media(max-width:700px){.sm-m-230{margin:115px}.sm-p-230{padding:115px}}
@media(max-width:700px){.sm-m-231{margin:115px}.sm-p-231{padding:115px}}
@media(max-width:700px){.sm-m-232{margin:116px}.sm-p-232{padding:116px}}
@media(max-width:700px){.sm-m-233{margin:116px}.sm-p-233{padding:116px}}
@media(max-width:700px){.sm-m-234{margin:117px}.sm-p-234{padding:117px}}
@media(max-width:700px){.sm-m-235{margin:117px}.sm-p-235{padding:117px}}
@media(max-width:700px){.sm-m-236{margin:118px}.sm-p-236{padding:118px}}
@media(max-width:700px){.sm-m-237{margin:118px}.sm-p-237{padding:118px}}
@media(max-width:700px){.sm-m-238{margin:119px}.sm-p-238{padding:119px}}
@media(max-width:700px){.sm-m-239{margin:119px}.sm-p-239{padding:119px}}
@media(max-width:700px){.sm-m-240{margin:120px}.sm-p-240{padding:120px}}

.fs-1{font-size:9px}
.lh-1{line-height:1.1}
.op-1{opacity:0.05}
.fs-2{font-size:10px}
.lh-2{line-height:1.2}
.op-2{opacity:0.05}
.fs-3{font-size:11px}
.lh-3{line-height:1.3}
.op-3{opacity:0.05}
.fs-4{font-size:12px}
.lh-4{line-height:1.4}
.op-4{opacity:0.05}
.fs-5{font-size:13px}
.lh-5{line-height:1.5}
.op-5{opacity:0.05}
.fs-6{font-size:14px}
.lh-6{line-height:1.6}
.op-6{opacity:0.05}
.fs-7{font-size:15px}
.lh-7{line-height:1.7}
.op-7{opacity:0.05}
.fs-8{font-size:16px}
.lh-8{line-height:1.8}
.op-8{opacity:0.05}
.fs-9{font-size:17px}
.lh-9{line-height:1.9}
.op-9{opacity:0.05}
.fs-10{font-size:18px}
.lh-10{line-height:2.0}
.op-10{opacity:0.05}
.fs-11{font-size:19px}
.lh-11{line-height:2.1}
.op-11{opacity:0.05}
.fs-12{font-size:20px}
.lh-12{line-height:2.2}
.op-12{opacity:0.05}
.fs-13{font-size:21px}
.lh-13{line-height:2.3}
.op-13{opacity:0.06}
.fs-14{font-size:22px}
.lh-14{line-height:2.4}
.op-14{opacity:0.06}
.fs-15{font-size:23px}
.lh-15{line-height:1.0}
.op-15{opacity:0.07}
.fs-16{font-size:24px}
.lh-16{line-height:1.1}
.op-16{opacity:0.07}
.fs-17{font-size:25px}
.lh-17{line-height:1.2}
.op-17{opacity:0.08}
.fs-18{font-size:26px}
.lh-18{line-height:1.3}
.op-18{opacity:0.08}
.fs-19{font-size:27px}
.lh-19{line-height:1.4}
.op-19{opacity:0.09}
.fs-20{font-size:28px}
.lh-20{line-height:1.5}
.op-20{opacity:0.09}
.fs-21{font-size:29px}
.lh-21{line-height:1.6}
.op-21{opacity:0.10}
.fs-22{font-size:30px}
.lh-22{line-height:1.7}
.op-22{opacity:0.10}
.fs-23{font-size:31px}
.lh-23{line-height:1.8}
.op-23{opacity:0.10}
.fs-24{font-size:8px}
.lh-24{line-height:1.9}
.op-24{opacity:0.11}
.fs-25{font-size:9px}
.lh-25{line-height:2.0}
.op-25{opacity:0.11}
.fs-26{font-size:10px}
.lh-26{line-height:2.1}
.op-26{opacity:0.12}
.fs-27{font-size:11px}
.lh-27{line-height:2.2}
.op-27{opacity:0.12}
.fs-28{font-size:12px}
.lh-28{line-height:2.3}
.op-28{opacity:0.13}
.fs-29{font-size:13px}
.lh-29{line-height:2.4}
.op-29{opacity:0.13}
.fs-30{font-size:14px}
.lh-30{line-height:1.0}
.op-30{opacity:0.14}
.fs-31{font-size:15px}
.lh-31{line-height:1.1}
.op-31{opacity:0.14}
.fs-32{font-size:16px}
.lh-32{line-height:1.2}
.op-32{opacity:0.15}
.fs-33{font-size:17px}
.lh-33{line-height:1.3}
.op-33{opacity:0.15}
.fs-34{font-size:18px}
.lh-34{line-height:1.4}
.op-34{opacity:0.15}
.fs-35{font-size:19px}
.lh-35{line-height:1.5}
.op-35{opacity:0.16}
.fs-36{font-size:20px}
.lh-36{line-height:1.6}
.op-36{opacity:0.16}
.fs-37{font-size:21px}
.lh-37{line-height:1.7}
.op-37{opacity:0.17}
.fs-38{font-size:22px}
.lh-38{line-height:1.8}
.op-38{opacity:0.17}
.fs-39{font-size:23px}
.lh-39{line-height:1.9}
.op-39{opacity:0.18}
.fs-40{font-size:24px}
.lh-40{line-height:2.0}
.op-40{opacity:0.18}
.fs-41{font-size:25px}
.lh-41{line-height:2.1}
.op-41{opacity:0.19}
.fs-42{font-size:26px}
.lh-42{line-height:2.2}
.op-42{opacity:0.19}
.fs-43{font-size:27px}
.lh-43{line-height:2.3}
.op-43{opacity:0.20}
.fs-44{font-size:28px}
.lh-44{line-height:2.4}
.op-44{opacity:0.20}
.fs-45{font-size:29px}
.lh-45{line-height:1.0}
.op-45{opacity:0.20}
.fs-46{font-size:30px}
.lh-46{line-height:1.1}
.op-46{opacity:0.21}
.fs-47{font-size:31px}
.lh-47{line-height:1.2}
.op-47{opacity:0.21}
.fs-48{font-size:8px}
.lh-48{line-height:1.3}
.op-48{opacity:0.22}
.fs-49{font-size:9px}
.lh-49{line-height:1.4}
.op-49{opacity:0.22}
.fs-50{font-size:10px}
.lh-50{line-height:1.5}
.op-50{opacity:0.23}
.fs-51{font-size:11px}
.lh-51{line-height:1.6}
.op-51{opacity:0.23}
.fs-52{font-size:12px}
.lh-52{line-height:1.7}
.op-52{opacity:0.24}
.fs-53{font-size:13px}
.lh-53{line-height:1.8}
.op-53{opacity:0.24}
.fs-54{font-size:14px}
.lh-54{line-height:1.9}
.op-54{opacity:0.25}
.fs-55{font-size:15px}
.lh-55{line-height:2.0}
.op-55{opacity:0.25}
.fs-56{font-size:16px}
.lh-56{line-height:2.1}
.op-56{opacity:0.25}
.fs-57{font-size:17px}
.lh-57{line-height:2.2}
.op-57{opacity:0.26}
.fs-58{font-size:18px}
.lh-58{line-height:2.3}
.op-58{opacity:0.26}
.fs-59{font-size:19px}
.lh-59{line-height:2.4}
.op-59{opacity:0.27}
.fs-60{font-size:20px}
.lh-60{line-height:1.0}
.op-60{opacity:0.27}
.fs-61{font-size:21px}
.lh-61{line-height:1.1}
.op-61{opacity:0.28}
.fs-62{font-size:22px}
.lh-62{line-height:1.2}
.op-62{opacity:0.28}
.fs-63{font-size:23px}
.lh-63{line-height:1.3}
.op-63{opacity:0.29}
.fs-64{font-size:24px}
.lh-64{line-height:1.4}
.op-64{opacity:0.29}
.fs-65{font-size:25px}
.lh-65{line-height:1.5}
.op-65{opacity:0.30}
.fs-66{font-size:26px}
.lh-66{line-height:1.6}
.op-66{opacity:0.30}
.fs-67{font-size:27px}
.lh-67{line-height:1.7}
.op-67{opacity:0.30}
.fs-68{font-size:28px}
.lh-68{line-height:1.8}
.op-68{opacity:0.31}
.fs-69{font-size:29px}
.lh-69{line-height:1.9}
.op-69{opacity:0.31}
.fs-70{font-size:30px}
.lh-70{line-height:2.0}
.op-70{opacity:0.32}
.fs-71{font-size:31px}
.lh-71{line-height:2.1}
.op-71{opacity:0.32}
.fs-72{font-size:8px}
.lh-72{line-height:2.2}
.op-72{opacity:0.33}
.fs-73{font-size:9px}
.lh-73{line-height:2.3}
.op-73{opacity:0.33}
.fs-74{font-size:10px}
.lh-74{line-height:2.4}
.op-74{opacity:0.34}
.fs-75{font-size:11px}
.lh-75{line-height:1.0}
.op-75{opacity:0.34}
.fs-76{font-size:12px}
.lh-76{line-height:1.1}
.op-76{opacity:0.35}
.fs-77{font-size:13px}
.lh-77{line-height:1.2}
.op-77{opacity:0.35}
.fs-78{font-size:14px}
.lh-78{line-height:1.3}
.op-78{opacity:0.35}
.fs-79{font-size:15px}
.lh-79{line-height:1.4}
.op-79{opacity:0.36}
.fs-80{font-size:16px}
.lh-80{line-height:1.5}
.op-80{opacity:0.36}
.fs-81{font-size:17px}
.lh-81{line-height:1.6}
.op-81{opacity:0.37}
.fs-82{font-size:18px}
.lh-82{line-height:1.7}
.op-82{opacity:0.37}
.fs-83{font-size:19px}
.lh-83{line-height:1.8}
.op-83{opacity:0.38}
.fs-84{font-size:20px}
.lh-84{line-height:1.9}
.op-84{opacity:0.38}
.fs-85{font-size:21px}
.lh-85{line-height:2.0}
.op-85{opacity:0.39}
.fs-86{font-size:22px}
.lh-86{line-height:2.1}
.op-86{opacity:0.39}
.fs-87{font-size:23px}
.lh-87{line-height:2.2}
.op-87{opacity:0.40}
.fs-88{font-size:24px}
.lh-88{line-height:2.3}
.op-88{opacity:0.40}
.fs-89{font-size:25px}
.lh-89{line-height:2.4}
.op-89{opacity:0.40}
.fs-90{font-size:26px}
.lh-90{line-height:1.0}
.op-90{opacity:0.41}
.fs-91{font-size:27px}
.lh-91{line-height:1.1}
.op-91{opacity:0.41}
.fs-92{font-size:28px}
.lh-92{line-height:1.2}
.op-92{opacity:0.42}
.fs-93{font-size:29px}
.lh-93{line-height:1.3}
.op-93{opacity:0.42}
.fs-94{font-size:30px}
.lh-94{line-height:1.4}
.op-94{opacity:0.43}
.fs-95{font-size:31px}
.lh-95{line-height:1.5}
.op-95{opacity:0.43}
.fs-96{font-size:8px}
.lh-96{line-height:1.6}
.op-96{opacity:0.44}
.fs-97{font-size:9px}
.lh-97{line-height:1.7}
.op-97{opacity:0.44}
.fs-98{font-size:10px}
.lh-98{line-height:1.8}
.op-98{opacity:0.45}
.fs-99{font-size:11px}
.lh-99{line-height:1.9}
.op-99{opacity:0.45}
.fs-100{font-size:12px}
.lh-100{line-height:2.0}
.op-100{opacity:0.45}
.fs-101{font-size:13px}
.lh-101{line-height:2.1}
.op-101{opacity:0.46}
.fs-102{font-size:14px}
.lh-102{line-height:2.2}
.op-102{opacity:0.46}
.fs-103{font-size:15px}
.lh-103{line-height:2.3}
.op-103{opacity:0.47}
.fs-104{font-size:16px}
.lh-104{line-height:2.4}
.op-104{opacity:0.47}
.fs-105{font-size:17px}
.lh-105{line-height:1.0}
.op-105{opacity:0.48}
.fs-106{font-size:18px}
.lh-106{line-height:1.1}
.op-106{opacity:0.48}
.fs-107{font-size:19px}
.lh-107{line-height:1.2}
.op-107{opacity:0.49}
.fs-108{font-size:20px}
.lh-108{line-height:1.3}
.op-108{opacity:0.49}
.fs-109{font-size:21px}
.lh-109{line-height:1.4}
.op-109{opacity:0.50}
.fs-110{font-size:22px}
.lh-110{line-height:1.5}
.op-110{opacity:0.50}
.fs-111{font-size:23px}
.lh-111{line-height:1.6}
.op-111{opacity:0.50}
.fs-112{font-size:24px}
.lh-112{line-height:1.7}
.op-112{opacity:0.51}
.fs-113{font-size:25px}
.lh-113{line-height:1.8}
.op-113{opacity:0.51}
.fs-114{font-size:26px}
.lh-114{line-height:1.9}
.op-114{opacity:0.52}
.fs-115{font-size:27px}
.lh-115{line-height:2.0}
.op-115{opacity:0.52}
.fs-116{font-size:28px}
.lh-116{line-height:2.1}
.op-116{opacity:0.53}
.fs-117{font-size:29px}
.lh-117{line-height:2.2}
.op-117{opacity:0.53}
.fs-118{font-size:30px}
.lh-118{line-height:2.3}
.op-118{opacity:0.54}
.fs-119{font-size:31px}
.lh-119{line-height:2.4}
.op-119{opacity:0.54}
.fs-120{font-size:8px}
.lh-120{line-height:1.0}
.op-120{opacity:0.55}
.fs-121{font-size:9px}
.lh-121{line-height:1.1}
.op-121{opacity:0.55}
.fs-122{font-size:10px}
.lh-122{line-height:1.2}
.op-122{opacity:0.55}
.fs-123{font-size:11px}
.lh-123{line-height:1.3}
.op-123{opacity:0.56}
.fs-124{font-size:12px}
.lh-124{line-height:1.4}
.op-124{opacity:0.56}
.fs-125{font-size:13px}
.lh-125{line-height:1.5}
.op-125{opacity:0.57}
.fs-126{font-size:14px}
.lh-126{line-height:1.6}
.op-126{opacity:0.57}
.fs-127{font-size:15px}
.lh-127{line-height:1.7}
.op-127{opacity:0.58}
.fs-128{font-size:16px}
.lh-128{line-height:1.8}
.op-128{opacity:0.58}
.fs-129{font-size:17px}
.lh-129{line-height:1.9}
.op-129{opacity:0.59}
.fs-130{font-size:18px}
.lh-130{line-height:2.0}
.op-130{opacity:0.59}
.fs-131{font-size:19px}
.lh-131{line-height:2.1}
.op-131{opacity:0.60}
.fs-132{font-size:20px}
.lh-132{line-height:2.2}
.op-132{opacity:0.60}
.fs-133{font-size:21px}
.lh-133{line-height:2.3}
.op-133{opacity:0.60}
.fs-134{font-size:22px}
.lh-134{line-height:2.4}
.op-134{opacity:0.61}
.fs-135{font-size:23px}
.lh-135{line-height:1.0}
.op-135{opacity:0.61}
.fs-136{font-size:24px}
.lh-136{line-height:1.1}
.op-136{opacity:0.62}
.fs-137{font-size:25px}
.lh-137{line-height:1.2}
.op-137{opacity:0.62}
.fs-138{font-size:26px}
.lh-138{line-height:1.3}
.op-138{opacity:0.63}
.fs-139{font-size:27px}
.lh-139{line-height:1.4}
.op-139{opacity:0.63}
.fs-140{font-size:28px}
.lh-140{line-height:1.5}
.op-140{opacity:0.64}
.fs-141{font-size:29px}
.lh-141{line-height:1.6}
.op-141{opacity:0.64}
.fs-142{font-size:30px}
.lh-142{line-height:1.7}
.op-142{opacity:0.65}
.fs-143{font-size:31px}
.lh-143{line-height:1.8}
.op-143{opacity:0.65}
.fs-144{font-size:8px}
.lh-144{line-height:1.9}
.op-144{opacity:0.65}
.fs-145{font-size:9px}
.lh-145{line-height:2.0}
.op-145{opacity:0.66}
.fs-146{font-size:10px}
.lh-146{line-height:2.1}
.op-146{opacity:0.66}
.fs-147{font-size:11px}
.lh-147{line-height:2.2}
.op-147{opacity:0.67}
.fs-148{font-size:12px}
.lh-148{line-height:2.3}
.op-148{opacity:0.67}
.fs-149{font-size:13px}
.lh-149{line-height:2.4}
.op-149{opacity:0.68}
.fs-150{font-size:14px}
.lh-150{line-height:1.0}
.op-150{opacity:0.68}
.fs-151{font-size:15px}
.lh-151{line-height:1.1}
.op-151{opacity:0.69}
.fs-152{font-size:16px}
.lh-152{line-height:1.2}
.op-152{opacity:0.69}
.fs-153{font-size:17px}
.lh-153{line-height:1.3}
.op-153{opacity:0.70}
.fs-154{font-size:18px}
.lh-154{line-height:1.4}
.op-154{opacity:0.70}
.fs-155{font-size:19px}
.lh-155{line-height:1.5}
.op-155{opacity:0.70}
.fs-156{font-size:20px}
.lh-156{line-height:1.6}
.op-156{opacity:0.71}
.fs-157{font-size:21px}
.lh-157{line-height:1.7}
.op-157{opacity:0.71}
.fs-158{font-size:22px}
.lh-158{line-height:1.8}
.op-158{opacity:0.72}
.fs-159{font-size:23px}
.lh-159{line-height:1.9}
.op-159{opacity:0.72}
.fs-160{font-size:24px}
.lh-160{line-height:2.0}
.op-160{opacity:0.73}
.fs-161{font-size:25px}
.lh-161{line-height:2.1}
.op-161{opacity:0.73}
.fs-162{font-size:26px}
.lh-162{line-height:2.2}
.op-162{opacity:0.74}
.fs-163{font-size:27px}
.lh-163{line-height:2.3}
.op-163{opacity:0.74}
.fs-164{font-size:28px}
.lh-164{line-height:2.4}
.op-164{opacity:0.75}
.fs-165{font-size:29px}
.lh-165{line-height:1.0}
.op-165{opacity:0.75}
.fs-166{font-size:30px}
.lh-166{line-height:1.1}
.op-166{opacity:0.75}
.fs-167{font-size:31px}
.lh-167{line-height:1.2}
.op-167{opacity:0.76}
.fs-168{font-size:8px}
.lh-168{line-height:1.3}
.op-168{opacity:0.76}
.fs-169{font-size:9px}
.lh-169{line-height:1.4}
.op-169{opacity:0.77}
.fs-170{font-size:10px}
.lh-170{line-height:1.5}
.op-170{opacity:0.77}
.fs-171{font-size:11px}
.lh-171{line-height:1.6}
.op-171{opacity:0.78}
.fs-172{font-size:12px}
.lh-172{line-height:1.7}
.op-172{opacity:0.78}
.fs-173{font-size:13px}
.lh-173{line-height:1.8}
.op-173{opacity:0.79}
.fs-174{font-size:14px}
.lh-174{line-height:1.9}
.op-174{opacity:0.79}
.fs-175{font-size:15px}
.lh-175{line-height:2.0}
.op-175{opacity:0.80}
.fs-176{font-size:16px}
.lh-176{line-height:2.1}
.op-176{opacity:0.80}
.fs-177{font-size:17px}
.lh-177{line-height:2.2}
.op-177{opacity:0.80}
.fs-178{font-size:18px}
.lh-178{line-height:2.3}
.op-178{opacity:0.81}
.fs-179{font-size:19px}
.lh-179{line-height:2.4}
.op-179{opacity:0.81}
.fs-180{font-size:20px}
.lh-180{line-height:1.0}
.op-180{opacity:0.82}
.fs-181{font-size:21px}
.lh-181{line-height:1.1}
.op-181{opacity:0.82}
.fs-182{font-size:22px}
.lh-182{line-height:1.2}
.op-182{opacity:0.83}
.fs-183{font-size:23px}
.lh-183{line-height:1.3}
.op-183{opacity:0.83}
.fs-184{font-size:24px}
.lh-184{line-height:1.4}
.op-184{opacity:0.84}
.fs-185{font-size:25px}
.lh-185{line-height:1.5}
.op-185{opacity:0.84}
.fs-186{font-size:26px}
.lh-186{line-height:1.6}
.op-186{opacity:0.85}
.fs-187{font-size:27px}
.lh-187{line-height:1.7}
.op-187{opacity:0.85}
.fs-188{font-size:28px}
.lh-188{line-height:1.8}
.op-188{opacity:0.85}
.fs-189{font-size:29px}
.lh-189{line-height:1.9}
.op-189{opacity:0.86}
.fs-190{font-size:30px}
.lh-190{line-height:2.0}
.op-190{opacity:0.86}
.fs-191{font-size:31px}
.lh-191{line-height:2.1}
.op-191{opacity:0.87}
.fs-192{font-size:8px}
.lh-192{line-height:2.2}
.op-192{opacity:0.87}
.fs-193{font-size:9px}
.lh-193{line-height:2.3}
.op-193{opacity:0.88}
.fs-194{font-size:10px}
.lh-194{line-height:2.4}
.op-194{opacity:0.88}
.fs-195{font-size:11px}
.lh-195{line-height:1.0}
.op-195{opacity:0.89}
.fs-196{font-size:12px}
.lh-196{line-height:1.1}
.op-196{opacity:0.89}
.fs-197{font-size:13px}
.lh-197{line-height:1.2}
.op-197{opacity:0.90}
.fs-198{font-size:14px}
.lh-198{line-height:1.3}
.op-198{opacity:0.90}
.fs-199{font-size:15px}
.lh-199{line-height:1.4}
.op-199{opacity:0.90}
.fs-200{font-size:16px}
.lh-200{line-height:1.5}
.op-200{opacity:0.91}
.fs-201{font-size:17px}
.lh-201{line-height:1.6}
.op-201{opacity:0.91}
.fs-202{font-size:18px}
.lh-202{line-height:1.7}
.op-202{opacity:0.92}
.fs-203{font-size:19px}
.lh-203{line-height:1.8}
.op-203{opacity:0.92}
.fs-204{font-size:20px}
.lh-204{line-height:1.9}
.op-204{opacity:0.93}
.fs-205{font-size:21px}
.lh-205{line-height:2.0}
.op-205{opacity:0.93}
.fs-206{font-size:22px}
.lh-206{line-height:2.1}
.op-206{opacity:0.94}
.fs-207{font-size:23px}
.lh-207{line-height:2.2}
.op-207{opacity:0.94}
.fs-208{font-size:24px}
.lh-208{line-height:2.3}
.op-208{opacity:0.95}
.fs-209{font-size:25px}
.lh-209{line-height:2.4}
.op-209{opacity:0.95}
.fs-210{font-size:26px}
.lh-210{line-height:1.0}
.op-210{opacity:0.95}
.fs-211{font-size:27px}
.lh-211{line-height:1.1}
.op-211{opacity:0.96}
.fs-212{font-size:28px}
.lh-212{line-height:1.2}
.op-212{opacity:0.96}
.fs-213{font-size:29px}
.lh-213{line-height:1.3}
.op-213{opacity:0.97}
.fs-214{font-size:30px}
.lh-214{line-height:1.4}
.op-214{opacity:0.97}
.fs-215{font-size:31px}
.lh-215{line-height:1.5}
.op-215{opacity:0.98}
.fs-216{font-size:8px}
.lh-216{line-height:1.6}
.op-216{opacity:0.98}
.fs-217{font-size:9px}
.lh-217{line-height:1.7}
.op-217{opacity:0.99}
.fs-218{font-size:10px}
.lh-218{line-height:1.8}
.op-218{opacity:0.99}
.fs-219{font-size:11px}
.lh-219{line-height:1.9}
.op-219{opacity:1.00}
.glow-cyan-0001{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-0002{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-0003{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0004{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0005{background:rgba(0,234,255,0.07)}
.bg-blue-0006{background:rgba(45,123,255,0.08)}
.opacity-0007{opacity:0.17}
.radius-0008{border-radius:9px}
.glow-cyan-0009{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-0010{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-0011{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0012{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0013{background:rgba(0,234,255,0.05)}
.bg-blue-0014{background:rgba(45,123,255,0.06)}
.opacity-0015{opacity:0.25}
.radius-0016{border-radius:17px}
.glow-cyan-0017{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-0018{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-0019{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0020{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0021{background:rgba(0,234,255,0.03)}
.bg-blue-0022{background:rgba(45,123,255,0.04)}
.opacity-0023{opacity:0.33}
.radius-0024{border-radius:25px}
.glow-cyan-0025{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-0026{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-0027{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0028{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0029{background:rgba(0,234,255,0.11)}
.bg-blue-0030{background:rgba(45,123,255,0.02)}
.opacity-0031{opacity:0.41}
.radius-0032{border-radius:33px}
.glow-cyan-0033{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-0034{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-0035{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0036{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0037{background:rgba(0,234,255,0.09)}
.bg-blue-0038{background:rgba(45,123,255,0.10)}
.opacity-0039{opacity:0.49}
.radius-0040{border-radius:41px}
.glow-cyan-0041{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-0042{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-0043{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0044{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0045{background:rgba(0,234,255,0.07)}
.bg-blue-0046{background:rgba(45,123,255,0.08)}
.opacity-0047{opacity:0.57}
.radius-0048{border-radius:7px}
.glow-cyan-0049{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-0050{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-0051{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0052{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0053{background:rgba(0,234,255,0.05)}
.bg-blue-0054{background:rgba(45,123,255,0.06)}
.opacity-0055{opacity:0.65}
.radius-0056{border-radius:15px}
.glow-cyan-0057{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-0058{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-0059{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0060{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0061{background:rgba(0,234,255,0.03)}
.bg-blue-0062{background:rgba(45,123,255,0.04)}
.opacity-0063{opacity:0.73}
.radius-0064{border-radius:23px}
.glow-cyan-0065{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-0066{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-0067{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0068{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0069{background:rgba(0,234,255,0.11)}
.bg-blue-0070{background:rgba(45,123,255,0.02)}
.opacity-0071{opacity:0.81}
.radius-0072{border-radius:31px}
.glow-cyan-0073{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-0074{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-0075{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0076{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0077{background:rgba(0,234,255,0.09)}
.bg-blue-0078{background:rgba(45,123,255,0.10)}
.opacity-0079{opacity:0.89}
.radius-0080{border-radius:39px}
.glow-cyan-0081{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-0082{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-0083{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0084{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0085{background:rgba(0,234,255,0.07)}
.bg-blue-0086{background:rgba(45,123,255,0.08)}
.opacity-0087{opacity:0.97}
.radius-0088{border-radius:5px}
.glow-cyan-0089{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-0090{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-0091{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0092{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0093{background:rgba(0,234,255,0.05)}
.bg-blue-0094{background:rgba(45,123,255,0.06)}
.opacity-0095{opacity:0.15}
.radius-0096{border-radius:13px}
.glow-cyan-0097{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-0098{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-0099{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0100{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0101{background:rgba(0,234,255,0.03)}
.bg-blue-0102{background:rgba(45,123,255,0.04)}
.opacity-0103{opacity:0.23}
.radius-0104{border-radius:21px}
.glow-cyan-0105{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-0106{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-0107{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0108{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0109{background:rgba(0,234,255,0.11)}
.bg-blue-0110{background:rgba(45,123,255,0.02)}
.opacity-0111{opacity:0.31}
.radius-0112{border-radius:29px}
.glow-cyan-0113{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-0114{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-0115{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0116{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0117{background:rgba(0,234,255,0.09)}
.bg-blue-0118{background:rgba(45,123,255,0.10)}
.opacity-0119{opacity:0.39}
.radius-0120{border-radius:37px}
.glow-cyan-0121{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-0122{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-0123{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0124{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0125{background:rgba(0,234,255,0.07)}
.bg-blue-0126{background:rgba(45,123,255,0.08)}
.opacity-0127{opacity:0.47}
.radius-0128{border-radius:3px}
.glow-cyan-0129{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-0130{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-0131{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0132{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0133{background:rgba(0,234,255,0.05)}
.bg-blue-0134{background:rgba(45,123,255,0.06)}
.opacity-0135{opacity:0.55}
.radius-0136{border-radius:11px}
.glow-cyan-0137{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-0138{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-0139{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0140{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0141{background:rgba(0,234,255,0.03)}
.bg-blue-0142{background:rgba(45,123,255,0.04)}
.opacity-0143{opacity:0.63}
.radius-0144{border-radius:19px}
.glow-cyan-0145{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-0146{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-0147{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0148{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0149{background:rgba(0,234,255,0.11)}
.bg-blue-0150{background:rgba(45,123,255,0.02)}
.opacity-0151{opacity:0.71}
.radius-0152{border-radius:27px}
.glow-cyan-0153{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-0154{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-0155{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0156{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0157{background:rgba(0,234,255,0.09)}
.bg-blue-0158{background:rgba(45,123,255,0.10)}
.opacity-0159{opacity:0.79}
.radius-0160{border-radius:35px}
.glow-cyan-0161{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-0162{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-0163{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0164{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0165{background:rgba(0,234,255,0.07)}
.bg-blue-0166{background:rgba(45,123,255,0.08)}
.opacity-0167{opacity:0.87}
.radius-0168{border-radius:1px}
.glow-cyan-0169{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-0170{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-0171{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0172{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0173{background:rgba(0,234,255,0.05)}
.bg-blue-0174{background:rgba(45,123,255,0.06)}
.opacity-0175{opacity:0.95}
.radius-0176{border-radius:9px}
.glow-cyan-0177{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-0178{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-0179{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0180{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0181{background:rgba(0,234,255,0.03)}
.bg-blue-0182{background:rgba(45,123,255,0.04)}
.opacity-0183{opacity:0.13}
.radius-0184{border-radius:17px}
.glow-cyan-0185{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-0186{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-0187{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0188{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0189{background:rgba(0,234,255,0.11)}
.bg-blue-0190{background:rgba(45,123,255,0.02)}
.opacity-0191{opacity:0.21}
.radius-0192{border-radius:25px}
.glow-cyan-0193{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-0194{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-0195{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0196{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0197{background:rgba(0,234,255,0.09)}
.bg-blue-0198{background:rgba(45,123,255,0.10)}
.opacity-0199{opacity:0.29}
.radius-0200{border-radius:33px}
.glow-cyan-0201{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-0202{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-0203{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0204{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0205{background:rgba(0,234,255,0.07)}
.bg-blue-0206{background:rgba(45,123,255,0.08)}
.opacity-0207{opacity:0.37}
.radius-0208{border-radius:41px}
.glow-cyan-0209{box-shadow:0 0 5px rgba(0,234,255,0.13)}
.glow-blue-0210{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-0211{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0212{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0213{background:rgba(0,234,255,0.05)}
.bg-blue-0214{background:rgba(45,123,255,0.06)}
.opacity-0215{opacity:0.45}
.radius-0216{border-radius:7px}
.glow-cyan-0217{box-shadow:0 0 13px rgba(0,234,255,0.09)}
.glow-blue-0218{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-0219{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0220{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0221{background:rgba(0,234,255,0.03)}
.bg-blue-0222{background:rgba(45,123,255,0.04)}
.opacity-0223{opacity:0.53}
.radius-0224{border-radius:15px}
.glow-cyan-0225{box-shadow:0 0 21px rgba(0,234,255,0.17)}
.glow-blue-0226{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-0227{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0228{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0229{background:rgba(0,234,255,0.11)}
.bg-blue-0230{background:rgba(45,123,255,0.02)}
.opacity-0231{opacity:0.61}
.radius-0232{border-radius:23px}
.glow-cyan-0233{box-shadow:0 0 29px rgba(0,234,255,0.13)}
.glow-blue-0234{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-0235{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0236{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0237{background:rgba(0,234,255,0.09)}
.bg-blue-0238{background:rgba(45,123,255,0.10)}
.opacity-0239{opacity:0.69}
.radius-0240{border-radius:31px}
.glow-cyan-0241{box-shadow:0 0 11px rgba(0,234,255,0.09)}
.glow-blue-0242{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-0243{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0244{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0245{background:rgba(0,234,255,0.07)}
.bg-blue-0246{background:rgba(45,123,255,0.08)}
.opacity-0247{opacity:0.77}
.radius-0248{border-radius:39px}
.glow-cyan-0249{box-shadow:0 0 19px rgba(0,234,255,0.17)}
.glow-blue-0250{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-0251{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0252{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0253{background:rgba(0,234,255,0.05)}
.bg-blue-0254{background:rgba(45,123,255,0.06)}
.opacity-0255{opacity:0.85}
.radius-0256{border-radius:5px}
.glow-cyan-0257{box-shadow:0 0 27px rgba(0,234,255,0.13)}
.glow-blue-0258{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-0259{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0260{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0261{background:rgba(0,234,255,0.03)}
.bg-blue-0262{background:rgba(45,123,255,0.04)}
.opacity-0263{opacity:0.93}
.radius-0264{border-radius:13px}
.glow-cyan-0265{box-shadow:0 0 9px rgba(0,234,255,0.09)}
.glow-blue-0266{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-0267{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0268{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0269{background:rgba(0,234,255,0.11)}
.bg-blue-0270{background:rgba(45,123,255,0.02)}
.opacity-0271{opacity:0.11}
.radius-0272{border-radius:21px}
.glow-cyan-0273{box-shadow:0 0 17px rgba(0,234,255,0.17)}
.glow-blue-0274{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-0275{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0276{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0277{background:rgba(0,234,255,0.09)}
.bg-blue-0278{background:rgba(45,123,255,0.10)}
.opacity-0279{opacity:0.19}
.radius-0280{border-radius:29px}
.glow-cyan-0281{box-shadow:0 0 25px rgba(0,234,255,0.13)}
.glow-blue-0282{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-0283{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0284{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0285{background:rgba(0,234,255,0.07)}
.bg-blue-0286{background:rgba(45,123,255,0.08)}
.opacity-0287{opacity:0.27}
.radius-0288{border-radius:37px}
.glow-cyan-0289{box-shadow:0 0 7px rgba(0,234,255,0.09)}
.glow-blue-0290{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-0291{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0292{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0293{background:rgba(0,234,255,0.05)}
.bg-blue-0294{background:rgba(45,123,255,0.06)}
.opacity-0295{opacity:0.35}
.radius-0296{border-radius:3px}
.glow-cyan-0297{box-shadow:0 0 15px rgba(0,234,255,0.17)}
.glow-blue-0298{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-0299{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0300{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0301{background:rgba(0,234,255,0.03)}
.bg-blue-0302{background:rgba(45,123,255,0.04)}
.opacity-0303{opacity:0.43}
.radius-0304{border-radius:11px}
.glow-cyan-0305{box-shadow:0 0 23px rgba(0,234,255,0.13)}
.glow-blue-0306{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-0307{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0308{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0309{background:rgba(0,234,255,0.11)}
.bg-blue-0310{background:rgba(45,123,255,0.02)}
.opacity-0311{opacity:0.51}
.radius-0312{border-radius:19px}
.glow-cyan-0313{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-0314{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-0315{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0316{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0317{background:rgba(0,234,255,0.09)}
.bg-blue-0318{background:rgba(45,123,255,0.10)}
.opacity-0319{opacity:0.59}
.radius-0320{border-radius:27px}
.glow-cyan-0321{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-0322{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-0323{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0324{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0325{background:rgba(0,234,255,0.07)}
.bg-blue-0326{background:rgba(45,123,255,0.08)}
.opacity-0327{opacity:0.67}
.radius-0328{border-radius:35px}
.glow-cyan-0329{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-0330{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-0331{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0332{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0333{background:rgba(0,234,255,0.05)}
.bg-blue-0334{background:rgba(45,123,255,0.06)}
.opacity-0335{opacity:0.75}
.radius-0336{border-radius:1px}
.glow-cyan-0337{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-0338{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-0339{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0340{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0341{background:rgba(0,234,255,0.03)}
.bg-blue-0342{background:rgba(45,123,255,0.04)}
.opacity-0343{opacity:0.83}
.radius-0344{border-radius:9px}
.glow-cyan-0345{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-0346{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-0347{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0348{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0349{background:rgba(0,234,255,0.11)}
.bg-blue-0350{background:rgba(45,123,255,0.02)}
.opacity-0351{opacity:0.91}
.radius-0352{border-radius:17px}
.glow-cyan-0353{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-0354{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-0355{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0356{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0357{background:rgba(0,234,255,0.09)}
.bg-blue-0358{background:rgba(45,123,255,0.10)}
.opacity-0359{opacity:0.99}
.radius-0360{border-radius:25px}
.glow-cyan-0361{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-0362{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-0363{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0364{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0365{background:rgba(0,234,255,0.07)}
.bg-blue-0366{background:rgba(45,123,255,0.08)}
.opacity-0367{opacity:0.17}
.radius-0368{border-radius:33px}
.glow-cyan-0369{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-0370{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-0371{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0372{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0373{background:rgba(0,234,255,0.05)}
.bg-blue-0374{background:rgba(45,123,255,0.06)}
.opacity-0375{opacity:0.25}
.radius-0376{border-radius:41px}
.glow-cyan-0377{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-0378{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-0379{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0380{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0381{background:rgba(0,234,255,0.03)}
.bg-blue-0382{background:rgba(45,123,255,0.04)}
.opacity-0383{opacity:0.33}
.radius-0384{border-radius:7px}
.glow-cyan-0385{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-0386{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-0387{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0388{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0389{background:rgba(0,234,255,0.11)}
.bg-blue-0390{background:rgba(45,123,255,0.02)}
.opacity-0391{opacity:0.41}
.radius-0392{border-radius:15px}
.glow-cyan-0393{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-0394{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-0395{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0396{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0397{background:rgba(0,234,255,0.09)}
.bg-blue-0398{background:rgba(45,123,255,0.10)}
.opacity-0399{opacity:0.49}
.radius-0400{border-radius:23px}
.glow-cyan-0401{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-0402{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-0403{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0404{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0405{background:rgba(0,234,255,0.07)}
.bg-blue-0406{background:rgba(45,123,255,0.08)}
.opacity-0407{opacity:0.57}
.radius-0408{border-radius:31px}
.glow-cyan-0409{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-0410{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-0411{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0412{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0413{background:rgba(0,234,255,0.05)}
.bg-blue-0414{background:rgba(45,123,255,0.06)}
.opacity-0415{opacity:0.65}
.radius-0416{border-radius:39px}
.glow-cyan-0417{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-0418{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-0419{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0420{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0421{background:rgba(0,234,255,0.03)}
.bg-blue-0422{background:rgba(45,123,255,0.04)}
.opacity-0423{opacity:0.73}
.radius-0424{border-radius:5px}
.glow-cyan-0425{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-0426{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-0427{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0428{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0429{background:rgba(0,234,255,0.11)}
.bg-blue-0430{background:rgba(45,123,255,0.02)}
.opacity-0431{opacity:0.81}
.radius-0432{border-radius:13px}
.glow-cyan-0433{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-0434{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-0435{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0436{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0437{background:rgba(0,234,255,0.09)}
.bg-blue-0438{background:rgba(45,123,255,0.10)}
.opacity-0439{opacity:0.89}
.radius-0440{border-radius:21px}
.glow-cyan-0441{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-0442{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-0443{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0444{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0445{background:rgba(0,234,255,0.07)}
.bg-blue-0446{background:rgba(45,123,255,0.08)}
.opacity-0447{opacity:0.97}
.radius-0448{border-radius:29px}
.glow-cyan-0449{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-0450{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-0451{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0452{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0453{background:rgba(0,234,255,0.05)}
.bg-blue-0454{background:rgba(45,123,255,0.06)}
.opacity-0455{opacity:0.15}
.radius-0456{border-radius:37px}
.glow-cyan-0457{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-0458{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-0459{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0460{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0461{background:rgba(0,234,255,0.03)}
.bg-blue-0462{background:rgba(45,123,255,0.04)}
.opacity-0463{opacity:0.23}
.radius-0464{border-radius:3px}
.glow-cyan-0465{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-0466{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-0467{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0468{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0469{background:rgba(0,234,255,0.11)}
.bg-blue-0470{background:rgba(45,123,255,0.02)}
.opacity-0471{opacity:0.31}
.radius-0472{border-radius:11px}
.glow-cyan-0473{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-0474{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-0475{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0476{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0477{background:rgba(0,234,255,0.09)}
.bg-blue-0478{background:rgba(45,123,255,0.10)}
.opacity-0479{opacity:0.39}
.radius-0480{border-radius:19px}
.glow-cyan-0481{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-0482{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-0483{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0484{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0485{background:rgba(0,234,255,0.07)}
.bg-blue-0486{background:rgba(45,123,255,0.08)}
.opacity-0487{opacity:0.47}
.radius-0488{border-radius:27px}
.glow-cyan-0489{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-0490{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-0491{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0492{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0493{background:rgba(0,234,255,0.05)}
.bg-blue-0494{background:rgba(45,123,255,0.06)}
.opacity-0495{opacity:0.55}
.radius-0496{border-radius:35px}
.glow-cyan-0497{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-0498{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-0499{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0500{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0501{background:rgba(0,234,255,0.03)}
.bg-blue-0502{background:rgba(45,123,255,0.04)}
.opacity-0503{opacity:0.63}
.radius-0504{border-radius:1px}
.glow-cyan-0505{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-0506{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-0507{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0508{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0509{background:rgba(0,234,255,0.11)}
.bg-blue-0510{background:rgba(45,123,255,0.02)}
.opacity-0511{opacity:0.71}
.radius-0512{border-radius:9px}
.glow-cyan-0513{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-0514{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-0515{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0516{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0517{background:rgba(0,234,255,0.09)}
.bg-blue-0518{background:rgba(45,123,255,0.10)}
.opacity-0519{opacity:0.79}
.radius-0520{border-radius:17px}
.glow-cyan-0521{box-shadow:0 0 5px rgba(0,234,255,0.13)}
.glow-blue-0522{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-0523{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0524{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0525{background:rgba(0,234,255,0.07)}
.bg-blue-0526{background:rgba(45,123,255,0.08)}
.opacity-0527{opacity:0.87}
.radius-0528{border-radius:25px}
.glow-cyan-0529{box-shadow:0 0 13px rgba(0,234,255,0.09)}
.glow-blue-0530{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-0531{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0532{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0533{background:rgba(0,234,255,0.05)}
.bg-blue-0534{background:rgba(45,123,255,0.06)}
.opacity-0535{opacity:0.95}
.radius-0536{border-radius:33px}
.glow-cyan-0537{box-shadow:0 0 21px rgba(0,234,255,0.17)}
.glow-blue-0538{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-0539{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0540{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0541{background:rgba(0,234,255,0.03)}
.bg-blue-0542{background:rgba(45,123,255,0.04)}
.opacity-0543{opacity:0.13}
.radius-0544{border-radius:41px}
.glow-cyan-0545{box-shadow:0 0 29px rgba(0,234,255,0.13)}
.glow-blue-0546{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-0547{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0548{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0549{background:rgba(0,234,255,0.11)}
.bg-blue-0550{background:rgba(45,123,255,0.02)}
.opacity-0551{opacity:0.21}
.radius-0552{border-radius:7px}
.glow-cyan-0553{box-shadow:0 0 11px rgba(0,234,255,0.09)}
.glow-blue-0554{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-0555{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0556{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0557{background:rgba(0,234,255,0.09)}
.bg-blue-0558{background:rgba(45,123,255,0.10)}
.opacity-0559{opacity:0.29}
.radius-0560{border-radius:15px}
.glow-cyan-0561{box-shadow:0 0 19px rgba(0,234,255,0.17)}
.glow-blue-0562{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-0563{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0564{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0565{background:rgba(0,234,255,0.07)}
.bg-blue-0566{background:rgba(45,123,255,0.08)}
.opacity-0567{opacity:0.37}
.radius-0568{border-radius:23px}
.glow-cyan-0569{box-shadow:0 0 27px rgba(0,234,255,0.13)}
.glow-blue-0570{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-0571{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0572{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0573{background:rgba(0,234,255,0.05)}
.bg-blue-0574{background:rgba(45,123,255,0.06)}
.opacity-0575{opacity:0.45}
.radius-0576{border-radius:31px}
.glow-cyan-0577{box-shadow:0 0 9px rgba(0,234,255,0.09)}
.glow-blue-0578{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-0579{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0580{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0581{background:rgba(0,234,255,0.03)}
.bg-blue-0582{background:rgba(45,123,255,0.04)}
.opacity-0583{opacity:0.53}
.radius-0584{border-radius:39px}
.glow-cyan-0585{box-shadow:0 0 17px rgba(0,234,255,0.17)}
.glow-blue-0586{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-0587{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0588{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0589{background:rgba(0,234,255,0.11)}
.bg-blue-0590{background:rgba(45,123,255,0.02)}
.opacity-0591{opacity:0.61}
.radius-0592{border-radius:5px}
.glow-cyan-0593{box-shadow:0 0 25px rgba(0,234,255,0.13)}
.glow-blue-0594{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-0595{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0596{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0597{background:rgba(0,234,255,0.09)}
.bg-blue-0598{background:rgba(45,123,255,0.10)}
.opacity-0599{opacity:0.69}
.radius-0600{border-radius:13px}
.glow-cyan-0601{box-shadow:0 0 7px rgba(0,234,255,0.09)}
.glow-blue-0602{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-0603{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0604{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0605{background:rgba(0,234,255,0.07)}
.bg-blue-0606{background:rgba(45,123,255,0.08)}
.opacity-0607{opacity:0.77}
.radius-0608{border-radius:21px}
.glow-cyan-0609{box-shadow:0 0 15px rgba(0,234,255,0.17)}
.glow-blue-0610{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-0611{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0612{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0613{background:rgba(0,234,255,0.05)}
.bg-blue-0614{background:rgba(45,123,255,0.06)}
.opacity-0615{opacity:0.85}
.radius-0616{border-radius:29px}
.glow-cyan-0617{box-shadow:0 0 23px rgba(0,234,255,0.13)}
.glow-blue-0618{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-0619{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0620{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0621{background:rgba(0,234,255,0.03)}
.bg-blue-0622{background:rgba(45,123,255,0.04)}
.opacity-0623{opacity:0.93}
.radius-0624{border-radius:37px}
.glow-cyan-0625{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-0626{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-0627{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0628{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0629{background:rgba(0,234,255,0.11)}
.bg-blue-0630{background:rgba(45,123,255,0.02)}
.opacity-0631{opacity:0.11}
.radius-0632{border-radius:3px}
.glow-cyan-0633{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-0634{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-0635{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0636{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0637{background:rgba(0,234,255,0.09)}
.bg-blue-0638{background:rgba(45,123,255,0.10)}
.opacity-0639{opacity:0.19}
.radius-0640{border-radius:11px}
.glow-cyan-0641{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-0642{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-0643{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0644{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0645{background:rgba(0,234,255,0.07)}
.bg-blue-0646{background:rgba(45,123,255,0.08)}
.opacity-0647{opacity:0.27}
.radius-0648{border-radius:19px}
.glow-cyan-0649{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-0650{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-0651{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0652{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0653{background:rgba(0,234,255,0.05)}
.bg-blue-0654{background:rgba(45,123,255,0.06)}
.opacity-0655{opacity:0.35}
.radius-0656{border-radius:27px}
.glow-cyan-0657{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-0658{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-0659{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0660{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0661{background:rgba(0,234,255,0.03)}
.bg-blue-0662{background:rgba(45,123,255,0.04)}
.opacity-0663{opacity:0.43}
.radius-0664{border-radius:35px}
.glow-cyan-0665{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-0666{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-0667{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0668{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0669{background:rgba(0,234,255,0.11)}
.bg-blue-0670{background:rgba(45,123,255,0.02)}
.opacity-0671{opacity:0.51}
.radius-0672{border-radius:1px}
.glow-cyan-0673{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-0674{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-0675{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0676{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0677{background:rgba(0,234,255,0.09)}
.bg-blue-0678{background:rgba(45,123,255,0.10)}
.opacity-0679{opacity:0.59}
.radius-0680{border-radius:9px}
.glow-cyan-0681{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-0682{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-0683{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0684{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0685{background:rgba(0,234,255,0.07)}
.bg-blue-0686{background:rgba(45,123,255,0.08)}
.opacity-0687{opacity:0.67}
.radius-0688{border-radius:17px}
.glow-cyan-0689{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-0690{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-0691{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0692{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0693{background:rgba(0,234,255,0.05)}
.bg-blue-0694{background:rgba(45,123,255,0.06)}
.opacity-0695{opacity:0.75}
.radius-0696{border-radius:25px}
.glow-cyan-0697{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-0698{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-0699{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0700{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0701{background:rgba(0,234,255,0.03)}
.bg-blue-0702{background:rgba(45,123,255,0.04)}
.opacity-0703{opacity:0.83}
.radius-0704{border-radius:33px}
.glow-cyan-0705{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-0706{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-0707{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0708{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0709{background:rgba(0,234,255,0.11)}
.bg-blue-0710{background:rgba(45,123,255,0.02)}
.opacity-0711{opacity:0.91}
.radius-0712{border-radius:41px}
.glow-cyan-0713{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-0714{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-0715{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0716{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0717{background:rgba(0,234,255,0.09)}
.bg-blue-0718{background:rgba(45,123,255,0.10)}
.opacity-0719{opacity:0.99}
.radius-0720{border-radius:7px}
.glow-cyan-0721{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-0722{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-0723{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0724{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0725{background:rgba(0,234,255,0.07)}
.bg-blue-0726{background:rgba(45,123,255,0.08)}
.opacity-0727{opacity:0.17}
.radius-0728{border-radius:15px}
.glow-cyan-0729{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-0730{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-0731{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0732{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0733{background:rgba(0,234,255,0.05)}
.bg-blue-0734{background:rgba(45,123,255,0.06)}
.opacity-0735{opacity:0.25}
.radius-0736{border-radius:23px}
.glow-cyan-0737{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-0738{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-0739{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0740{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0741{background:rgba(0,234,255,0.03)}
.bg-blue-0742{background:rgba(45,123,255,0.04)}
.opacity-0743{opacity:0.33}
.radius-0744{border-radius:31px}
.glow-cyan-0745{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-0746{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-0747{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0748{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0749{background:rgba(0,234,255,0.11)}
.bg-blue-0750{background:rgba(45,123,255,0.02)}
.opacity-0751{opacity:0.41}
.radius-0752{border-radius:39px}
.glow-cyan-0753{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-0754{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-0755{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0756{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0757{background:rgba(0,234,255,0.09)}
.bg-blue-0758{background:rgba(45,123,255,0.10)}
.opacity-0759{opacity:0.49}
.radius-0760{border-radius:5px}
.glow-cyan-0761{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-0762{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-0763{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0764{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0765{background:rgba(0,234,255,0.07)}
.bg-blue-0766{background:rgba(45,123,255,0.08)}
.opacity-0767{opacity:0.57}
.radius-0768{border-radius:13px}
.glow-cyan-0769{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-0770{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-0771{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0772{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0773{background:rgba(0,234,255,0.05)}
.bg-blue-0774{background:rgba(45,123,255,0.06)}
.opacity-0775{opacity:0.65}
.radius-0776{border-radius:21px}
.glow-cyan-0777{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-0778{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-0779{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0780{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0781{background:rgba(0,234,255,0.03)}
.bg-blue-0782{background:rgba(45,123,255,0.04)}
.opacity-0783{opacity:0.73}
.radius-0784{border-radius:29px}
.glow-cyan-0785{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-0786{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-0787{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0788{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0789{background:rgba(0,234,255,0.11)}
.bg-blue-0790{background:rgba(45,123,255,0.02)}
.opacity-0791{opacity:0.81}
.radius-0792{border-radius:37px}
.glow-cyan-0793{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-0794{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-0795{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0796{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0797{background:rgba(0,234,255,0.09)}
.bg-blue-0798{background:rgba(45,123,255,0.10)}
.opacity-0799{opacity:0.89}
.radius-0800{border-radius:3px}
.glow-cyan-0801{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-0802{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-0803{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0804{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0805{background:rgba(0,234,255,0.07)}
.bg-blue-0806{background:rgba(45,123,255,0.08)}
.opacity-0807{opacity:0.97}
.radius-0808{border-radius:11px}
.glow-cyan-0809{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-0810{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-0811{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0812{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0813{background:rgba(0,234,255,0.05)}
.bg-blue-0814{background:rgba(45,123,255,0.06)}
.opacity-0815{opacity:0.15}
.radius-0816{border-radius:19px}
.glow-cyan-0817{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-0818{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-0819{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0820{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0821{background:rgba(0,234,255,0.03)}
.bg-blue-0822{background:rgba(45,123,255,0.04)}
.opacity-0823{opacity:0.23}
.radius-0824{border-radius:27px}
.glow-cyan-0825{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-0826{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-0827{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0828{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0829{background:rgba(0,234,255,0.11)}
.bg-blue-0830{background:rgba(45,123,255,0.02)}
.opacity-0831{opacity:0.31}
.radius-0832{border-radius:35px}
.glow-cyan-0833{box-shadow:0 0 5px rgba(0,234,255,0.13)}
.glow-blue-0834{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-0835{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0836{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0837{background:rgba(0,234,255,0.09)}
.bg-blue-0838{background:rgba(45,123,255,0.10)}
.opacity-0839{opacity:0.39}
.radius-0840{border-radius:1px}
.glow-cyan-0841{box-shadow:0 0 13px rgba(0,234,255,0.09)}
.glow-blue-0842{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-0843{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0844{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0845{background:rgba(0,234,255,0.07)}
.bg-blue-0846{background:rgba(45,123,255,0.08)}
.opacity-0847{opacity:0.47}
.radius-0848{border-radius:9px}
.glow-cyan-0849{box-shadow:0 0 21px rgba(0,234,255,0.17)}
.glow-blue-0850{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-0851{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0852{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0853{background:rgba(0,234,255,0.05)}
.bg-blue-0854{background:rgba(45,123,255,0.06)}
.opacity-0855{opacity:0.55}
.radius-0856{border-radius:17px}
.glow-cyan-0857{box-shadow:0 0 29px rgba(0,234,255,0.13)}
.glow-blue-0858{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-0859{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0860{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0861{background:rgba(0,234,255,0.03)}
.bg-blue-0862{background:rgba(45,123,255,0.04)}
.opacity-0863{opacity:0.63}
.radius-0864{border-radius:25px}
.glow-cyan-0865{box-shadow:0 0 11px rgba(0,234,255,0.09)}
.glow-blue-0866{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-0867{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0868{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0869{background:rgba(0,234,255,0.11)}
.bg-blue-0870{background:rgba(45,123,255,0.02)}
.opacity-0871{opacity:0.71}
.radius-0872{border-radius:33px}
.glow-cyan-0873{box-shadow:0 0 19px rgba(0,234,255,0.17)}
.glow-blue-0874{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-0875{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0876{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0877{background:rgba(0,234,255,0.09)}
.bg-blue-0878{background:rgba(45,123,255,0.10)}
.opacity-0879{opacity:0.79}
.radius-0880{border-radius:41px}
.glow-cyan-0881{box-shadow:0 0 27px rgba(0,234,255,0.13)}
.glow-blue-0882{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-0883{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0884{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0885{background:rgba(0,234,255,0.07)}
.bg-blue-0886{background:rgba(45,123,255,0.08)}
.opacity-0887{opacity:0.87}
.radius-0888{border-radius:7px}
.glow-cyan-0889{box-shadow:0 0 9px rgba(0,234,255,0.09)}
.glow-blue-0890{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-0891{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0892{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0893{background:rgba(0,234,255,0.05)}
.bg-blue-0894{background:rgba(45,123,255,0.06)}
.opacity-0895{opacity:0.95}
.radius-0896{border-radius:15px}
.glow-cyan-0897{box-shadow:0 0 17px rgba(0,234,255,0.17)}
.glow-blue-0898{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-0899{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0900{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0901{background:rgba(0,234,255,0.03)}
.bg-blue-0902{background:rgba(45,123,255,0.04)}
.opacity-0903{opacity:0.13}
.radius-0904{border-radius:23px}
.glow-cyan-0905{box-shadow:0 0 25px rgba(0,234,255,0.13)}
.glow-blue-0906{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-0907{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0908{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0909{background:rgba(0,234,255,0.11)}
.bg-blue-0910{background:rgba(45,123,255,0.02)}
.opacity-0911{opacity:0.21}
.radius-0912{border-radius:31px}
.glow-cyan-0913{box-shadow:0 0 7px rgba(0,234,255,0.09)}
.glow-blue-0914{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-0915{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0916{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0917{background:rgba(0,234,255,0.09)}
.bg-blue-0918{background:rgba(45,123,255,0.10)}
.opacity-0919{opacity:0.29}
.radius-0920{border-radius:39px}
.glow-cyan-0921{box-shadow:0 0 15px rgba(0,234,255,0.17)}
.glow-blue-0922{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-0923{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0924{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0925{background:rgba(0,234,255,0.07)}
.bg-blue-0926{background:rgba(45,123,255,0.08)}
.opacity-0927{opacity:0.37}
.radius-0928{border-radius:5px}
.glow-cyan-0929{box-shadow:0 0 23px rgba(0,234,255,0.13)}
.glow-blue-0930{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-0931{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0932{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0933{background:rgba(0,234,255,0.05)}
.bg-blue-0934{background:rgba(45,123,255,0.06)}
.opacity-0935{opacity:0.45}
.radius-0936{border-radius:13px}
.glow-cyan-0937{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-0938{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-0939{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0940{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0941{background:rgba(0,234,255,0.03)}
.bg-blue-0942{background:rgba(45,123,255,0.04)}
.opacity-0943{opacity:0.53}
.radius-0944{border-radius:21px}
.glow-cyan-0945{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-0946{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-0947{border:1px solid rgba(0,234,255,0.17)}
.border-blue-0948{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-0949{background:rgba(0,234,255,0.11)}
.bg-blue-0950{background:rgba(45,123,255,0.02)}
.opacity-0951{opacity:0.61}
.radius-0952{border-radius:29px}
.glow-cyan-0953{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-0954{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-0955{border:1px solid rgba(0,234,255,0.11)}
.border-blue-0956{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-0957{background:rgba(0,234,255,0.09)}
.bg-blue-0958{background:rgba(45,123,255,0.10)}
.opacity-0959{opacity:0.69}
.radius-0960{border-radius:37px}
.glow-cyan-0961{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-0962{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-0963{border:1px solid rgba(0,234,255,0.19)}
.border-blue-0964{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-0965{background:rgba(0,234,255,0.07)}
.bg-blue-0966{background:rgba(45,123,255,0.08)}
.opacity-0967{opacity:0.77}
.radius-0968{border-radius:3px}
.glow-cyan-0969{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-0970{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-0971{border:1px solid rgba(0,234,255,0.13)}
.border-blue-0972{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-0973{background:rgba(0,234,255,0.05)}
.bg-blue-0974{background:rgba(45,123,255,0.06)}
.opacity-0975{opacity:0.85}
.radius-0976{border-radius:11px}
.glow-cyan-0977{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-0978{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-0979{border:1px solid rgba(0,234,255,0.21)}
.border-blue-0980{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-0981{background:rgba(0,234,255,0.03)}
.bg-blue-0982{background:rgba(45,123,255,0.04)}
.opacity-0983{opacity:0.93}
.radius-0984{border-radius:19px}
.glow-cyan-0985{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-0986{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-0987{border:1px solid rgba(0,234,255,0.15)}
.border-blue-0988{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-0989{background:rgba(0,234,255,0.11)}
.bg-blue-0990{background:rgba(45,123,255,0.02)}
.opacity-0991{opacity:0.11}
.radius-0992{border-radius:27px}
.glow-cyan-0993{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-0994{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-0995{border:1px solid rgba(0,234,255,0.09)}
.border-blue-0996{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-0997{background:rgba(0,234,255,0.09)}
.bg-blue-0998{background:rgba(45,123,255,0.10)}
.opacity-0999{opacity:0.19}
.radius-1000{border-radius:35px}
.glow-cyan-1001{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-1002{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-1003{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1004{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1005{background:rgba(0,234,255,0.07)}
.bg-blue-1006{background:rgba(45,123,255,0.08)}
.opacity-1007{opacity:0.27}
.radius-1008{border-radius:1px}
.glow-cyan-1009{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-1010{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-1011{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1012{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1013{background:rgba(0,234,255,0.05)}
.bg-blue-1014{background:rgba(45,123,255,0.06)}
.opacity-1015{opacity:0.35}
.radius-1016{border-radius:9px}
.glow-cyan-1017{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-1018{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-1019{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1020{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1021{background:rgba(0,234,255,0.03)}
.bg-blue-1022{background:rgba(45,123,255,0.04)}
.opacity-1023{opacity:0.43}
.radius-1024{border-radius:17px}
.glow-cyan-1025{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-1026{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-1027{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1028{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1029{background:rgba(0,234,255,0.11)}
.bg-blue-1030{background:rgba(45,123,255,0.02)}
.opacity-1031{opacity:0.51}
.radius-1032{border-radius:25px}
.glow-cyan-1033{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-1034{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-1035{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1036{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1037{background:rgba(0,234,255,0.09)}
.bg-blue-1038{background:rgba(45,123,255,0.10)}
.opacity-1039{opacity:0.59}
.radius-1040{border-radius:33px}
.glow-cyan-1041{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-1042{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-1043{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1044{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1045{background:rgba(0,234,255,0.07)}
.bg-blue-1046{background:rgba(45,123,255,0.08)}
.opacity-1047{opacity:0.67}
.radius-1048{border-radius:41px}
.glow-cyan-1049{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-1050{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-1051{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1052{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1053{background:rgba(0,234,255,0.05)}
.bg-blue-1054{background:rgba(45,123,255,0.06)}
.opacity-1055{opacity:0.75}
.radius-1056{border-radius:7px}
.glow-cyan-1057{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-1058{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-1059{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1060{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1061{background:rgba(0,234,255,0.03)}
.bg-blue-1062{background:rgba(45,123,255,0.04)}
.opacity-1063{opacity:0.83}
.radius-1064{border-radius:15px}
.glow-cyan-1065{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-1066{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-1067{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1068{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1069{background:rgba(0,234,255,0.11)}
.bg-blue-1070{background:rgba(45,123,255,0.02)}
.opacity-1071{opacity:0.91}
.radius-1072{border-radius:23px}
.glow-cyan-1073{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-1074{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-1075{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1076{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1077{background:rgba(0,234,255,0.09)}
.bg-blue-1078{background:rgba(45,123,255,0.10)}
.opacity-1079{opacity:0.99}
.radius-1080{border-radius:31px}
.glow-cyan-1081{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-1082{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-1083{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1084{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1085{background:rgba(0,234,255,0.07)}
.bg-blue-1086{background:rgba(45,123,255,0.08)}
.opacity-1087{opacity:0.17}
.radius-1088{border-radius:39px}
.glow-cyan-1089{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-1090{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-1091{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1092{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1093{background:rgba(0,234,255,0.05)}
.bg-blue-1094{background:rgba(45,123,255,0.06)}
.opacity-1095{opacity:0.25}
.radius-1096{border-radius:5px}
.glow-cyan-1097{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-1098{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-1099{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1100{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1101{background:rgba(0,234,255,0.03)}
.bg-blue-1102{background:rgba(45,123,255,0.04)}
.opacity-1103{opacity:0.33}
.radius-1104{border-radius:13px}
.glow-cyan-1105{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-1106{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-1107{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1108{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1109{background:rgba(0,234,255,0.11)}
.bg-blue-1110{background:rgba(45,123,255,0.02)}
.opacity-1111{opacity:0.41}
.radius-1112{border-radius:21px}
.glow-cyan-1113{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-1114{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-1115{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1116{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1117{background:rgba(0,234,255,0.09)}
.bg-blue-1118{background:rgba(45,123,255,0.10)}
.opacity-1119{opacity:0.49}
.radius-1120{border-radius:29px}
.glow-cyan-1121{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-1122{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-1123{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1124{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1125{background:rgba(0,234,255,0.07)}
.bg-blue-1126{background:rgba(45,123,255,0.08)}
.opacity-1127{opacity:0.57}
.radius-1128{border-radius:37px}
.glow-cyan-1129{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-1130{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-1131{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1132{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1133{background:rgba(0,234,255,0.05)}
.bg-blue-1134{background:rgba(45,123,255,0.06)}
.opacity-1135{opacity:0.65}
.radius-1136{border-radius:3px}
.glow-cyan-1137{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-1138{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-1139{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1140{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1141{background:rgba(0,234,255,0.03)}
.bg-blue-1142{background:rgba(45,123,255,0.04)}
.opacity-1143{opacity:0.73}
.radius-1144{border-radius:11px}
.glow-cyan-1145{box-shadow:0 0 5px rgba(0,234,255,0.13)}
.glow-blue-1146{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-1147{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1148{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1149{background:rgba(0,234,255,0.11)}
.bg-blue-1150{background:rgba(45,123,255,0.02)}
.opacity-1151{opacity:0.81}
.radius-1152{border-radius:19px}
.glow-cyan-1153{box-shadow:0 0 13px rgba(0,234,255,0.09)}
.glow-blue-1154{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-1155{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1156{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1157{background:rgba(0,234,255,0.09)}
.bg-blue-1158{background:rgba(45,123,255,0.10)}
.opacity-1159{opacity:0.89}
.radius-1160{border-radius:27px}
.glow-cyan-1161{box-shadow:0 0 21px rgba(0,234,255,0.17)}
.glow-blue-1162{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-1163{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1164{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1165{background:rgba(0,234,255,0.07)}
.bg-blue-1166{background:rgba(45,123,255,0.08)}
.opacity-1167{opacity:0.97}
.radius-1168{border-radius:35px}
.glow-cyan-1169{box-shadow:0 0 29px rgba(0,234,255,0.13)}
.glow-blue-1170{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-1171{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1172{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1173{background:rgba(0,234,255,0.05)}
.bg-blue-1174{background:rgba(45,123,255,0.06)}
.opacity-1175{opacity:0.15}
.radius-1176{border-radius:1px}
.glow-cyan-1177{box-shadow:0 0 11px rgba(0,234,255,0.09)}
.glow-blue-1178{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-1179{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1180{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1181{background:rgba(0,234,255,0.03)}
.bg-blue-1182{background:rgba(45,123,255,0.04)}
.opacity-1183{opacity:0.23}
.radius-1184{border-radius:9px}
.glow-cyan-1185{box-shadow:0 0 19px rgba(0,234,255,0.17)}
.glow-blue-1186{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-1187{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1188{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1189{background:rgba(0,234,255,0.11)}
.bg-blue-1190{background:rgba(45,123,255,0.02)}
.opacity-1191{opacity:0.31}
.radius-1192{border-radius:17px}
.glow-cyan-1193{box-shadow:0 0 27px rgba(0,234,255,0.13)}
.glow-blue-1194{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-1195{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1196{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1197{background:rgba(0,234,255,0.09)}
.bg-blue-1198{background:rgba(45,123,255,0.10)}
.opacity-1199{opacity:0.39}
.radius-1200{border-radius:25px}
.glow-cyan-1201{box-shadow:0 0 9px rgba(0,234,255,0.09)}
.glow-blue-1202{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-1203{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1204{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1205{background:rgba(0,234,255,0.07)}
.bg-blue-1206{background:rgba(45,123,255,0.08)}
.opacity-1207{opacity:0.47}
.radius-1208{border-radius:33px}
.glow-cyan-1209{box-shadow:0 0 17px rgba(0,234,255,0.17)}
.glow-blue-1210{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-1211{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1212{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1213{background:rgba(0,234,255,0.05)}
.bg-blue-1214{background:rgba(45,123,255,0.06)}
.opacity-1215{opacity:0.55}
.radius-1216{border-radius:41px}
.glow-cyan-1217{box-shadow:0 0 25px rgba(0,234,255,0.13)}
.glow-blue-1218{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-1219{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1220{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1221{background:rgba(0,234,255,0.03)}
.bg-blue-1222{background:rgba(45,123,255,0.04)}
.opacity-1223{opacity:0.63}
.radius-1224{border-radius:7px}
.glow-cyan-1225{box-shadow:0 0 7px rgba(0,234,255,0.09)}
.glow-blue-1226{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-1227{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1228{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1229{background:rgba(0,234,255,0.11)}
.bg-blue-1230{background:rgba(45,123,255,0.02)}
.opacity-1231{opacity:0.71}
.radius-1232{border-radius:15px}
.glow-cyan-1233{box-shadow:0 0 15px rgba(0,234,255,0.17)}
.glow-blue-1234{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-1235{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1236{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1237{background:rgba(0,234,255,0.09)}
.bg-blue-1238{background:rgba(45,123,255,0.10)}
.opacity-1239{opacity:0.79}
.radius-1240{border-radius:23px}
.glow-cyan-1241{box-shadow:0 0 23px rgba(0,234,255,0.13)}
.glow-blue-1242{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-1243{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1244{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1245{background:rgba(0,234,255,0.07)}
.bg-blue-1246{background:rgba(45,123,255,0.08)}
.opacity-1247{opacity:0.87}
.radius-1248{border-radius:31px}
.glow-cyan-1249{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-1250{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-1251{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1252{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1253{background:rgba(0,234,255,0.05)}
.bg-blue-1254{background:rgba(45,123,255,0.06)}
.opacity-1255{opacity:0.95}
.radius-1256{border-radius:39px}
.glow-cyan-1257{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-1258{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-1259{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1260{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1261{background:rgba(0,234,255,0.03)}
.bg-blue-1262{background:rgba(45,123,255,0.04)}
.opacity-1263{opacity:0.13}
.radius-1264{border-radius:5px}
.glow-cyan-1265{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-1266{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-1267{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1268{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1269{background:rgba(0,234,255,0.11)}
.bg-blue-1270{background:rgba(45,123,255,0.02)}
.opacity-1271{opacity:0.21}
.radius-1272{border-radius:13px}
.glow-cyan-1273{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-1274{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-1275{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1276{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1277{background:rgba(0,234,255,0.09)}
.bg-blue-1278{background:rgba(45,123,255,0.10)}
.opacity-1279{opacity:0.29}
.radius-1280{border-radius:21px}
.glow-cyan-1281{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-1282{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-1283{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1284{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1285{background:rgba(0,234,255,0.07)}
.bg-blue-1286{background:rgba(45,123,255,0.08)}
.opacity-1287{opacity:0.37}
.radius-1288{border-radius:29px}
.glow-cyan-1289{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-1290{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-1291{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1292{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1293{background:rgba(0,234,255,0.05)}
.bg-blue-1294{background:rgba(45,123,255,0.06)}
.opacity-1295{opacity:0.45}
.radius-1296{border-radius:37px}
.glow-cyan-1297{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-1298{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-1299{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1300{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1301{background:rgba(0,234,255,0.03)}
.bg-blue-1302{background:rgba(45,123,255,0.04)}
.opacity-1303{opacity:0.53}
.radius-1304{border-radius:3px}
.glow-cyan-1305{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-1306{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-1307{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1308{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1309{background:rgba(0,234,255,0.11)}
.bg-blue-1310{background:rgba(45,123,255,0.02)}
.opacity-1311{opacity:0.61}
.radius-1312{border-radius:11px}
.glow-cyan-1313{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-1314{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-1315{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1316{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1317{background:rgba(0,234,255,0.09)}
.bg-blue-1318{background:rgba(45,123,255,0.10)}
.opacity-1319{opacity:0.69}
.radius-1320{border-radius:19px}
.glow-cyan-1321{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-1322{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-1323{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1324{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1325{background:rgba(0,234,255,0.07)}
.bg-blue-1326{background:rgba(45,123,255,0.08)}
.opacity-1327{opacity:0.77}
.radius-1328{border-radius:27px}
.glow-cyan-1329{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-1330{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-1331{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1332{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1333{background:rgba(0,234,255,0.05)}
.bg-blue-1334{background:rgba(45,123,255,0.06)}
.opacity-1335{opacity:0.85}
.radius-1336{border-radius:35px}
.glow-cyan-1337{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-1338{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-1339{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1340{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1341{background:rgba(0,234,255,0.03)}
.bg-blue-1342{background:rgba(45,123,255,0.04)}
.opacity-1343{opacity:0.93}
.radius-1344{border-radius:1px}
.glow-cyan-1345{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-1346{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-1347{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1348{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1349{background:rgba(0,234,255,0.11)}
.bg-blue-1350{background:rgba(45,123,255,0.02)}
.opacity-1351{opacity:0.11}
.radius-1352{border-radius:9px}
.glow-cyan-1353{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-1354{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-1355{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1356{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1357{background:rgba(0,234,255,0.09)}
.bg-blue-1358{background:rgba(45,123,255,0.10)}
.opacity-1359{opacity:0.19}
.radius-1360{border-radius:17px}
.glow-cyan-1361{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-1362{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-1363{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1364{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1365{background:rgba(0,234,255,0.07)}
.bg-blue-1366{background:rgba(45,123,255,0.08)}
.opacity-1367{opacity:0.27}
.radius-1368{border-radius:25px}
.glow-cyan-1369{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-1370{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-1371{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1372{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1373{background:rgba(0,234,255,0.05)}
.bg-blue-1374{background:rgba(45,123,255,0.06)}
.opacity-1375{opacity:0.35}
.radius-1376{border-radius:33px}
.glow-cyan-1377{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-1378{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-1379{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1380{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1381{background:rgba(0,234,255,0.03)}
.bg-blue-1382{background:rgba(45,123,255,0.04)}
.opacity-1383{opacity:0.43}
.radius-1384{border-radius:41px}
.glow-cyan-1385{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-1386{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-1387{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1388{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1389{background:rgba(0,234,255,0.11)}
.bg-blue-1390{background:rgba(45,123,255,0.02)}
.opacity-1391{opacity:0.51}
.radius-1392{border-radius:7px}
.glow-cyan-1393{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-1394{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-1395{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1396{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1397{background:rgba(0,234,255,0.09)}
.bg-blue-1398{background:rgba(45,123,255,0.10)}
.opacity-1399{opacity:0.59}
.radius-1400{border-radius:15px}
.glow-cyan-1401{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-1402{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-1403{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1404{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1405{background:rgba(0,234,255,0.07)}
.bg-blue-1406{background:rgba(45,123,255,0.08)}
.opacity-1407{opacity:0.67}
.radius-1408{border-radius:23px}
.glow-cyan-1409{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-1410{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-1411{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1412{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1413{background:rgba(0,234,255,0.05)}
.bg-blue-1414{background:rgba(45,123,255,0.06)}
.opacity-1415{opacity:0.75}
.radius-1416{border-radius:31px}
.glow-cyan-1417{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-1418{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-1419{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1420{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1421{background:rgba(0,234,255,0.03)}
.bg-blue-1422{background:rgba(45,123,255,0.04)}
.opacity-1423{opacity:0.83}
.radius-1424{border-radius:39px}
.glow-cyan-1425{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-1426{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-1427{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1428{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1429{background:rgba(0,234,255,0.11)}
.bg-blue-1430{background:rgba(45,123,255,0.02)}
.opacity-1431{opacity:0.91}
.radius-1432{border-radius:5px}
.glow-cyan-1433{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-1434{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-1435{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1436{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1437{background:rgba(0,234,255,0.09)}
.bg-blue-1438{background:rgba(45,123,255,0.10)}
.opacity-1439{opacity:0.99}
.radius-1440{border-radius:13px}
.glow-cyan-1441{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-1442{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-1443{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1444{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1445{background:rgba(0,234,255,0.07)}
.bg-blue-1446{background:rgba(45,123,255,0.08)}
.opacity-1447{opacity:0.17}
.radius-1448{border-radius:21px}
.glow-cyan-1449{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-1450{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-1451{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1452{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1453{background:rgba(0,234,255,0.05)}
.bg-blue-1454{background:rgba(45,123,255,0.06)}
.opacity-1455{opacity:0.25}
.radius-1456{border-radius:29px}
.glow-cyan-1457{box-shadow:0 0 5px rgba(0,234,255,0.13)}
.glow-blue-1458{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-1459{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1460{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1461{background:rgba(0,234,255,0.03)}
.bg-blue-1462{background:rgba(45,123,255,0.04)}
.opacity-1463{opacity:0.33}
.radius-1464{border-radius:37px}
.glow-cyan-1465{box-shadow:0 0 13px rgba(0,234,255,0.09)}
.glow-blue-1466{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-1467{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1468{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1469{background:rgba(0,234,255,0.11)}
.bg-blue-1470{background:rgba(45,123,255,0.02)}
.opacity-1471{opacity:0.41}
.radius-1472{border-radius:3px}
.glow-cyan-1473{box-shadow:0 0 21px rgba(0,234,255,0.17)}
.glow-blue-1474{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-1475{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1476{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1477{background:rgba(0,234,255,0.09)}
.bg-blue-1478{background:rgba(45,123,255,0.10)}
.opacity-1479{opacity:0.49}
.radius-1480{border-radius:11px}
.glow-cyan-1481{box-shadow:0 0 29px rgba(0,234,255,0.13)}
.glow-blue-1482{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-1483{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1484{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1485{background:rgba(0,234,255,0.07)}
.bg-blue-1486{background:rgba(45,123,255,0.08)}
.opacity-1487{opacity:0.57}
.radius-1488{border-radius:19px}
.glow-cyan-1489{box-shadow:0 0 11px rgba(0,234,255,0.09)}
.glow-blue-1490{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-1491{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1492{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1493{background:rgba(0,234,255,0.05)}
.bg-blue-1494{background:rgba(45,123,255,0.06)}
.opacity-1495{opacity:0.65}
.radius-1496{border-radius:27px}
.glow-cyan-1497{box-shadow:0 0 19px rgba(0,234,255,0.17)}
.glow-blue-1498{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-1499{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1500{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1501{background:rgba(0,234,255,0.03)}
.bg-blue-1502{background:rgba(45,123,255,0.04)}
.opacity-1503{opacity:0.73}
.radius-1504{border-radius:35px}
.glow-cyan-1505{box-shadow:0 0 27px rgba(0,234,255,0.13)}
.glow-blue-1506{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-1507{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1508{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1509{background:rgba(0,234,255,0.11)}
.bg-blue-1510{background:rgba(45,123,255,0.02)}
.opacity-1511{opacity:0.81}
.radius-1512{border-radius:1px}
.glow-cyan-1513{box-shadow:0 0 9px rgba(0,234,255,0.09)}
.glow-blue-1514{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-1515{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1516{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1517{background:rgba(0,234,255,0.09)}
.bg-blue-1518{background:rgba(45,123,255,0.10)}
.opacity-1519{opacity:0.89}
.radius-1520{border-radius:9px}
.glow-cyan-1521{box-shadow:0 0 17px rgba(0,234,255,0.17)}
.glow-blue-1522{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-1523{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1524{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1525{background:rgba(0,234,255,0.07)}
.bg-blue-1526{background:rgba(45,123,255,0.08)}
.opacity-1527{opacity:0.97}
.radius-1528{border-radius:17px}
.glow-cyan-1529{box-shadow:0 0 25px rgba(0,234,255,0.13)}
.glow-blue-1530{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-1531{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1532{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1533{background:rgba(0,234,255,0.05)}
.bg-blue-1534{background:rgba(45,123,255,0.06)}
.opacity-1535{opacity:0.15}
.radius-1536{border-radius:25px}
.glow-cyan-1537{box-shadow:0 0 7px rgba(0,234,255,0.09)}
.glow-blue-1538{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-1539{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1540{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1541{background:rgba(0,234,255,0.03)}
.bg-blue-1542{background:rgba(45,123,255,0.04)}
.opacity-1543{opacity:0.23}
.radius-1544{border-radius:33px}
.glow-cyan-1545{box-shadow:0 0 15px rgba(0,234,255,0.17)}
.glow-blue-1546{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-1547{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1548{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1549{background:rgba(0,234,255,0.11)}
.bg-blue-1550{background:rgba(45,123,255,0.02)}
.opacity-1551{opacity:0.31}
.radius-1552{border-radius:41px}
.glow-cyan-1553{box-shadow:0 0 23px rgba(0,234,255,0.13)}
.glow-blue-1554{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-1555{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1556{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1557{background:rgba(0,234,255,0.09)}
.bg-blue-1558{background:rgba(45,123,255,0.10)}
.opacity-1559{opacity:0.39}
.radius-1560{border-radius:7px}
.glow-cyan-1561{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-1562{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-1563{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1564{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1565{background:rgba(0,234,255,0.07)}
.bg-blue-1566{background:rgba(45,123,255,0.08)}
.opacity-1567{opacity:0.47}
.radius-1568{border-radius:15px}
.glow-cyan-1569{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-1570{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-1571{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1572{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1573{background:rgba(0,234,255,0.05)}
.bg-blue-1574{background:rgba(45,123,255,0.06)}
.opacity-1575{opacity:0.55}
.radius-1576{border-radius:23px}
.glow-cyan-1577{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-1578{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-1579{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1580{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1581{background:rgba(0,234,255,0.03)}
.bg-blue-1582{background:rgba(45,123,255,0.04)}
.opacity-1583{opacity:0.63}
.radius-1584{border-radius:31px}
.glow-cyan-1585{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-1586{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-1587{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1588{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1589{background:rgba(0,234,255,0.11)}
.bg-blue-1590{background:rgba(45,123,255,0.02)}
.opacity-1591{opacity:0.71}
.radius-1592{border-radius:39px}
.glow-cyan-1593{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-1594{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-1595{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1596{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1597{background:rgba(0,234,255,0.09)}
.bg-blue-1598{background:rgba(45,123,255,0.10)}
.opacity-1599{opacity:0.79}
.radius-1600{border-radius:5px}
.glow-cyan-1601{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-1602{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-1603{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1604{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1605{background:rgba(0,234,255,0.07)}
.bg-blue-1606{background:rgba(45,123,255,0.08)}
.opacity-1607{opacity:0.87}
.radius-1608{border-radius:13px}
.glow-cyan-1609{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-1610{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-1611{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1612{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1613{background:rgba(0,234,255,0.05)}
.bg-blue-1614{background:rgba(45,123,255,0.06)}
.opacity-1615{opacity:0.95}
.radius-1616{border-radius:21px}
.glow-cyan-1617{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-1618{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-1619{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1620{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1621{background:rgba(0,234,255,0.03)}
.bg-blue-1622{background:rgba(45,123,255,0.04)}
.opacity-1623{opacity:0.13}
.radius-1624{border-radius:29px}
.glow-cyan-1625{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-1626{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-1627{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1628{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1629{background:rgba(0,234,255,0.11)}
.bg-blue-1630{background:rgba(45,123,255,0.02)}
.opacity-1631{opacity:0.21}
.radius-1632{border-radius:37px}
.glow-cyan-1633{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-1634{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-1635{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1636{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1637{background:rgba(0,234,255,0.09)}
.bg-blue-1638{background:rgba(45,123,255,0.10)}
.opacity-1639{opacity:0.29}
.radius-1640{border-radius:3px}
.glow-cyan-1641{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-1642{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-1643{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1644{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1645{background:rgba(0,234,255,0.07)}
.bg-blue-1646{background:rgba(45,123,255,0.08)}
.opacity-1647{opacity:0.37}
.radius-1648{border-radius:11px}
.glow-cyan-1649{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-1650{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-1651{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1652{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1653{background:rgba(0,234,255,0.05)}
.bg-blue-1654{background:rgba(45,123,255,0.06)}
.opacity-1655{opacity:0.45}
.radius-1656{border-radius:19px}
.glow-cyan-1657{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-1658{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-1659{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1660{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1661{background:rgba(0,234,255,0.03)}
.bg-blue-1662{background:rgba(45,123,255,0.04)}
.opacity-1663{opacity:0.53}
.radius-1664{border-radius:27px}
.glow-cyan-1665{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-1666{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-1667{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1668{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1669{background:rgba(0,234,255,0.11)}
.bg-blue-1670{background:rgba(45,123,255,0.02)}
.opacity-1671{opacity:0.61}
.radius-1672{border-radius:35px}
.glow-cyan-1673{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-1674{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-1675{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1676{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1677{background:rgba(0,234,255,0.09)}
.bg-blue-1678{background:rgba(45,123,255,0.10)}
.opacity-1679{opacity:0.69}
.radius-1680{border-radius:1px}
.glow-cyan-1681{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-1682{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-1683{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1684{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1685{background:rgba(0,234,255,0.07)}
.bg-blue-1686{background:rgba(45,123,255,0.08)}
.opacity-1687{opacity:0.77}
.radius-1688{border-radius:9px}
.glow-cyan-1689{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-1690{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-1691{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1692{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1693{background:rgba(0,234,255,0.05)}
.bg-blue-1694{background:rgba(45,123,255,0.06)}
.opacity-1695{opacity:0.85}
.radius-1696{border-radius:17px}
.glow-cyan-1697{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-1698{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-1699{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1700{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1701{background:rgba(0,234,255,0.03)}
.bg-blue-1702{background:rgba(45,123,255,0.04)}
.opacity-1703{opacity:0.93}
.radius-1704{border-radius:25px}
.glow-cyan-1705{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-1706{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-1707{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1708{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1709{background:rgba(0,234,255,0.11)}
.bg-blue-1710{background:rgba(45,123,255,0.02)}
.opacity-1711{opacity:0.11}
.radius-1712{border-radius:33px}
.glow-cyan-1713{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-1714{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-1715{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1716{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1717{background:rgba(0,234,255,0.09)}
.bg-blue-1718{background:rgba(45,123,255,0.10)}
.opacity-1719{opacity:0.19}
.radius-1720{border-radius:41px}
.glow-cyan-1721{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-1722{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-1723{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1724{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1725{background:rgba(0,234,255,0.07)}
.bg-blue-1726{background:rgba(45,123,255,0.08)}
.opacity-1727{opacity:0.27}
.radius-1728{border-radius:7px}
.glow-cyan-1729{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-1730{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-1731{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1732{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1733{background:rgba(0,234,255,0.05)}
.bg-blue-1734{background:rgba(45,123,255,0.06)}
.opacity-1735{opacity:0.35}
.radius-1736{border-radius:15px}
.glow-cyan-1737{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-1738{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-1739{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1740{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1741{background:rgba(0,234,255,0.03)}
.bg-blue-1742{background:rgba(45,123,255,0.04)}
.opacity-1743{opacity:0.43}
.radius-1744{border-radius:23px}
.glow-cyan-1745{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-1746{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-1747{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1748{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1749{background:rgba(0,234,255,0.11)}
.bg-blue-1750{background:rgba(45,123,255,0.02)}
.opacity-1751{opacity:0.51}
.radius-1752{border-radius:31px}
.glow-cyan-1753{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-1754{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-1755{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1756{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1757{background:rgba(0,234,255,0.09)}
.bg-blue-1758{background:rgba(45,123,255,0.10)}
.opacity-1759{opacity:0.59}
.radius-1760{border-radius:39px}
.glow-cyan-1761{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-1762{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-1763{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1764{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1765{background:rgba(0,234,255,0.07)}
.bg-blue-1766{background:rgba(45,123,255,0.08)}
.opacity-1767{opacity:0.67}
.radius-1768{border-radius:5px}
.glow-cyan-1769{box-shadow:0 0 5px rgba(0,234,255,0.13)}
.glow-blue-1770{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-1771{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1772{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1773{background:rgba(0,234,255,0.05)}
.bg-blue-1774{background:rgba(45,123,255,0.06)}
.opacity-1775{opacity:0.75}
.radius-1776{border-radius:13px}
.glow-cyan-1777{box-shadow:0 0 13px rgba(0,234,255,0.09)}
.glow-blue-1778{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-1779{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1780{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1781{background:rgba(0,234,255,0.03)}
.bg-blue-1782{background:rgba(45,123,255,0.04)}
.opacity-1783{opacity:0.83}
.radius-1784{border-radius:21px}
.glow-cyan-1785{box-shadow:0 0 21px rgba(0,234,255,0.17)}
.glow-blue-1786{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-1787{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1788{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1789{background:rgba(0,234,255,0.11)}
.bg-blue-1790{background:rgba(45,123,255,0.02)}
.opacity-1791{opacity:0.91}
.radius-1792{border-radius:29px}
.glow-cyan-1793{box-shadow:0 0 29px rgba(0,234,255,0.13)}
.glow-blue-1794{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-1795{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1796{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1797{background:rgba(0,234,255,0.09)}
.bg-blue-1798{background:rgba(45,123,255,0.10)}
.opacity-1799{opacity:0.99}
.radius-1800{border-radius:37px}
.glow-cyan-1801{box-shadow:0 0 11px rgba(0,234,255,0.09)}
.glow-blue-1802{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-1803{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1804{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1805{background:rgba(0,234,255,0.07)}
.bg-blue-1806{background:rgba(45,123,255,0.08)}
.opacity-1807{opacity:0.17}
.radius-1808{border-radius:3px}
.glow-cyan-1809{box-shadow:0 0 19px rgba(0,234,255,0.17)}
.glow-blue-1810{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-1811{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1812{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1813{background:rgba(0,234,255,0.05)}
.bg-blue-1814{background:rgba(45,123,255,0.06)}
.opacity-1815{opacity:0.25}
.radius-1816{border-radius:11px}
.glow-cyan-1817{box-shadow:0 0 27px rgba(0,234,255,0.13)}
.glow-blue-1818{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-1819{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1820{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1821{background:rgba(0,234,255,0.03)}
.bg-blue-1822{background:rgba(45,123,255,0.04)}
.opacity-1823{opacity:0.33}
.radius-1824{border-radius:19px}
.glow-cyan-1825{box-shadow:0 0 9px rgba(0,234,255,0.09)}
.glow-blue-1826{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-1827{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1828{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1829{background:rgba(0,234,255,0.11)}
.bg-blue-1830{background:rgba(45,123,255,0.02)}
.opacity-1831{opacity:0.41}
.radius-1832{border-radius:27px}
.glow-cyan-1833{box-shadow:0 0 17px rgba(0,234,255,0.17)}
.glow-blue-1834{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-1835{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1836{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1837{background:rgba(0,234,255,0.09)}
.bg-blue-1838{background:rgba(45,123,255,0.10)}
.opacity-1839{opacity:0.49}
.radius-1840{border-radius:35px}
.glow-cyan-1841{box-shadow:0 0 25px rgba(0,234,255,0.13)}
.glow-blue-1842{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-1843{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1844{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1845{background:rgba(0,234,255,0.07)}
.bg-blue-1846{background:rgba(45,123,255,0.08)}
.opacity-1847{opacity:0.57}
.radius-1848{border-radius:1px}
.glow-cyan-1849{box-shadow:0 0 7px rgba(0,234,255,0.09)}
.glow-blue-1850{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-1851{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1852{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1853{background:rgba(0,234,255,0.05)}
.bg-blue-1854{background:rgba(45,123,255,0.06)}
.opacity-1855{opacity:0.65}
.radius-1856{border-radius:9px}
.glow-cyan-1857{box-shadow:0 0 15px rgba(0,234,255,0.17)}
.glow-blue-1858{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-1859{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1860{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1861{background:rgba(0,234,255,0.03)}
.bg-blue-1862{background:rgba(45,123,255,0.04)}
.opacity-1863{opacity:0.73}
.radius-1864{border-radius:17px}
.glow-cyan-1865{box-shadow:0 0 23px rgba(0,234,255,0.13)}
.glow-blue-1866{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-1867{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1868{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1869{background:rgba(0,234,255,0.11)}
.bg-blue-1870{background:rgba(45,123,255,0.02)}
.opacity-1871{opacity:0.81}
.radius-1872{border-radius:25px}
.glow-cyan-1873{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-1874{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-1875{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1876{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1877{background:rgba(0,234,255,0.09)}
.bg-blue-1878{background:rgba(45,123,255,0.10)}
.opacity-1879{opacity:0.89}
.radius-1880{border-radius:33px}
.glow-cyan-1881{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-1882{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-1883{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1884{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1885{background:rgba(0,234,255,0.07)}
.bg-blue-1886{background:rgba(45,123,255,0.08)}
.opacity-1887{opacity:0.97}
.radius-1888{border-radius:41px}
.glow-cyan-1889{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-1890{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-1891{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1892{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1893{background:rgba(0,234,255,0.05)}
.bg-blue-1894{background:rgba(45,123,255,0.06)}
.opacity-1895{opacity:0.15}
.radius-1896{border-radius:7px}
.glow-cyan-1897{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-1898{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-1899{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1900{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1901{background:rgba(0,234,255,0.03)}
.bg-blue-1902{background:rgba(45,123,255,0.04)}
.opacity-1903{opacity:0.23}
.radius-1904{border-radius:15px}
.glow-cyan-1905{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-1906{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-1907{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1908{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1909{background:rgba(0,234,255,0.11)}
.bg-blue-1910{background:rgba(45,123,255,0.02)}
.opacity-1911{opacity:0.31}
.radius-1912{border-radius:23px}
.glow-cyan-1913{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-1914{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-1915{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1916{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1917{background:rgba(0,234,255,0.09)}
.bg-blue-1918{background:rgba(45,123,255,0.10)}
.opacity-1919{opacity:0.39}
.radius-1920{border-radius:31px}
.glow-cyan-1921{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-1922{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-1923{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1924{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1925{background:rgba(0,234,255,0.07)}
.bg-blue-1926{background:rgba(45,123,255,0.08)}
.opacity-1927{opacity:0.47}
.radius-1928{border-radius:39px}
.glow-cyan-1929{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-1930{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-1931{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1932{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1933{background:rgba(0,234,255,0.05)}
.bg-blue-1934{background:rgba(45,123,255,0.06)}
.opacity-1935{opacity:0.55}
.radius-1936{border-radius:5px}
.glow-cyan-1937{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-1938{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-1939{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1940{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1941{background:rgba(0,234,255,0.03)}
.bg-blue-1942{background:rgba(45,123,255,0.04)}
.opacity-1943{opacity:0.63}
.radius-1944{border-radius:13px}
.glow-cyan-1945{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-1946{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-1947{border:1px solid rgba(0,234,255,0.09)}
.border-blue-1948{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-1949{background:rgba(0,234,255,0.11)}
.bg-blue-1950{background:rgba(45,123,255,0.02)}
.opacity-1951{opacity:0.71}
.radius-1952{border-radius:21px}
.glow-cyan-1953{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-1954{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-1955{border:1px solid rgba(0,234,255,0.17)}
.border-blue-1956{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-1957{background:rgba(0,234,255,0.09)}
.bg-blue-1958{background:rgba(45,123,255,0.10)}
.opacity-1959{opacity:0.79}
.radius-1960{border-radius:29px}
.glow-cyan-1961{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-1962{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-1963{border:1px solid rgba(0,234,255,0.11)}
.border-blue-1964{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-1965{background:rgba(0,234,255,0.07)}
.bg-blue-1966{background:rgba(45,123,255,0.08)}
.opacity-1967{opacity:0.87}
.radius-1968{border-radius:37px}
.glow-cyan-1969{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-1970{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-1971{border:1px solid rgba(0,234,255,0.19)}
.border-blue-1972{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-1973{background:rgba(0,234,255,0.05)}
.bg-blue-1974{background:rgba(45,123,255,0.06)}
.opacity-1975{opacity:0.95}
.radius-1976{border-radius:3px}
.glow-cyan-1977{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-1978{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-1979{border:1px solid rgba(0,234,255,0.13)}
.border-blue-1980{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-1981{background:rgba(0,234,255,0.03)}
.bg-blue-1982{background:rgba(45,123,255,0.04)}
.opacity-1983{opacity:0.13}
.radius-1984{border-radius:11px}
.glow-cyan-1985{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-1986{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-1987{border:1px solid rgba(0,234,255,0.21)}
.border-blue-1988{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-1989{background:rgba(0,234,255,0.11)}
.bg-blue-1990{background:rgba(45,123,255,0.02)}
.opacity-1991{opacity:0.21}
.radius-1992{border-radius:19px}
.glow-cyan-1993{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-1994{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-1995{border:1px solid rgba(0,234,255,0.15)}
.border-blue-1996{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-1997{background:rgba(0,234,255,0.09)}
.bg-blue-1998{background:rgba(45,123,255,0.10)}
.opacity-1999{opacity:0.29}
.radius-2000{border-radius:27px}
.glow-cyan-2001{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-2002{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-2003{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2004{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2005{background:rgba(0,234,255,0.07)}
.bg-blue-2006{background:rgba(45,123,255,0.08)}
.opacity-2007{opacity:0.37}
.radius-2008{border-radius:35px}
.glow-cyan-2009{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-2010{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-2011{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2012{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2013{background:rgba(0,234,255,0.05)}
.bg-blue-2014{background:rgba(45,123,255,0.06)}
.opacity-2015{opacity:0.45}
.radius-2016{border-radius:1px}
.glow-cyan-2017{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-2018{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-2019{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2020{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2021{background:rgba(0,234,255,0.03)}
.bg-blue-2022{background:rgba(45,123,255,0.04)}
.opacity-2023{opacity:0.53}
.radius-2024{border-radius:9px}
.glow-cyan-2025{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-2026{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-2027{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2028{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2029{background:rgba(0,234,255,0.11)}
.bg-blue-2030{background:rgba(45,123,255,0.02)}
.opacity-2031{opacity:0.61}
.radius-2032{border-radius:17px}
.glow-cyan-2033{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-2034{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-2035{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2036{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2037{background:rgba(0,234,255,0.09)}
.bg-blue-2038{background:rgba(45,123,255,0.10)}
.opacity-2039{opacity:0.69}
.radius-2040{border-radius:25px}
.glow-cyan-2041{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-2042{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-2043{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2044{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2045{background:rgba(0,234,255,0.07)}
.bg-blue-2046{background:rgba(45,123,255,0.08)}
.opacity-2047{opacity:0.77}
.radius-2048{border-radius:33px}
.glow-cyan-2049{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-2050{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-2051{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2052{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2053{background:rgba(0,234,255,0.05)}
.bg-blue-2054{background:rgba(45,123,255,0.06)}
.opacity-2055{opacity:0.85}
.radius-2056{border-radius:41px}
.glow-cyan-2057{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-2058{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-2059{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2060{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2061{background:rgba(0,234,255,0.03)}
.bg-blue-2062{background:rgba(45,123,255,0.04)}
.opacity-2063{opacity:0.93}
.radius-2064{border-radius:7px}
.glow-cyan-2065{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-2066{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-2067{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2068{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2069{background:rgba(0,234,255,0.11)}
.bg-blue-2070{background:rgba(45,123,255,0.02)}
.opacity-2071{opacity:0.11}
.radius-2072{border-radius:15px}
.glow-cyan-2073{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-2074{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-2075{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2076{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2077{background:rgba(0,234,255,0.09)}
.bg-blue-2078{background:rgba(45,123,255,0.10)}
.opacity-2079{opacity:0.19}
.radius-2080{border-radius:23px}
.glow-cyan-2081{box-shadow:0 0 5px rgba(0,234,255,0.13)}
.glow-blue-2082{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-2083{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2084{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2085{background:rgba(0,234,255,0.07)}
.bg-blue-2086{background:rgba(45,123,255,0.08)}
.opacity-2087{opacity:0.27}
.radius-2088{border-radius:31px}
.glow-cyan-2089{box-shadow:0 0 13px rgba(0,234,255,0.09)}
.glow-blue-2090{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-2091{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2092{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2093{background:rgba(0,234,255,0.05)}
.bg-blue-2094{background:rgba(45,123,255,0.06)}
.opacity-2095{opacity:0.35}
.radius-2096{border-radius:39px}
.glow-cyan-2097{box-shadow:0 0 21px rgba(0,234,255,0.17)}
.glow-blue-2098{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-2099{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2100{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2101{background:rgba(0,234,255,0.03)}
.bg-blue-2102{background:rgba(45,123,255,0.04)}
.opacity-2103{opacity:0.43}
.radius-2104{border-radius:5px}
.glow-cyan-2105{box-shadow:0 0 29px rgba(0,234,255,0.13)}
.glow-blue-2106{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-2107{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2108{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2109{background:rgba(0,234,255,0.11)}
.bg-blue-2110{background:rgba(45,123,255,0.02)}
.opacity-2111{opacity:0.51}
.radius-2112{border-radius:13px}
.glow-cyan-2113{box-shadow:0 0 11px rgba(0,234,255,0.09)}
.glow-blue-2114{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-2115{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2116{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2117{background:rgba(0,234,255,0.09)}
.bg-blue-2118{background:rgba(45,123,255,0.10)}
.opacity-2119{opacity:0.59}
.radius-2120{border-radius:21px}
.glow-cyan-2121{box-shadow:0 0 19px rgba(0,234,255,0.17)}
.glow-blue-2122{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-2123{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2124{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2125{background:rgba(0,234,255,0.07)}
.bg-blue-2126{background:rgba(45,123,255,0.08)}
.opacity-2127{opacity:0.67}
.radius-2128{border-radius:29px}
.glow-cyan-2129{box-shadow:0 0 27px rgba(0,234,255,0.13)}
.glow-blue-2130{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-2131{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2132{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2133{background:rgba(0,234,255,0.05)}
.bg-blue-2134{background:rgba(45,123,255,0.06)}
.opacity-2135{opacity:0.75}
.radius-2136{border-radius:37px}
.glow-cyan-2137{box-shadow:0 0 9px rgba(0,234,255,0.09)}
.glow-blue-2138{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-2139{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2140{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2141{background:rgba(0,234,255,0.03)}
.bg-blue-2142{background:rgba(45,123,255,0.04)}
.opacity-2143{opacity:0.83}
.radius-2144{border-radius:3px}
.glow-cyan-2145{box-shadow:0 0 17px rgba(0,234,255,0.17)}
.glow-blue-2146{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-2147{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2148{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2149{background:rgba(0,234,255,0.11)}
.bg-blue-2150{background:rgba(45,123,255,0.02)}
.opacity-2151{opacity:0.91}
.radius-2152{border-radius:11px}
.glow-cyan-2153{box-shadow:0 0 25px rgba(0,234,255,0.13)}
.glow-blue-2154{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-2155{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2156{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2157{background:rgba(0,234,255,0.09)}
.bg-blue-2158{background:rgba(45,123,255,0.10)}
.opacity-2159{opacity:0.99}
.radius-2160{border-radius:19px}
.glow-cyan-2161{box-shadow:0 0 7px rgba(0,234,255,0.09)}
.glow-blue-2162{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-2163{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2164{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2165{background:rgba(0,234,255,0.07)}
.bg-blue-2166{background:rgba(45,123,255,0.08)}
.opacity-2167{opacity:0.17}
.radius-2168{border-radius:27px}
.glow-cyan-2169{box-shadow:0 0 15px rgba(0,234,255,0.17)}
.glow-blue-2170{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-2171{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2172{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2173{background:rgba(0,234,255,0.05)}
.bg-blue-2174{background:rgba(45,123,255,0.06)}
.opacity-2175{opacity:0.25}
.radius-2176{border-radius:35px}
.glow-cyan-2177{box-shadow:0 0 23px rgba(0,234,255,0.13)}
.glow-blue-2178{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-2179{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2180{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2181{background:rgba(0,234,255,0.03)}
.bg-blue-2182{background:rgba(45,123,255,0.04)}
.opacity-2183{opacity:0.33}
.radius-2184{border-radius:1px}
.glow-cyan-2185{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-2186{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-2187{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2188{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2189{background:rgba(0,234,255,0.11)}
.bg-blue-2190{background:rgba(45,123,255,0.02)}
.opacity-2191{opacity:0.41}
.radius-2192{border-radius:9px}
.glow-cyan-2193{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-2194{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-2195{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2196{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2197{background:rgba(0,234,255,0.09)}
.bg-blue-2198{background:rgba(45,123,255,0.10)}
.opacity-2199{opacity:0.49}
.radius-2200{border-radius:17px}
.glow-cyan-2201{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-2202{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-2203{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2204{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2205{background:rgba(0,234,255,0.07)}
.bg-blue-2206{background:rgba(45,123,255,0.08)}
.opacity-2207{opacity:0.57}
.radius-2208{border-radius:25px}
.glow-cyan-2209{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-2210{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-2211{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2212{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2213{background:rgba(0,234,255,0.05)}
.bg-blue-2214{background:rgba(45,123,255,0.06)}
.opacity-2215{opacity:0.65}
.radius-2216{border-radius:33px}
.glow-cyan-2217{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-2218{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-2219{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2220{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2221{background:rgba(0,234,255,0.03)}
.bg-blue-2222{background:rgba(45,123,255,0.04)}
.opacity-2223{opacity:0.73}
.radius-2224{border-radius:41px}
.glow-cyan-2225{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-2226{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-2227{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2228{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2229{background:rgba(0,234,255,0.11)}
.bg-blue-2230{background:rgba(45,123,255,0.02)}
.opacity-2231{opacity:0.81}
.radius-2232{border-radius:7px}
.glow-cyan-2233{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-2234{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-2235{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2236{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2237{background:rgba(0,234,255,0.09)}
.bg-blue-2238{background:rgba(45,123,255,0.10)}
.opacity-2239{opacity:0.89}
.radius-2240{border-radius:15px}
.glow-cyan-2241{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-2242{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-2243{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2244{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2245{background:rgba(0,234,255,0.07)}
.bg-blue-2246{background:rgba(45,123,255,0.08)}
.opacity-2247{opacity:0.97}
.radius-2248{border-radius:23px}
.glow-cyan-2249{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-2250{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-2251{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2252{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2253{background:rgba(0,234,255,0.05)}
.bg-blue-2254{background:rgba(45,123,255,0.06)}
.opacity-2255{opacity:0.15}
.radius-2256{border-radius:31px}
.glow-cyan-2257{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-2258{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-2259{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2260{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2261{background:rgba(0,234,255,0.03)}
.bg-blue-2262{background:rgba(45,123,255,0.04)}
.opacity-2263{opacity:0.23}
.radius-2264{border-radius:39px}
.glow-cyan-2265{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-2266{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-2267{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2268{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2269{background:rgba(0,234,255,0.11)}
.bg-blue-2270{background:rgba(45,123,255,0.02)}
.opacity-2271{opacity:0.31}
.radius-2272{border-radius:5px}
.glow-cyan-2273{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-2274{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-2275{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2276{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2277{background:rgba(0,234,255,0.09)}
.bg-blue-2278{background:rgba(45,123,255,0.10)}
.opacity-2279{opacity:0.39}
.radius-2280{border-radius:13px}
.glow-cyan-2281{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-2282{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-2283{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2284{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2285{background:rgba(0,234,255,0.07)}
.bg-blue-2286{background:rgba(45,123,255,0.08)}
.opacity-2287{opacity:0.47}
.radius-2288{border-radius:21px}
.glow-cyan-2289{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-2290{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-2291{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2292{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2293{background:rgba(0,234,255,0.05)}
.bg-blue-2294{background:rgba(45,123,255,0.06)}
.opacity-2295{opacity:0.55}
.radius-2296{border-radius:29px}
.glow-cyan-2297{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-2298{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-2299{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2300{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2301{background:rgba(0,234,255,0.03)}
.bg-blue-2302{background:rgba(45,123,255,0.04)}
.opacity-2303{opacity:0.63}
.radius-2304{border-radius:37px}
.glow-cyan-2305{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-2306{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-2307{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2308{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2309{background:rgba(0,234,255,0.11)}
.bg-blue-2310{background:rgba(45,123,255,0.02)}
.opacity-2311{opacity:0.71}
.radius-2312{border-radius:3px}
.glow-cyan-2313{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-2314{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-2315{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2316{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2317{background:rgba(0,234,255,0.09)}
.bg-blue-2318{background:rgba(45,123,255,0.10)}
.opacity-2319{opacity:0.79}
.radius-2320{border-radius:11px}
.glow-cyan-2321{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-2322{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-2323{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2324{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2325{background:rgba(0,234,255,0.07)}
.bg-blue-2326{background:rgba(45,123,255,0.08)}
.opacity-2327{opacity:0.87}
.radius-2328{border-radius:19px}
.glow-cyan-2329{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-2330{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-2331{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2332{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2333{background:rgba(0,234,255,0.05)}
.bg-blue-2334{background:rgba(45,123,255,0.06)}
.opacity-2335{opacity:0.95}
.radius-2336{border-radius:27px}
.glow-cyan-2337{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-2338{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-2339{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2340{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2341{background:rgba(0,234,255,0.03)}
.bg-blue-2342{background:rgba(45,123,255,0.04)}
.opacity-2343{opacity:0.13}
.radius-2344{border-radius:35px}
.glow-cyan-2345{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-2346{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-2347{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2348{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2349{background:rgba(0,234,255,0.11)}
.bg-blue-2350{background:rgba(45,123,255,0.02)}
.opacity-2351{opacity:0.21}
.radius-2352{border-radius:1px}
.glow-cyan-2353{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-2354{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-2355{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2356{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2357{background:rgba(0,234,255,0.09)}
.bg-blue-2358{background:rgba(45,123,255,0.10)}
.opacity-2359{opacity:0.29}
.radius-2360{border-radius:9px}
.glow-cyan-2361{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-2362{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-2363{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2364{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2365{background:rgba(0,234,255,0.07)}
.bg-blue-2366{background:rgba(45,123,255,0.08)}
.opacity-2367{opacity:0.37}
.radius-2368{border-radius:17px}
.glow-cyan-2369{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-2370{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-2371{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2372{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2373{background:rgba(0,234,255,0.05)}
.bg-blue-2374{background:rgba(45,123,255,0.06)}
.opacity-2375{opacity:0.45}
.radius-2376{border-radius:25px}
.glow-cyan-2377{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-2378{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-2379{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2380{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2381{background:rgba(0,234,255,0.03)}
.bg-blue-2382{background:rgba(45,123,255,0.04)}
.opacity-2383{opacity:0.53}
.radius-2384{border-radius:33px}
.glow-cyan-2385{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-2386{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-2387{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2388{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2389{background:rgba(0,234,255,0.11)}
.bg-blue-2390{background:rgba(45,123,255,0.02)}
.opacity-2391{opacity:0.61}
.radius-2392{border-radius:41px}
.glow-cyan-2393{box-shadow:0 0 5px rgba(0,234,255,0.13)}
.glow-blue-2394{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-2395{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2396{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2397{background:rgba(0,234,255,0.09)}
.bg-blue-2398{background:rgba(45,123,255,0.10)}
.opacity-2399{opacity:0.69}
.radius-2400{border-radius:7px}
.glow-cyan-2401{box-shadow:0 0 13px rgba(0,234,255,0.09)}
.glow-blue-2402{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-2403{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2404{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2405{background:rgba(0,234,255,0.07)}
.bg-blue-2406{background:rgba(45,123,255,0.08)}
.opacity-2407{opacity:0.77}
.radius-2408{border-radius:15px}
.glow-cyan-2409{box-shadow:0 0 21px rgba(0,234,255,0.17)}
.glow-blue-2410{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-2411{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2412{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2413{background:rgba(0,234,255,0.05)}
.bg-blue-2414{background:rgba(45,123,255,0.06)}
.opacity-2415{opacity:0.85}
.radius-2416{border-radius:23px}
.glow-cyan-2417{box-shadow:0 0 29px rgba(0,234,255,0.13)}
.glow-blue-2418{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-2419{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2420{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2421{background:rgba(0,234,255,0.03)}
.bg-blue-2422{background:rgba(45,123,255,0.04)}
.opacity-2423{opacity:0.93}
.radius-2424{border-radius:31px}
.glow-cyan-2425{box-shadow:0 0 11px rgba(0,234,255,0.09)}
.glow-blue-2426{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-2427{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2428{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2429{background:rgba(0,234,255,0.11)}
.bg-blue-2430{background:rgba(45,123,255,0.02)}
.opacity-2431{opacity:0.11}
.radius-2432{border-radius:39px}
.glow-cyan-2433{box-shadow:0 0 19px rgba(0,234,255,0.17)}
.glow-blue-2434{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-2435{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2436{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2437{background:rgba(0,234,255,0.09)}
.bg-blue-2438{background:rgba(45,123,255,0.10)}
.opacity-2439{opacity:0.19}
.radius-2440{border-radius:5px}
.glow-cyan-2441{box-shadow:0 0 27px rgba(0,234,255,0.13)}
.glow-blue-2442{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-2443{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2444{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2445{background:rgba(0,234,255,0.07)}
.bg-blue-2446{background:rgba(45,123,255,0.08)}
.opacity-2447{opacity:0.27}
.radius-2448{border-radius:13px}
.glow-cyan-2449{box-shadow:0 0 9px rgba(0,234,255,0.09)}
.glow-blue-2450{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-2451{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2452{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2453{background:rgba(0,234,255,0.05)}
.bg-blue-2454{background:rgba(45,123,255,0.06)}
.opacity-2455{opacity:0.35}
.radius-2456{border-radius:21px}
.glow-cyan-2457{box-shadow:0 0 17px rgba(0,234,255,0.17)}
.glow-blue-2458{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-2459{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2460{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2461{background:rgba(0,234,255,0.03)}
.bg-blue-2462{background:rgba(45,123,255,0.04)}
.opacity-2463{opacity:0.43}
.radius-2464{border-radius:29px}
.glow-cyan-2465{box-shadow:0 0 25px rgba(0,234,255,0.13)}
.glow-blue-2466{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-2467{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2468{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2469{background:rgba(0,234,255,0.11)}
.bg-blue-2470{background:rgba(45,123,255,0.02)}
.opacity-2471{opacity:0.51}
.radius-2472{border-radius:37px}
.glow-cyan-2473{box-shadow:0 0 7px rgba(0,234,255,0.09)}
.glow-blue-2474{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-2475{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2476{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2477{background:rgba(0,234,255,0.09)}
.bg-blue-2478{background:rgba(45,123,255,0.10)}
.opacity-2479{opacity:0.59}
.radius-2480{border-radius:3px}
.glow-cyan-2481{box-shadow:0 0 15px rgba(0,234,255,0.17)}
.glow-blue-2482{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-2483{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2484{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2485{background:rgba(0,234,255,0.07)}
.bg-blue-2486{background:rgba(45,123,255,0.08)}
.opacity-2487{opacity:0.67}
.radius-2488{border-radius:11px}
.glow-cyan-2489{box-shadow:0 0 23px rgba(0,234,255,0.13)}
.glow-blue-2490{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-2491{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2492{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2493{background:rgba(0,234,255,0.05)}
.bg-blue-2494{background:rgba(45,123,255,0.06)}
.opacity-2495{opacity:0.75}
.radius-2496{border-radius:19px}
.glow-cyan-2497{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-2498{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-2499{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2500{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2501{background:rgba(0,234,255,0.03)}
.bg-blue-2502{background:rgba(45,123,255,0.04)}
.opacity-2503{opacity:0.83}
.radius-2504{border-radius:27px}
.glow-cyan-2505{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-2506{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-2507{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2508{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2509{background:rgba(0,234,255,0.11)}
.bg-blue-2510{background:rgba(45,123,255,0.02)}
.opacity-2511{opacity:0.91}
.radius-2512{border-radius:35px}
.glow-cyan-2513{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-2514{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-2515{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2516{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2517{background:rgba(0,234,255,0.09)}
.bg-blue-2518{background:rgba(45,123,255,0.10)}
.opacity-2519{opacity:0.99}
.radius-2520{border-radius:1px}
.glow-cyan-2521{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-2522{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-2523{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2524{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2525{background:rgba(0,234,255,0.07)}
.bg-blue-2526{background:rgba(45,123,255,0.08)}
.opacity-2527{opacity:0.17}
.radius-2528{border-radius:9px}
.glow-cyan-2529{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-2530{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-2531{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2532{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2533{background:rgba(0,234,255,0.05)}
.bg-blue-2534{background:rgba(45,123,255,0.06)}
.opacity-2535{opacity:0.25}
.radius-2536{border-radius:17px}
.glow-cyan-2537{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-2538{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-2539{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2540{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2541{background:rgba(0,234,255,0.03)}
.bg-blue-2542{background:rgba(45,123,255,0.04)}
.opacity-2543{opacity:0.33}
.radius-2544{border-radius:25px}
.glow-cyan-2545{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-2546{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-2547{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2548{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2549{background:rgba(0,234,255,0.11)}
.bg-blue-2550{background:rgba(45,123,255,0.02)}
.opacity-2551{opacity:0.41}
.radius-2552{border-radius:33px}
.glow-cyan-2553{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-2554{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-2555{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2556{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2557{background:rgba(0,234,255,0.09)}
.bg-blue-2558{background:rgba(45,123,255,0.10)}
.opacity-2559{opacity:0.49}
.radius-2560{border-radius:41px}
.glow-cyan-2561{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-2562{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-2563{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2564{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2565{background:rgba(0,234,255,0.07)}
.bg-blue-2566{background:rgba(45,123,255,0.08)}
.opacity-2567{opacity:0.57}
.radius-2568{border-radius:7px}
.glow-cyan-2569{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-2570{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-2571{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2572{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2573{background:rgba(0,234,255,0.05)}
.bg-blue-2574{background:rgba(45,123,255,0.06)}
.opacity-2575{opacity:0.65}
.radius-2576{border-radius:15px}
.glow-cyan-2577{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-2578{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-2579{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2580{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2581{background:rgba(0,234,255,0.03)}
.bg-blue-2582{background:rgba(45,123,255,0.04)}
.opacity-2583{opacity:0.73}
.radius-2584{border-radius:23px}
.glow-cyan-2585{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-2586{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-2587{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2588{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2589{background:rgba(0,234,255,0.11)}
.bg-blue-2590{background:rgba(45,123,255,0.02)}
.opacity-2591{opacity:0.81}
.radius-2592{border-radius:31px}
.glow-cyan-2593{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-2594{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-2595{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2596{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2597{background:rgba(0,234,255,0.09)}
.bg-blue-2598{background:rgba(45,123,255,0.10)}
.opacity-2599{opacity:0.89}
.radius-2600{border-radius:39px}
.glow-cyan-2601{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-2602{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-2603{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2604{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2605{background:rgba(0,234,255,0.07)}
.bg-blue-2606{background:rgba(45,123,255,0.08)}
.opacity-2607{opacity:0.97}
.radius-2608{border-radius:5px}
.glow-cyan-2609{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-2610{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-2611{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2612{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2613{background:rgba(0,234,255,0.05)}
.bg-blue-2614{background:rgba(45,123,255,0.06)}
.opacity-2615{opacity:0.15}
.radius-2616{border-radius:13px}
.glow-cyan-2617{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-2618{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-2619{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2620{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2621{background:rgba(0,234,255,0.03)}
.bg-blue-2622{background:rgba(45,123,255,0.04)}
.opacity-2623{opacity:0.23}
.radius-2624{border-radius:21px}
.glow-cyan-2625{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-2626{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-2627{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2628{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2629{background:rgba(0,234,255,0.11)}
.bg-blue-2630{background:rgba(45,123,255,0.02)}
.opacity-2631{opacity:0.31}
.radius-2632{border-radius:29px}
.glow-cyan-2633{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-2634{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-2635{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2636{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2637{background:rgba(0,234,255,0.09)}
.bg-blue-2638{background:rgba(45,123,255,0.10)}
.opacity-2639{opacity:0.39}
.radius-2640{border-radius:37px}
.glow-cyan-2641{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-2642{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-2643{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2644{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2645{background:rgba(0,234,255,0.07)}
.bg-blue-2646{background:rgba(45,123,255,0.08)}
.opacity-2647{opacity:0.47}
.radius-2648{border-radius:3px}
.glow-cyan-2649{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-2650{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-2651{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2652{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2653{background:rgba(0,234,255,0.05)}
.bg-blue-2654{background:rgba(45,123,255,0.06)}
.opacity-2655{opacity:0.55}
.radius-2656{border-radius:11px}
.glow-cyan-2657{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-2658{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-2659{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2660{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2661{background:rgba(0,234,255,0.03)}
.bg-blue-2662{background:rgba(45,123,255,0.04)}
.opacity-2663{opacity:0.63}
.radius-2664{border-radius:19px}
.glow-cyan-2665{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-2666{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-2667{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2668{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2669{background:rgba(0,234,255,0.11)}
.bg-blue-2670{background:rgba(45,123,255,0.02)}
.opacity-2671{opacity:0.71}
.radius-2672{border-radius:27px}
.glow-cyan-2673{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-2674{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-2675{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2676{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2677{background:rgba(0,234,255,0.09)}
.bg-blue-2678{background:rgba(45,123,255,0.10)}
.opacity-2679{opacity:0.79}
.radius-2680{border-radius:35px}
.glow-cyan-2681{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-2682{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-2683{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2684{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2685{background:rgba(0,234,255,0.07)}
.bg-blue-2686{background:rgba(45,123,255,0.08)}
.opacity-2687{opacity:0.87}
.radius-2688{border-radius:1px}
.glow-cyan-2689{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-2690{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-2691{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2692{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2693{background:rgba(0,234,255,0.05)}
.bg-blue-2694{background:rgba(45,123,255,0.06)}
.opacity-2695{opacity:0.95}
.radius-2696{border-radius:9px}
.glow-cyan-2697{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-2698{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-2699{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2700{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2701{background:rgba(0,234,255,0.03)}
.bg-blue-2702{background:rgba(45,123,255,0.04)}
.opacity-2703{opacity:0.13}
.radius-2704{border-radius:17px}
.glow-cyan-2705{box-shadow:0 0 5px rgba(0,234,255,0.13)}
.glow-blue-2706{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-2707{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2708{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2709{background:rgba(0,234,255,0.11)}
.bg-blue-2710{background:rgba(45,123,255,0.02)}
.opacity-2711{opacity:0.21}
.radius-2712{border-radius:25px}
.glow-cyan-2713{box-shadow:0 0 13px rgba(0,234,255,0.09)}
.glow-blue-2714{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-2715{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2716{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2717{background:rgba(0,234,255,0.09)}
.bg-blue-2718{background:rgba(45,123,255,0.10)}
.opacity-2719{opacity:0.29}
.radius-2720{border-radius:33px}
.glow-cyan-2721{box-shadow:0 0 21px rgba(0,234,255,0.17)}
.glow-blue-2722{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-2723{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2724{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2725{background:rgba(0,234,255,0.07)}
.bg-blue-2726{background:rgba(45,123,255,0.08)}
.opacity-2727{opacity:0.37}
.radius-2728{border-radius:41px}
.glow-cyan-2729{box-shadow:0 0 29px rgba(0,234,255,0.13)}
.glow-blue-2730{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-2731{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2732{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2733{background:rgba(0,234,255,0.05)}
.bg-blue-2734{background:rgba(45,123,255,0.06)}
.opacity-2735{opacity:0.45}
.radius-2736{border-radius:7px}
.glow-cyan-2737{box-shadow:0 0 11px rgba(0,234,255,0.09)}
.glow-blue-2738{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-2739{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2740{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2741{background:rgba(0,234,255,0.03)}
.bg-blue-2742{background:rgba(45,123,255,0.04)}
.opacity-2743{opacity:0.53}
.radius-2744{border-radius:15px}
.glow-cyan-2745{box-shadow:0 0 19px rgba(0,234,255,0.17)}
.glow-blue-2746{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-2747{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2748{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2749{background:rgba(0,234,255,0.11)}
.bg-blue-2750{background:rgba(45,123,255,0.02)}
.opacity-2751{opacity:0.61}
.radius-2752{border-radius:23px}
.glow-cyan-2753{box-shadow:0 0 27px rgba(0,234,255,0.13)}
.glow-blue-2754{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-2755{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2756{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2757{background:rgba(0,234,255,0.09)}
.bg-blue-2758{background:rgba(45,123,255,0.10)}
.opacity-2759{opacity:0.69}
.radius-2760{border-radius:31px}
.glow-cyan-2761{box-shadow:0 0 9px rgba(0,234,255,0.09)}
.glow-blue-2762{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-2763{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2764{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2765{background:rgba(0,234,255,0.07)}
.bg-blue-2766{background:rgba(45,123,255,0.08)}
.opacity-2767{opacity:0.77}
.radius-2768{border-radius:39px}
.glow-cyan-2769{box-shadow:0 0 17px rgba(0,234,255,0.17)}
.glow-blue-2770{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-2771{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2772{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2773{background:rgba(0,234,255,0.05)}
.bg-blue-2774{background:rgba(45,123,255,0.06)}
.opacity-2775{opacity:0.85}
.radius-2776{border-radius:5px}
.glow-cyan-2777{box-shadow:0 0 25px rgba(0,234,255,0.13)}
.glow-blue-2778{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-2779{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2780{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2781{background:rgba(0,234,255,0.03)}
.bg-blue-2782{background:rgba(45,123,255,0.04)}
.opacity-2783{opacity:0.93}
.radius-2784{border-radius:13px}
.glow-cyan-2785{box-shadow:0 0 7px rgba(0,234,255,0.09)}
.glow-blue-2786{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-2787{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2788{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2789{background:rgba(0,234,255,0.11)}
.bg-blue-2790{background:rgba(45,123,255,0.02)}
.opacity-2791{opacity:0.11}
.radius-2792{border-radius:21px}
.glow-cyan-2793{box-shadow:0 0 15px rgba(0,234,255,0.17)}
.glow-blue-2794{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-2795{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2796{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2797{background:rgba(0,234,255,0.09)}
.bg-blue-2798{background:rgba(45,123,255,0.10)}
.opacity-2799{opacity:0.19}
.radius-2800{border-radius:29px}
.glow-cyan-2801{box-shadow:0 0 23px rgba(0,234,255,0.13)}
.glow-blue-2802{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-2803{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2804{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2805{background:rgba(0,234,255,0.07)}
.bg-blue-2806{background:rgba(45,123,255,0.08)}
.opacity-2807{opacity:0.27}
.radius-2808{border-radius:37px}
.glow-cyan-2809{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-2810{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-2811{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2812{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2813{background:rgba(0,234,255,0.05)}
.bg-blue-2814{background:rgba(45,123,255,0.06)}
.opacity-2815{opacity:0.35}
.radius-2816{border-radius:3px}
.glow-cyan-2817{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-2818{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-2819{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2820{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2821{background:rgba(0,234,255,0.03)}
.bg-blue-2822{background:rgba(45,123,255,0.04)}
.opacity-2823{opacity:0.43}
.radius-2824{border-radius:11px}
.glow-cyan-2825{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-2826{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-2827{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2828{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2829{background:rgba(0,234,255,0.11)}
.bg-blue-2830{background:rgba(45,123,255,0.02)}
.opacity-2831{opacity:0.51}
.radius-2832{border-radius:19px}
.glow-cyan-2833{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-2834{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-2835{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2836{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2837{background:rgba(0,234,255,0.09)}
.bg-blue-2838{background:rgba(45,123,255,0.10)}
.opacity-2839{opacity:0.59}
.radius-2840{border-radius:27px}
.glow-cyan-2841{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-2842{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-2843{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2844{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2845{background:rgba(0,234,255,0.07)}
.bg-blue-2846{background:rgba(45,123,255,0.08)}
.opacity-2847{opacity:0.67}
.radius-2848{border-radius:35px}
.glow-cyan-2849{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-2850{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-2851{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2852{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2853{background:rgba(0,234,255,0.05)}
.bg-blue-2854{background:rgba(45,123,255,0.06)}
.opacity-2855{opacity:0.75}
.radius-2856{border-radius:1px}
.glow-cyan-2857{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-2858{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-2859{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2860{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2861{background:rgba(0,234,255,0.03)}
.bg-blue-2862{background:rgba(45,123,255,0.04)}
.opacity-2863{opacity:0.83}
.radius-2864{border-radius:9px}
.glow-cyan-2865{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-2866{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-2867{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2868{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2869{background:rgba(0,234,255,0.11)}
.bg-blue-2870{background:rgba(45,123,255,0.02)}
.opacity-2871{opacity:0.91}
.radius-2872{border-radius:17px}
.glow-cyan-2873{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-2874{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-2875{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2876{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2877{background:rgba(0,234,255,0.09)}
.bg-blue-2878{background:rgba(45,123,255,0.10)}
.opacity-2879{opacity:0.99}
.radius-2880{border-radius:25px}
.glow-cyan-2881{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-2882{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-2883{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2884{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2885{background:rgba(0,234,255,0.07)}
.bg-blue-2886{background:rgba(45,123,255,0.08)}
.opacity-2887{opacity:0.17}
.radius-2888{border-radius:33px}
.glow-cyan-2889{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-2890{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-2891{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2892{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2893{background:rgba(0,234,255,0.05)}
.bg-blue-2894{background:rgba(45,123,255,0.06)}
.opacity-2895{opacity:0.25}
.radius-2896{border-radius:41px}
.glow-cyan-2897{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-2898{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-2899{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2900{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2901{background:rgba(0,234,255,0.03)}
.bg-blue-2902{background:rgba(45,123,255,0.04)}
.opacity-2903{opacity:0.33}
.radius-2904{border-radius:7px}
.glow-cyan-2905{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-2906{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-2907{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2908{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2909{background:rgba(0,234,255,0.11)}
.bg-blue-2910{background:rgba(45,123,255,0.02)}
.opacity-2911{opacity:0.41}
.radius-2912{border-radius:15px}
.glow-cyan-2913{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-2914{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-2915{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2916{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2917{background:rgba(0,234,255,0.09)}
.bg-blue-2918{background:rgba(45,123,255,0.10)}
.opacity-2919{opacity:0.49}
.radius-2920{border-radius:23px}
.glow-cyan-2921{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-2922{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-2923{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2924{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2925{background:rgba(0,234,255,0.07)}
.bg-blue-2926{background:rgba(45,123,255,0.08)}
.opacity-2927{opacity:0.57}
.radius-2928{border-radius:31px}
.glow-cyan-2929{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-2930{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-2931{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2932{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2933{background:rgba(0,234,255,0.05)}
.bg-blue-2934{background:rgba(45,123,255,0.06)}
.opacity-2935{opacity:0.65}
.radius-2936{border-radius:39px}
.glow-cyan-2937{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-2938{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-2939{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2940{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2941{background:rgba(0,234,255,0.03)}
.bg-blue-2942{background:rgba(45,123,255,0.04)}
.opacity-2943{opacity:0.73}
.radius-2944{border-radius:5px}
.glow-cyan-2945{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-2946{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-2947{border:1px solid rgba(0,234,255,0.15)}
.border-blue-2948{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-2949{background:rgba(0,234,255,0.11)}
.bg-blue-2950{background:rgba(45,123,255,0.02)}
.opacity-2951{opacity:0.81}
.radius-2952{border-radius:13px}
.glow-cyan-2953{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-2954{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-2955{border:1px solid rgba(0,234,255,0.09)}
.border-blue-2956{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-2957{background:rgba(0,234,255,0.09)}
.bg-blue-2958{background:rgba(45,123,255,0.10)}
.opacity-2959{opacity:0.89}
.radius-2960{border-radius:21px}
.glow-cyan-2961{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-2962{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-2963{border:1px solid rgba(0,234,255,0.17)}
.border-blue-2964{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-2965{background:rgba(0,234,255,0.07)}
.bg-blue-2966{background:rgba(45,123,255,0.08)}
.opacity-2967{opacity:0.97}
.radius-2968{border-radius:29px}
.glow-cyan-2969{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-2970{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-2971{border:1px solid rgba(0,234,255,0.11)}
.border-blue-2972{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-2973{background:rgba(0,234,255,0.05)}
.bg-blue-2974{background:rgba(45,123,255,0.06)}
.opacity-2975{opacity:0.15}
.radius-2976{border-radius:37px}
.glow-cyan-2977{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-2978{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-2979{border:1px solid rgba(0,234,255,0.19)}
.border-blue-2980{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-2981{background:rgba(0,234,255,0.03)}
.bg-blue-2982{background:rgba(45,123,255,0.04)}
.opacity-2983{opacity:0.23}
.radius-2984{border-radius:3px}
.glow-cyan-2985{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-2986{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-2987{border:1px solid rgba(0,234,255,0.13)}
.border-blue-2988{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-2989{background:rgba(0,234,255,0.11)}
.bg-blue-2990{background:rgba(45,123,255,0.02)}
.opacity-2991{opacity:0.31}
.radius-2992{border-radius:11px}
.glow-cyan-2993{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-2994{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-2995{border:1px solid rgba(0,234,255,0.21)}
.border-blue-2996{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-2997{background:rgba(0,234,255,0.09)}
.bg-blue-2998{background:rgba(45,123,255,0.10)}
.opacity-2999{opacity:0.39}
.radius-3000{border-radius:19px}
.glow-cyan-3001{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-3002{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-3003{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3004{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3005{background:rgba(0,234,255,0.07)}
.bg-blue-3006{background:rgba(45,123,255,0.08)}
.opacity-3007{opacity:0.47}
.radius-3008{border-radius:27px}
.glow-cyan-3009{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-3010{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-3011{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3012{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3013{background:rgba(0,234,255,0.05)}
.bg-blue-3014{background:rgba(45,123,255,0.06)}
.opacity-3015{opacity:0.55}
.radius-3016{border-radius:35px}
.glow-cyan-3017{box-shadow:0 0 5px rgba(0,234,255,0.13)}
.glow-blue-3018{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-3019{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3020{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3021{background:rgba(0,234,255,0.03)}
.bg-blue-3022{background:rgba(45,123,255,0.04)}
.opacity-3023{opacity:0.63}
.radius-3024{border-radius:1px}
.glow-cyan-3025{box-shadow:0 0 13px rgba(0,234,255,0.09)}
.glow-blue-3026{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-3027{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3028{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3029{background:rgba(0,234,255,0.11)}
.bg-blue-3030{background:rgba(45,123,255,0.02)}
.opacity-3031{opacity:0.71}
.radius-3032{border-radius:9px}
.glow-cyan-3033{box-shadow:0 0 21px rgba(0,234,255,0.17)}
.glow-blue-3034{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-3035{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3036{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3037{background:rgba(0,234,255,0.09)}
.bg-blue-3038{background:rgba(45,123,255,0.10)}
.opacity-3039{opacity:0.79}
.radius-3040{border-radius:17px}
.glow-cyan-3041{box-shadow:0 0 29px rgba(0,234,255,0.13)}
.glow-blue-3042{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-3043{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3044{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3045{background:rgba(0,234,255,0.07)}
.bg-blue-3046{background:rgba(45,123,255,0.08)}
.opacity-3047{opacity:0.87}
.radius-3048{border-radius:25px}
.glow-cyan-3049{box-shadow:0 0 11px rgba(0,234,255,0.09)}
.glow-blue-3050{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-3051{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3052{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3053{background:rgba(0,234,255,0.05)}
.bg-blue-3054{background:rgba(45,123,255,0.06)}
.opacity-3055{opacity:0.95}
.radius-3056{border-radius:33px}
.glow-cyan-3057{box-shadow:0 0 19px rgba(0,234,255,0.17)}
.glow-blue-3058{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-3059{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3060{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3061{background:rgba(0,234,255,0.03)}
.bg-blue-3062{background:rgba(45,123,255,0.04)}
.opacity-3063{opacity:0.13}
.radius-3064{border-radius:41px}
.glow-cyan-3065{box-shadow:0 0 27px rgba(0,234,255,0.13)}
.glow-blue-3066{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-3067{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3068{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3069{background:rgba(0,234,255,0.11)}
.bg-blue-3070{background:rgba(45,123,255,0.02)}
.opacity-3071{opacity:0.21}
.radius-3072{border-radius:7px}
.glow-cyan-3073{box-shadow:0 0 9px rgba(0,234,255,0.09)}
.glow-blue-3074{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-3075{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3076{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3077{background:rgba(0,234,255,0.09)}
.bg-blue-3078{background:rgba(45,123,255,0.10)}
.opacity-3079{opacity:0.29}
.radius-3080{border-radius:15px}
.glow-cyan-3081{box-shadow:0 0 17px rgba(0,234,255,0.17)}
.glow-blue-3082{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-3083{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3084{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3085{background:rgba(0,234,255,0.07)}
.bg-blue-3086{background:rgba(45,123,255,0.08)}
.opacity-3087{opacity:0.37}
.radius-3088{border-radius:23px}
.glow-cyan-3089{box-shadow:0 0 25px rgba(0,234,255,0.13)}
.glow-blue-3090{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-3091{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3092{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3093{background:rgba(0,234,255,0.05)}
.bg-blue-3094{background:rgba(45,123,255,0.06)}
.opacity-3095{opacity:0.45}
.radius-3096{border-radius:31px}
.glow-cyan-3097{box-shadow:0 0 7px rgba(0,234,255,0.09)}
.glow-blue-3098{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-3099{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3100{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3101{background:rgba(0,234,255,0.03)}
.bg-blue-3102{background:rgba(45,123,255,0.04)}
.opacity-3103{opacity:0.53}
.radius-3104{border-radius:39px}
.glow-cyan-3105{box-shadow:0 0 15px rgba(0,234,255,0.17)}
.glow-blue-3106{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-3107{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3108{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3109{background:rgba(0,234,255,0.11)}
.bg-blue-3110{background:rgba(45,123,255,0.02)}
.opacity-3111{opacity:0.61}
.radius-3112{border-radius:5px}
.glow-cyan-3113{box-shadow:0 0 23px rgba(0,234,255,0.13)}
.glow-blue-3114{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-3115{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3116{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3117{background:rgba(0,234,255,0.09)}
.bg-blue-3118{background:rgba(45,123,255,0.10)}
.opacity-3119{opacity:0.69}
.radius-3120{border-radius:13px}
.glow-cyan-3121{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-3122{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-3123{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3124{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3125{background:rgba(0,234,255,0.07)}
.bg-blue-3126{background:rgba(45,123,255,0.08)}
.opacity-3127{opacity:0.77}
.radius-3128{border-radius:21px}
.glow-cyan-3129{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-3130{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-3131{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3132{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3133{background:rgba(0,234,255,0.05)}
.bg-blue-3134{background:rgba(45,123,255,0.06)}
.opacity-3135{opacity:0.85}
.radius-3136{border-radius:29px}
.glow-cyan-3137{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-3138{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-3139{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3140{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3141{background:rgba(0,234,255,0.03)}
.bg-blue-3142{background:rgba(45,123,255,0.04)}
.opacity-3143{opacity:0.93}
.radius-3144{border-radius:37px}
.glow-cyan-3145{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-3146{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-3147{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3148{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3149{background:rgba(0,234,255,0.11)}
.bg-blue-3150{background:rgba(45,123,255,0.02)}
.opacity-3151{opacity:0.11}
.radius-3152{border-radius:3px}
.glow-cyan-3153{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-3154{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-3155{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3156{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3157{background:rgba(0,234,255,0.09)}
.bg-blue-3158{background:rgba(45,123,255,0.10)}
.opacity-3159{opacity:0.19}
.radius-3160{border-radius:11px}
.glow-cyan-3161{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-3162{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-3163{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3164{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3165{background:rgba(0,234,255,0.07)}
.bg-blue-3166{background:rgba(45,123,255,0.08)}
.opacity-3167{opacity:0.27}
.radius-3168{border-radius:19px}
.glow-cyan-3169{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-3170{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-3171{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3172{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3173{background:rgba(0,234,255,0.05)}
.bg-blue-3174{background:rgba(45,123,255,0.06)}
.opacity-3175{opacity:0.35}
.radius-3176{border-radius:27px}
.glow-cyan-3177{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-3178{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-3179{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3180{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3181{background:rgba(0,234,255,0.03)}
.bg-blue-3182{background:rgba(45,123,255,0.04)}
.opacity-3183{opacity:0.43}
.radius-3184{border-radius:35px}
.glow-cyan-3185{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-3186{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-3187{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3188{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3189{background:rgba(0,234,255,0.11)}
.bg-blue-3190{background:rgba(45,123,255,0.02)}
.opacity-3191{opacity:0.51}
.radius-3192{border-radius:1px}
.glow-cyan-3193{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-3194{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-3195{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3196{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3197{background:rgba(0,234,255,0.09)}
.bg-blue-3198{background:rgba(45,123,255,0.10)}
.opacity-3199{opacity:0.59}
.radius-3200{border-radius:9px}
.glow-cyan-3201{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-3202{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-3203{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3204{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3205{background:rgba(0,234,255,0.07)}
.bg-blue-3206{background:rgba(45,123,255,0.08)}
.opacity-3207{opacity:0.67}
.radius-3208{border-radius:17px}
.glow-cyan-3209{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-3210{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-3211{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3212{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3213{background:rgba(0,234,255,0.05)}
.bg-blue-3214{background:rgba(45,123,255,0.06)}
.opacity-3215{opacity:0.75}
.radius-3216{border-radius:25px}
.glow-cyan-3217{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-3218{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-3219{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3220{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3221{background:rgba(0,234,255,0.03)}
.bg-blue-3222{background:rgba(45,123,255,0.04)}
.opacity-3223{opacity:0.83}
.radius-3224{border-radius:33px}
.glow-cyan-3225{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-3226{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-3227{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3228{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3229{background:rgba(0,234,255,0.11)}
.bg-blue-3230{background:rgba(45,123,255,0.02)}
.opacity-3231{opacity:0.91}
.radius-3232{border-radius:41px}
.glow-cyan-3233{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-3234{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-3235{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3236{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3237{background:rgba(0,234,255,0.09)}
.bg-blue-3238{background:rgba(45,123,255,0.10)}
.opacity-3239{opacity:0.99}
.radius-3240{border-radius:7px}
.glow-cyan-3241{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-3242{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-3243{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3244{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3245{background:rgba(0,234,255,0.07)}
.bg-blue-3246{background:rgba(45,123,255,0.08)}
.opacity-3247{opacity:0.17}
.radius-3248{border-radius:15px}
.glow-cyan-3249{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-3250{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-3251{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3252{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3253{background:rgba(0,234,255,0.05)}
.bg-blue-3254{background:rgba(45,123,255,0.06)}
.opacity-3255{opacity:0.25}
.radius-3256{border-radius:23px}
.glow-cyan-3257{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-3258{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-3259{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3260{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3261{background:rgba(0,234,255,0.03)}
.bg-blue-3262{background:rgba(45,123,255,0.04)}
.opacity-3263{opacity:0.33}
.radius-3264{border-radius:31px}
.glow-cyan-3265{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-3266{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-3267{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3268{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3269{background:rgba(0,234,255,0.11)}
.bg-blue-3270{background:rgba(45,123,255,0.02)}
.opacity-3271{opacity:0.41}
.radius-3272{border-radius:39px}
.glow-cyan-3273{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-3274{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-3275{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3276{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3277{background:rgba(0,234,255,0.09)}
.bg-blue-3278{background:rgba(45,123,255,0.10)}
.opacity-3279{opacity:0.49}
.radius-3280{border-radius:5px}
.glow-cyan-3281{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-3282{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-3283{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3284{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3285{background:rgba(0,234,255,0.07)}
.bg-blue-3286{background:rgba(45,123,255,0.08)}
.opacity-3287{opacity:0.57}
.radius-3288{border-radius:13px}
.glow-cyan-3289{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-3290{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-3291{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3292{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3293{background:rgba(0,234,255,0.05)}
.bg-blue-3294{background:rgba(45,123,255,0.06)}
.opacity-3295{opacity:0.65}
.radius-3296{border-radius:21px}
.glow-cyan-3297{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-3298{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-3299{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3300{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3301{background:rgba(0,234,255,0.03)}
.bg-blue-3302{background:rgba(45,123,255,0.04)}
.opacity-3303{opacity:0.73}
.radius-3304{border-radius:29px}
.glow-cyan-3305{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-3306{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-3307{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3308{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3309{background:rgba(0,234,255,0.11)}
.bg-blue-3310{background:rgba(45,123,255,0.02)}
.opacity-3311{opacity:0.81}
.radius-3312{border-radius:37px}
.glow-cyan-3313{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-3314{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-3315{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3316{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3317{background:rgba(0,234,255,0.09)}
.bg-blue-3318{background:rgba(45,123,255,0.10)}
.opacity-3319{opacity:0.89}
.radius-3320{border-radius:3px}
.glow-cyan-3321{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-3322{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-3323{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3324{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3325{background:rgba(0,234,255,0.07)}
.bg-blue-3326{background:rgba(45,123,255,0.08)}
.opacity-3327{opacity:0.97}
.radius-3328{border-radius:11px}
.glow-cyan-3329{box-shadow:0 0 5px rgba(0,234,255,0.13)}
.glow-blue-3330{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-3331{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3332{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3333{background:rgba(0,234,255,0.05)}
.bg-blue-3334{background:rgba(45,123,255,0.06)}
.opacity-3335{opacity:0.15}
.radius-3336{border-radius:19px}
.glow-cyan-3337{box-shadow:0 0 13px rgba(0,234,255,0.09)}
.glow-blue-3338{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-3339{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3340{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3341{background:rgba(0,234,255,0.03)}
.bg-blue-3342{background:rgba(45,123,255,0.04)}
.opacity-3343{opacity:0.23}
.radius-3344{border-radius:27px}
.glow-cyan-3345{box-shadow:0 0 21px rgba(0,234,255,0.17)}
.glow-blue-3346{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-3347{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3348{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3349{background:rgba(0,234,255,0.11)}
.bg-blue-3350{background:rgba(45,123,255,0.02)}
.opacity-3351{opacity:0.31}
.radius-3352{border-radius:35px}
.glow-cyan-3353{box-shadow:0 0 29px rgba(0,234,255,0.13)}
.glow-blue-3354{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-3355{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3356{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3357{background:rgba(0,234,255,0.09)}
.bg-blue-3358{background:rgba(45,123,255,0.10)}
.opacity-3359{opacity:0.39}
.radius-3360{border-radius:1px}
.glow-cyan-3361{box-shadow:0 0 11px rgba(0,234,255,0.09)}
.glow-blue-3362{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-3363{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3364{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3365{background:rgba(0,234,255,0.07)}
.bg-blue-3366{background:rgba(45,123,255,0.08)}
.opacity-3367{opacity:0.47}
.radius-3368{border-radius:9px}
.glow-cyan-3369{box-shadow:0 0 19px rgba(0,234,255,0.17)}
.glow-blue-3370{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-3371{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3372{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3373{background:rgba(0,234,255,0.05)}
.bg-blue-3374{background:rgba(45,123,255,0.06)}
.opacity-3375{opacity:0.55}
.radius-3376{border-radius:17px}
.glow-cyan-3377{box-shadow:0 0 27px rgba(0,234,255,0.13)}
.glow-blue-3378{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-3379{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3380{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3381{background:rgba(0,234,255,0.03)}
.bg-blue-3382{background:rgba(45,123,255,0.04)}
.opacity-3383{opacity:0.63}
.radius-3384{border-radius:25px}
.glow-cyan-3385{box-shadow:0 0 9px rgba(0,234,255,0.09)}
.glow-blue-3386{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-3387{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3388{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3389{background:rgba(0,234,255,0.11)}
.bg-blue-3390{background:rgba(45,123,255,0.02)}
.opacity-3391{opacity:0.71}
.radius-3392{border-radius:33px}
.glow-cyan-3393{box-shadow:0 0 17px rgba(0,234,255,0.17)}
.glow-blue-3394{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-3395{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3396{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3397{background:rgba(0,234,255,0.09)}
.bg-blue-3398{background:rgba(45,123,255,0.10)}
.opacity-3399{opacity:0.79}
.radius-3400{border-radius:41px}
.glow-cyan-3401{box-shadow:0 0 25px rgba(0,234,255,0.13)}
.glow-blue-3402{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-3403{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3404{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3405{background:rgba(0,234,255,0.07)}
.bg-blue-3406{background:rgba(45,123,255,0.08)}
.opacity-3407{opacity:0.87}
.radius-3408{border-radius:7px}
.glow-cyan-3409{box-shadow:0 0 7px rgba(0,234,255,0.09)}
.glow-blue-3410{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-3411{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3412{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3413{background:rgba(0,234,255,0.05)}
.bg-blue-3414{background:rgba(45,123,255,0.06)}
.opacity-3415{opacity:0.95}
.radius-3416{border-radius:15px}
.glow-cyan-3417{box-shadow:0 0 15px rgba(0,234,255,0.17)}
.glow-blue-3418{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-3419{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3420{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3421{background:rgba(0,234,255,0.03)}
.bg-blue-3422{background:rgba(45,123,255,0.04)}
.opacity-3423{opacity:0.13}
.radius-3424{border-radius:23px}
.glow-cyan-3425{box-shadow:0 0 23px rgba(0,234,255,0.13)}
.glow-blue-3426{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-3427{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3428{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3429{background:rgba(0,234,255,0.11)}
.bg-blue-3430{background:rgba(45,123,255,0.02)}
.opacity-3431{opacity:0.21}
.radius-3432{border-radius:31px}
.glow-cyan-3433{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-3434{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-3435{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3436{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3437{background:rgba(0,234,255,0.09)}
.bg-blue-3438{background:rgba(45,123,255,0.10)}
.opacity-3439{opacity:0.29}
.radius-3440{border-radius:39px}
.glow-cyan-3441{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-3442{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-3443{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3444{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3445{background:rgba(0,234,255,0.07)}
.bg-blue-3446{background:rgba(45,123,255,0.08)}
.opacity-3447{opacity:0.37}
.radius-3448{border-radius:5px}
.glow-cyan-3449{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-3450{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-3451{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3452{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3453{background:rgba(0,234,255,0.05)}
.bg-blue-3454{background:rgba(45,123,255,0.06)}
.opacity-3455{opacity:0.45}
.radius-3456{border-radius:13px}
.glow-cyan-3457{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-3458{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-3459{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3460{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3461{background:rgba(0,234,255,0.03)}
.bg-blue-3462{background:rgba(45,123,255,0.04)}
.opacity-3463{opacity:0.53}
.radius-3464{border-radius:21px}
.glow-cyan-3465{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-3466{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-3467{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3468{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3469{background:rgba(0,234,255,0.11)}
.bg-blue-3470{background:rgba(45,123,255,0.02)}
.opacity-3471{opacity:0.61}
.radius-3472{border-radius:29px}
.glow-cyan-3473{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-3474{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-3475{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3476{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3477{background:rgba(0,234,255,0.09)}
.bg-blue-3478{background:rgba(45,123,255,0.10)}
.opacity-3479{opacity:0.69}
.radius-3480{border-radius:37px}
.glow-cyan-3481{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-3482{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-3483{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3484{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3485{background:rgba(0,234,255,0.07)}
.bg-blue-3486{background:rgba(45,123,255,0.08)}
.opacity-3487{opacity:0.77}
.radius-3488{border-radius:3px}
.glow-cyan-3489{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-3490{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-3491{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3492{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3493{background:rgba(0,234,255,0.05)}
.bg-blue-3494{background:rgba(45,123,255,0.06)}
.opacity-3495{opacity:0.85}
.radius-3496{border-radius:11px}
.glow-cyan-3497{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-3498{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-3499{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3500{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3501{background:rgba(0,234,255,0.03)}
.bg-blue-3502{background:rgba(45,123,255,0.04)}
.opacity-3503{opacity:0.93}
.radius-3504{border-radius:19px}
.glow-cyan-3505{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-3506{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-3507{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3508{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3509{background:rgba(0,234,255,0.11)}
.bg-blue-3510{background:rgba(45,123,255,0.02)}
.opacity-3511{opacity:0.11}
.radius-3512{border-radius:27px}
.glow-cyan-3513{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-3514{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-3515{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3516{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3517{background:rgba(0,234,255,0.09)}
.bg-blue-3518{background:rgba(45,123,255,0.10)}
.opacity-3519{opacity:0.19}
.radius-3520{border-radius:35px}
.glow-cyan-3521{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-3522{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-3523{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3524{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3525{background:rgba(0,234,255,0.07)}
.bg-blue-3526{background:rgba(45,123,255,0.08)}
.opacity-3527{opacity:0.27}
.radius-3528{border-radius:1px}
.glow-cyan-3529{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-3530{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-3531{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3532{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3533{background:rgba(0,234,255,0.05)}
.bg-blue-3534{background:rgba(45,123,255,0.06)}
.opacity-3535{opacity:0.35}
.radius-3536{border-radius:9px}
.glow-cyan-3537{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-3538{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-3539{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3540{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3541{background:rgba(0,234,255,0.03)}
.bg-blue-3542{background:rgba(45,123,255,0.04)}
.opacity-3543{opacity:0.43}
.radius-3544{border-radius:17px}
.glow-cyan-3545{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-3546{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-3547{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3548{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3549{background:rgba(0,234,255,0.11)}
.bg-blue-3550{background:rgba(45,123,255,0.02)}
.opacity-3551{opacity:0.51}
.radius-3552{border-radius:25px}
.glow-cyan-3553{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-3554{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-3555{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3556{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3557{background:rgba(0,234,255,0.09)}
.bg-blue-3558{background:rgba(45,123,255,0.10)}
.opacity-3559{opacity:0.59}
.radius-3560{border-radius:33px}
.glow-cyan-3561{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-3562{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-3563{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3564{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3565{background:rgba(0,234,255,0.07)}
.bg-blue-3566{background:rgba(45,123,255,0.08)}
.opacity-3567{opacity:0.67}
.radius-3568{border-radius:41px}
.glow-cyan-3569{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-3570{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-3571{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3572{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3573{background:rgba(0,234,255,0.05)}
.bg-blue-3574{background:rgba(45,123,255,0.06)}
.opacity-3575{opacity:0.75}
.radius-3576{border-radius:7px}
.glow-cyan-3577{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-3578{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-3579{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3580{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3581{background:rgba(0,234,255,0.03)}
.bg-blue-3582{background:rgba(45,123,255,0.04)}
.opacity-3583{opacity:0.83}
.radius-3584{border-radius:15px}
.glow-cyan-3585{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-3586{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-3587{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3588{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3589{background:rgba(0,234,255,0.11)}
.bg-blue-3590{background:rgba(45,123,255,0.02)}
.opacity-3591{opacity:0.91}
.radius-3592{border-radius:23px}
.glow-cyan-3593{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-3594{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-3595{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3596{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3597{background:rgba(0,234,255,0.09)}
.bg-blue-3598{background:rgba(45,123,255,0.10)}
.opacity-3599{opacity:0.99}
.radius-3600{border-radius:31px}
.glow-cyan-3601{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-3602{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-3603{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3604{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3605{background:rgba(0,234,255,0.07)}
.bg-blue-3606{background:rgba(45,123,255,0.08)}
.opacity-3607{opacity:0.17}
.radius-3608{border-radius:39px}
.glow-cyan-3609{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-3610{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-3611{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3612{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3613{background:rgba(0,234,255,0.05)}
.bg-blue-3614{background:rgba(45,123,255,0.06)}
.opacity-3615{opacity:0.25}
.radius-3616{border-radius:5px}
.glow-cyan-3617{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-3618{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-3619{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3620{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3621{background:rgba(0,234,255,0.03)}
.bg-blue-3622{background:rgba(45,123,255,0.04)}
.opacity-3623{opacity:0.33}
.radius-3624{border-radius:13px}
.glow-cyan-3625{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-3626{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-3627{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3628{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3629{background:rgba(0,234,255,0.11)}
.bg-blue-3630{background:rgba(45,123,255,0.02)}
.opacity-3631{opacity:0.41}
.radius-3632{border-radius:21px}
.glow-cyan-3633{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-3634{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-3635{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3636{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3637{background:rgba(0,234,255,0.09)}
.bg-blue-3638{background:rgba(45,123,255,0.10)}
.opacity-3639{opacity:0.49}
.radius-3640{border-radius:29px}
.glow-cyan-3641{box-shadow:0 0 5px rgba(0,234,255,0.13)}
.glow-blue-3642{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-3643{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3644{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3645{background:rgba(0,234,255,0.07)}
.bg-blue-3646{background:rgba(45,123,255,0.08)}
.opacity-3647{opacity:0.57}
.radius-3648{border-radius:37px}
.glow-cyan-3649{box-shadow:0 0 13px rgba(0,234,255,0.09)}
.glow-blue-3650{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-3651{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3652{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3653{background:rgba(0,234,255,0.05)}
.bg-blue-3654{background:rgba(45,123,255,0.06)}
.opacity-3655{opacity:0.65}
.radius-3656{border-radius:3px}
.glow-cyan-3657{box-shadow:0 0 21px rgba(0,234,255,0.17)}
.glow-blue-3658{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-3659{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3660{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3661{background:rgba(0,234,255,0.03)}
.bg-blue-3662{background:rgba(45,123,255,0.04)}
.opacity-3663{opacity:0.73}
.radius-3664{border-radius:11px}
.glow-cyan-3665{box-shadow:0 0 29px rgba(0,234,255,0.13)}
.glow-blue-3666{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-3667{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3668{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3669{background:rgba(0,234,255,0.11)}
.bg-blue-3670{background:rgba(45,123,255,0.02)}
.opacity-3671{opacity:0.81}
.radius-3672{border-radius:19px}
.glow-cyan-3673{box-shadow:0 0 11px rgba(0,234,255,0.09)}
.glow-blue-3674{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-3675{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3676{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3677{background:rgba(0,234,255,0.09)}
.bg-blue-3678{background:rgba(45,123,255,0.10)}
.opacity-3679{opacity:0.89}
.radius-3680{border-radius:27px}
.glow-cyan-3681{box-shadow:0 0 19px rgba(0,234,255,0.17)}
.glow-blue-3682{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-3683{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3684{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3685{background:rgba(0,234,255,0.07)}
.bg-blue-3686{background:rgba(45,123,255,0.08)}
.opacity-3687{opacity:0.97}
.radius-3688{border-radius:35px}
.glow-cyan-3689{box-shadow:0 0 27px rgba(0,234,255,0.13)}
.glow-blue-3690{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-3691{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3692{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3693{background:rgba(0,234,255,0.05)}
.bg-blue-3694{background:rgba(45,123,255,0.06)}
.opacity-3695{opacity:0.15}
.radius-3696{border-radius:1px}
.glow-cyan-3697{box-shadow:0 0 9px rgba(0,234,255,0.09)}
.glow-blue-3698{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-3699{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3700{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3701{background:rgba(0,234,255,0.03)}
.bg-blue-3702{background:rgba(45,123,255,0.04)}
.opacity-3703{opacity:0.23}
.radius-3704{border-radius:9px}
.glow-cyan-3705{box-shadow:0 0 17px rgba(0,234,255,0.17)}
.glow-blue-3706{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-3707{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3708{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3709{background:rgba(0,234,255,0.11)}
.bg-blue-3710{background:rgba(45,123,255,0.02)}
.opacity-3711{opacity:0.31}
.radius-3712{border-radius:17px}
.glow-cyan-3713{box-shadow:0 0 25px rgba(0,234,255,0.13)}
.glow-blue-3714{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-3715{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3716{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3717{background:rgba(0,234,255,0.09)}
.bg-blue-3718{background:rgba(45,123,255,0.10)}
.opacity-3719{opacity:0.39}
.radius-3720{border-radius:25px}
.glow-cyan-3721{box-shadow:0 0 7px rgba(0,234,255,0.09)}
.glow-blue-3722{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-3723{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3724{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3725{background:rgba(0,234,255,0.07)}
.bg-blue-3726{background:rgba(45,123,255,0.08)}
.opacity-3727{opacity:0.47}
.radius-3728{border-radius:33px}
.glow-cyan-3729{box-shadow:0 0 15px rgba(0,234,255,0.17)}
.glow-blue-3730{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-3731{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3732{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3733{background:rgba(0,234,255,0.05)}
.bg-blue-3734{background:rgba(45,123,255,0.06)}
.opacity-3735{opacity:0.55}
.radius-3736{border-radius:41px}
.glow-cyan-3737{box-shadow:0 0 23px rgba(0,234,255,0.13)}
.glow-blue-3738{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-3739{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3740{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3741{background:rgba(0,234,255,0.03)}
.bg-blue-3742{background:rgba(45,123,255,0.04)}
.opacity-3743{opacity:0.63}
.radius-3744{border-radius:7px}
.glow-cyan-3745{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-3746{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-3747{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3748{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3749{background:rgba(0,234,255,0.11)}
.bg-blue-3750{background:rgba(45,123,255,0.02)}
.opacity-3751{opacity:0.71}
.radius-3752{border-radius:15px}
.glow-cyan-3753{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-3754{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-3755{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3756{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3757{background:rgba(0,234,255,0.09)}
.bg-blue-3758{background:rgba(45,123,255,0.10)}
.opacity-3759{opacity:0.79}
.radius-3760{border-radius:23px}
.glow-cyan-3761{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-3762{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-3763{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3764{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3765{background:rgba(0,234,255,0.07)}
.bg-blue-3766{background:rgba(45,123,255,0.08)}
.opacity-3767{opacity:0.87}
.radius-3768{border-radius:31px}
.glow-cyan-3769{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-3770{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-3771{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3772{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3773{background:rgba(0,234,255,0.05)}
.bg-blue-3774{background:rgba(45,123,255,0.06)}
.opacity-3775{opacity:0.95}
.radius-3776{border-radius:39px}
.glow-cyan-3777{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-3778{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-3779{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3780{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3781{background:rgba(0,234,255,0.03)}
.bg-blue-3782{background:rgba(45,123,255,0.04)}
.opacity-3783{opacity:0.13}
.radius-3784{border-radius:5px}
.glow-cyan-3785{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-3786{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-3787{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3788{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3789{background:rgba(0,234,255,0.11)}
.bg-blue-3790{background:rgba(45,123,255,0.02)}
.opacity-3791{opacity:0.21}
.radius-3792{border-radius:13px}
.glow-cyan-3793{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-3794{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-3795{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3796{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3797{background:rgba(0,234,255,0.09)}
.bg-blue-3798{background:rgba(45,123,255,0.10)}
.opacity-3799{opacity:0.29}
.radius-3800{border-radius:21px}
.glow-cyan-3801{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-3802{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-3803{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3804{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3805{background:rgba(0,234,255,0.07)}
.bg-blue-3806{background:rgba(45,123,255,0.08)}
.opacity-3807{opacity:0.37}
.radius-3808{border-radius:29px}
.glow-cyan-3809{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-3810{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-3811{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3812{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3813{background:rgba(0,234,255,0.05)}
.bg-blue-3814{background:rgba(45,123,255,0.06)}
.opacity-3815{opacity:0.45}
.radius-3816{border-radius:37px}
.glow-cyan-3817{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-3818{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-3819{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3820{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3821{background:rgba(0,234,255,0.03)}
.bg-blue-3822{background:rgba(45,123,255,0.04)}
.opacity-3823{opacity:0.53}
.radius-3824{border-radius:3px}
.glow-cyan-3825{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-3826{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-3827{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3828{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3829{background:rgba(0,234,255,0.11)}
.bg-blue-3830{background:rgba(45,123,255,0.02)}
.opacity-3831{opacity:0.61}
.radius-3832{border-radius:11px}
.glow-cyan-3833{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-3834{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-3835{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3836{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3837{background:rgba(0,234,255,0.09)}
.bg-blue-3838{background:rgba(45,123,255,0.10)}
.opacity-3839{opacity:0.69}
.radius-3840{border-radius:19px}
.glow-cyan-3841{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-3842{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-3843{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3844{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3845{background:rgba(0,234,255,0.07)}
.bg-blue-3846{background:rgba(45,123,255,0.08)}
.opacity-3847{opacity:0.77}
.radius-3848{border-radius:27px}
.glow-cyan-3849{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-3850{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-3851{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3852{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3853{background:rgba(0,234,255,0.05)}
.bg-blue-3854{background:rgba(45,123,255,0.06)}
.opacity-3855{opacity:0.85}
.radius-3856{border-radius:35px}
.glow-cyan-3857{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-3858{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-3859{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3860{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3861{background:rgba(0,234,255,0.03)}
.bg-blue-3862{background:rgba(45,123,255,0.04)}
.opacity-3863{opacity:0.93}
.radius-3864{border-radius:1px}
.glow-cyan-3865{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-3866{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-3867{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3868{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3869{background:rgba(0,234,255,0.11)}
.bg-blue-3870{background:rgba(45,123,255,0.02)}
.opacity-3871{opacity:0.11}
.radius-3872{border-radius:9px}
.glow-cyan-3873{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-3874{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-3875{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3876{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3877{background:rgba(0,234,255,0.09)}
.bg-blue-3878{background:rgba(45,123,255,0.10)}
.opacity-3879{opacity:0.19}
.radius-3880{border-radius:17px}
.glow-cyan-3881{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-3882{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-3883{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3884{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3885{background:rgba(0,234,255,0.07)}
.bg-blue-3886{background:rgba(45,123,255,0.08)}
.opacity-3887{opacity:0.27}
.radius-3888{border-radius:25px}
.glow-cyan-3889{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-3890{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-3891{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3892{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3893{background:rgba(0,234,255,0.05)}
.bg-blue-3894{background:rgba(45,123,255,0.06)}
.opacity-3895{opacity:0.35}
.radius-3896{border-radius:33px}
.glow-cyan-3897{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-3898{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-3899{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3900{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3901{background:rgba(0,234,255,0.03)}
.bg-blue-3902{background:rgba(45,123,255,0.04)}
.opacity-3903{opacity:0.43}
.radius-3904{border-radius:41px}
.glow-cyan-3905{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-3906{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-3907{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3908{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3909{background:rgba(0,234,255,0.11)}
.bg-blue-3910{background:rgba(45,123,255,0.02)}
.opacity-3911{opacity:0.51}
.radius-3912{border-radius:7px}
.glow-cyan-3913{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-3914{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-3915{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3916{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3917{background:rgba(0,234,255,0.09)}
.bg-blue-3918{background:rgba(45,123,255,0.10)}
.opacity-3919{opacity:0.59}
.radius-3920{border-radius:15px}
.glow-cyan-3921{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-3922{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-3923{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3924{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3925{background:rgba(0,234,255,0.07)}
.bg-blue-3926{background:rgba(45,123,255,0.08)}
.opacity-3927{opacity:0.67}
.radius-3928{border-radius:23px}
.glow-cyan-3929{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-3930{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-3931{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3932{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3933{background:rgba(0,234,255,0.05)}
.bg-blue-3934{background:rgba(45,123,255,0.06)}
.opacity-3935{opacity:0.75}
.radius-3936{border-radius:31px}
.glow-cyan-3937{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-3938{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-3939{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3940{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3941{background:rgba(0,234,255,0.03)}
.bg-blue-3942{background:rgba(45,123,255,0.04)}
.opacity-3943{opacity:0.83}
.radius-3944{border-radius:39px}
.glow-cyan-3945{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-3946{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-3947{border:1px solid rgba(0,234,255,0.21)}
.border-blue-3948{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-3949{background:rgba(0,234,255,0.11)}
.bg-blue-3950{background:rgba(45,123,255,0.02)}
.opacity-3951{opacity:0.91}
.radius-3952{border-radius:5px}
.glow-cyan-3953{box-shadow:0 0 5px rgba(0,234,255,0.13)}
.glow-blue-3954{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-3955{border:1px solid rgba(0,234,255,0.15)}
.border-blue-3956{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-3957{background:rgba(0,234,255,0.09)}
.bg-blue-3958{background:rgba(45,123,255,0.10)}
.opacity-3959{opacity:0.99}
.radius-3960{border-radius:13px}
.glow-cyan-3961{box-shadow:0 0 13px rgba(0,234,255,0.09)}
.glow-blue-3962{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-3963{border:1px solid rgba(0,234,255,0.09)}
.border-blue-3964{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-3965{background:rgba(0,234,255,0.07)}
.bg-blue-3966{background:rgba(45,123,255,0.08)}
.opacity-3967{opacity:0.17}
.radius-3968{border-radius:21px}
.glow-cyan-3969{box-shadow:0 0 21px rgba(0,234,255,0.17)}
.glow-blue-3970{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-3971{border:1px solid rgba(0,234,255,0.17)}
.border-blue-3972{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-3973{background:rgba(0,234,255,0.05)}
.bg-blue-3974{background:rgba(45,123,255,0.06)}
.opacity-3975{opacity:0.25}
.radius-3976{border-radius:29px}
.glow-cyan-3977{box-shadow:0 0 29px rgba(0,234,255,0.13)}
.glow-blue-3978{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-3979{border:1px solid rgba(0,234,255,0.11)}
.border-blue-3980{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-3981{background:rgba(0,234,255,0.03)}
.bg-blue-3982{background:rgba(45,123,255,0.04)}
.opacity-3983{opacity:0.33}
.radius-3984{border-radius:37px}
.glow-cyan-3985{box-shadow:0 0 11px rgba(0,234,255,0.09)}
.glow-blue-3986{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-3987{border:1px solid rgba(0,234,255,0.19)}
.border-blue-3988{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-3989{background:rgba(0,234,255,0.11)}
.bg-blue-3990{background:rgba(45,123,255,0.02)}
.opacity-3991{opacity:0.41}
.radius-3992{border-radius:3px}
.glow-cyan-3993{box-shadow:0 0 19px rgba(0,234,255,0.17)}
.glow-blue-3994{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-3995{border:1px solid rgba(0,234,255,0.13)}
.border-blue-3996{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-3997{background:rgba(0,234,255,0.09)}
.bg-blue-3998{background:rgba(45,123,255,0.10)}
.opacity-3999{opacity:0.49}
.radius-4000{border-radius:11px}
.glow-cyan-4001{box-shadow:0 0 27px rgba(0,234,255,0.13)}
.glow-blue-4002{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-4003{border:1px solid rgba(0,234,255,0.21)}
.border-blue-4004{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-4005{background:rgba(0,234,255,0.07)}
.bg-blue-4006{background:rgba(45,123,255,0.08)}
.opacity-4007{opacity:0.57}
.radius-4008{border-radius:19px}
.glow-cyan-4009{box-shadow:0 0 9px rgba(0,234,255,0.09)}
.glow-blue-4010{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-4011{border:1px solid rgba(0,234,255,0.15)}
.border-blue-4012{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-4013{background:rgba(0,234,255,0.05)}
.bg-blue-4014{background:rgba(45,123,255,0.06)}
.opacity-4015{opacity:0.65}
.radius-4016{border-radius:27px}
.glow-cyan-4017{box-shadow:0 0 17px rgba(0,234,255,0.17)}
.glow-blue-4018{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-4019{border:1px solid rgba(0,234,255,0.09)}
.border-blue-4020{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-4021{background:rgba(0,234,255,0.03)}
.bg-blue-4022{background:rgba(45,123,255,0.04)}
.opacity-4023{opacity:0.73}
.radius-4024{border-radius:35px}
.glow-cyan-4025{box-shadow:0 0 25px rgba(0,234,255,0.13)}
.glow-blue-4026{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-4027{border:1px solid rgba(0,234,255,0.17)}
.border-blue-4028{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-4029{background:rgba(0,234,255,0.11)}
.bg-blue-4030{background:rgba(45,123,255,0.02)}
.opacity-4031{opacity:0.81}
.radius-4032{border-radius:1px}
.glow-cyan-4033{box-shadow:0 0 7px rgba(0,234,255,0.09)}
.glow-blue-4034{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-4035{border:1px solid rgba(0,234,255,0.11)}
.border-blue-4036{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-4037{background:rgba(0,234,255,0.09)}
.bg-blue-4038{background:rgba(45,123,255,0.10)}
.opacity-4039{opacity:0.89}
.radius-4040{border-radius:9px}
.glow-cyan-4041{box-shadow:0 0 15px rgba(0,234,255,0.17)}
.glow-blue-4042{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-4043{border:1px solid rgba(0,234,255,0.19)}
.border-blue-4044{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-4045{background:rgba(0,234,255,0.07)}
.bg-blue-4046{background:rgba(45,123,255,0.08)}
.opacity-4047{opacity:0.97}
.radius-4048{border-radius:17px}
.glow-cyan-4049{box-shadow:0 0 23px rgba(0,234,255,0.13)}
.glow-blue-4050{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-4051{border:1px solid rgba(0,234,255,0.13)}
.border-blue-4052{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-4053{background:rgba(0,234,255,0.05)}
.bg-blue-4054{background:rgba(45,123,255,0.06)}
.opacity-4055{opacity:0.15}
.radius-4056{border-radius:25px}
.glow-cyan-4057{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-4058{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-4059{border:1px solid rgba(0,234,255,0.21)}
.border-blue-4060{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-4061{background:rgba(0,234,255,0.03)}
.bg-blue-4062{background:rgba(45,123,255,0.04)}
.opacity-4063{opacity:0.23}
.radius-4064{border-radius:33px}
.glow-cyan-4065{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-4066{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-4067{border:1px solid rgba(0,234,255,0.15)}
.border-blue-4068{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-4069{background:rgba(0,234,255,0.11)}
.bg-blue-4070{background:rgba(45,123,255,0.02)}
.opacity-4071{opacity:0.31}
.radius-4072{border-radius:41px}
.glow-cyan-4073{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-4074{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-4075{border:1px solid rgba(0,234,255,0.09)}
.border-blue-4076{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-4077{background:rgba(0,234,255,0.09)}
.bg-blue-4078{background:rgba(45,123,255,0.10)}
.opacity-4079{opacity:0.39}
.radius-4080{border-radius:7px}
.glow-cyan-4081{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-4082{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-4083{border:1px solid rgba(0,234,255,0.17)}
.border-blue-4084{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-4085{background:rgba(0,234,255,0.07)}
.bg-blue-4086{background:rgba(45,123,255,0.08)}
.opacity-4087{opacity:0.47}
.radius-4088{border-radius:15px}
.glow-cyan-4089{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-4090{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-4091{border:1px solid rgba(0,234,255,0.11)}
.border-blue-4092{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-4093{background:rgba(0,234,255,0.05)}
.bg-blue-4094{background:rgba(45,123,255,0.06)}
.opacity-4095{opacity:0.55}
.radius-4096{border-radius:23px}
.glow-cyan-4097{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-4098{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-4099{border:1px solid rgba(0,234,255,0.19)}
.border-blue-4100{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-4101{background:rgba(0,234,255,0.03)}
.bg-blue-4102{background:rgba(45,123,255,0.04)}
.opacity-4103{opacity:0.63}
.radius-4104{border-radius:31px}
.glow-cyan-4105{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-4106{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-4107{border:1px solid rgba(0,234,255,0.13)}
.border-blue-4108{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-4109{background:rgba(0,234,255,0.11)}
.bg-blue-4110{background:rgba(45,123,255,0.02)}
.opacity-4111{opacity:0.71}
.radius-4112{border-radius:39px}
.glow-cyan-4113{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-4114{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-4115{border:1px solid rgba(0,234,255,0.21)}
.border-blue-4116{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-4117{background:rgba(0,234,255,0.09)}
.bg-blue-4118{background:rgba(45,123,255,0.10)}
.opacity-4119{opacity:0.79}
.radius-4120{border-radius:5px}
.glow-cyan-4121{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-4122{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-4123{border:1px solid rgba(0,234,255,0.15)}
.border-blue-4124{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-4125{background:rgba(0,234,255,0.07)}
.bg-blue-4126{background:rgba(45,123,255,0.08)}
.opacity-4127{opacity:0.87}
.radius-4128{border-radius:13px}
.glow-cyan-4129{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-4130{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-4131{border:1px solid rgba(0,234,255,0.09)}
.border-blue-4132{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-4133{background:rgba(0,234,255,0.05)}
.bg-blue-4134{background:rgba(45,123,255,0.06)}
.opacity-4135{opacity:0.95}
.radius-4136{border-radius:21px}
.glow-cyan-4137{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-4138{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-4139{border:1px solid rgba(0,234,255,0.17)}
.border-blue-4140{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-4141{background:rgba(0,234,255,0.03)}
.bg-blue-4142{background:rgba(45,123,255,0.04)}
.opacity-4143{opacity:0.13}
.radius-4144{border-radius:29px}
.glow-cyan-4145{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-4146{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-4147{border:1px solid rgba(0,234,255,0.11)}
.border-blue-4148{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-4149{background:rgba(0,234,255,0.11)}
.bg-blue-4150{background:rgba(45,123,255,0.02)}
.opacity-4151{opacity:0.21}
.radius-4152{border-radius:37px}
.glow-cyan-4153{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-4154{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-4155{border:1px solid rgba(0,234,255,0.19)}
.border-blue-4156{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-4157{background:rgba(0,234,255,0.09)}
.bg-blue-4158{background:rgba(45,123,255,0.10)}
.opacity-4159{opacity:0.29}
.radius-4160{border-radius:3px}
.glow-cyan-4161{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-4162{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-4163{border:1px solid rgba(0,234,255,0.13)}
.border-blue-4164{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-4165{background:rgba(0,234,255,0.07)}
.bg-blue-4166{background:rgba(45,123,255,0.08)}
.opacity-4167{opacity:0.37}
.radius-4168{border-radius:11px}
.glow-cyan-4169{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-4170{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-4171{border:1px solid rgba(0,234,255,0.21)}
.border-blue-4172{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-4173{background:rgba(0,234,255,0.05)}
.bg-blue-4174{background:rgba(45,123,255,0.06)}
.opacity-4175{opacity:0.45}
.radius-4176{border-radius:19px}
.glow-cyan-4177{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-4178{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-4179{border:1px solid rgba(0,234,255,0.15)}
.border-blue-4180{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-4181{background:rgba(0,234,255,0.03)}
.bg-blue-4182{background:rgba(45,123,255,0.04)}
.opacity-4183{opacity:0.53}
.radius-4184{border-radius:27px}
.glow-cyan-4185{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-4186{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-4187{border:1px solid rgba(0,234,255,0.09)}
.border-blue-4188{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-4189{background:rgba(0,234,255,0.11)}
.bg-blue-4190{background:rgba(45,123,255,0.02)}
.opacity-4191{opacity:0.61}
.radius-4192{border-radius:35px}
.glow-cyan-4193{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-4194{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-4195{border:1px solid rgba(0,234,255,0.17)}
.border-blue-4196{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-4197{background:rgba(0,234,255,0.09)}
.bg-blue-4198{background:rgba(45,123,255,0.10)}
.opacity-4199{opacity:0.69}
.radius-4200{border-radius:1px}
.glow-cyan-4201{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-4202{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-4203{border:1px solid rgba(0,234,255,0.11)}
.border-blue-4204{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-4205{background:rgba(0,234,255,0.07)}
.bg-blue-4206{background:rgba(45,123,255,0.08)}
.opacity-4207{opacity:0.77}
.radius-4208{border-radius:9px}
.glow-cyan-4209{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-4210{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-4211{border:1px solid rgba(0,234,255,0.19)}
.border-blue-4212{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-4213{background:rgba(0,234,255,0.05)}
.bg-blue-4214{background:rgba(45,123,255,0.06)}
.opacity-4215{opacity:0.85}
.radius-4216{border-radius:17px}
.glow-cyan-4217{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-4218{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-4219{border:1px solid rgba(0,234,255,0.13)}
.border-blue-4220{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-4221{background:rgba(0,234,255,0.03)}
.bg-blue-4222{background:rgba(45,123,255,0.04)}
.opacity-4223{opacity:0.93}
.radius-4224{border-radius:25px}
.glow-cyan-4225{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-4226{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-4227{border:1px solid rgba(0,234,255,0.21)}
.border-blue-4228{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-4229{background:rgba(0,234,255,0.11)}
.bg-blue-4230{background:rgba(45,123,255,0.02)}
.opacity-4231{opacity:0.11}
.radius-4232{border-radius:33px}
.glow-cyan-4233{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-4234{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-4235{border:1px solid rgba(0,234,255,0.15)}
.border-blue-4236{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-4237{background:rgba(0,234,255,0.09)}
.bg-blue-4238{background:rgba(45,123,255,0.10)}
.opacity-4239{opacity:0.19}
.radius-4240{border-radius:41px}
.glow-cyan-4241{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-4242{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-4243{border:1px solid rgba(0,234,255,0.09)}
.border-blue-4244{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-4245{background:rgba(0,234,255,0.07)}
.bg-blue-4246{background:rgba(45,123,255,0.08)}
.opacity-4247{opacity:0.27}
.radius-4248{border-radius:7px}
.glow-cyan-4249{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-4250{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-4251{border:1px solid rgba(0,234,255,0.17)}
.border-blue-4252{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-4253{background:rgba(0,234,255,0.05)}
.bg-blue-4254{background:rgba(45,123,255,0.06)}
.opacity-4255{opacity:0.35}
.radius-4256{border-radius:15px}
.glow-cyan-4257{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-4258{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-4259{border:1px solid rgba(0,234,255,0.11)}
.border-blue-4260{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-4261{background:rgba(0,234,255,0.03)}
.bg-blue-4262{background:rgba(45,123,255,0.04)}
.opacity-4263{opacity:0.43}
.radius-4264{border-radius:23px}
.glow-cyan-4265{box-shadow:0 0 5px rgba(0,234,255,0.13)}
.glow-blue-4266{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-4267{border:1px solid rgba(0,234,255,0.19)}
.border-blue-4268{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-4269{background:rgba(0,234,255,0.11)}
.bg-blue-4270{background:rgba(45,123,255,0.02)}
.opacity-4271{opacity:0.51}
.radius-4272{border-radius:31px}
.glow-cyan-4273{box-shadow:0 0 13px rgba(0,234,255,0.09)}
.glow-blue-4274{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-4275{border:1px solid rgba(0,234,255,0.13)}
.border-blue-4276{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-4277{background:rgba(0,234,255,0.09)}
.bg-blue-4278{background:rgba(45,123,255,0.10)}
.opacity-4279{opacity:0.59}
.radius-4280{border-radius:39px}
.glow-cyan-4281{box-shadow:0 0 21px rgba(0,234,255,0.17)}
.glow-blue-4282{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-4283{border:1px solid rgba(0,234,255,0.21)}
.border-blue-4284{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-4285{background:rgba(0,234,255,0.07)}
.bg-blue-4286{background:rgba(45,123,255,0.08)}
.opacity-4287{opacity:0.67}
.radius-4288{border-radius:5px}
.glow-cyan-4289{box-shadow:0 0 29px rgba(0,234,255,0.13)}
.glow-blue-4290{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-4291{border:1px solid rgba(0,234,255,0.15)}
.border-blue-4292{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-4293{background:rgba(0,234,255,0.05)}
.bg-blue-4294{background:rgba(45,123,255,0.06)}
.opacity-4295{opacity:0.75}
.radius-4296{border-radius:13px}
.glow-cyan-4297{box-shadow:0 0 11px rgba(0,234,255,0.09)}
.glow-blue-4298{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-4299{border:1px solid rgba(0,234,255,0.09)}
.border-blue-4300{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-4301{background:rgba(0,234,255,0.03)}
.bg-blue-4302{background:rgba(45,123,255,0.04)}
.opacity-4303{opacity:0.83}
.radius-4304{border-radius:21px}
.glow-cyan-4305{box-shadow:0 0 19px rgba(0,234,255,0.17)}
.glow-blue-4306{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-4307{border:1px solid rgba(0,234,255,0.17)}
.border-blue-4308{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-4309{background:rgba(0,234,255,0.11)}
.bg-blue-4310{background:rgba(45,123,255,0.02)}
.opacity-4311{opacity:0.91}
.radius-4312{border-radius:29px}
.glow-cyan-4313{box-shadow:0 0 27px rgba(0,234,255,0.13)}
.glow-blue-4314{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-4315{border:1px solid rgba(0,234,255,0.11)}
.border-blue-4316{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-4317{background:rgba(0,234,255,0.09)}
.bg-blue-4318{background:rgba(45,123,255,0.10)}
.opacity-4319{opacity:0.99}
.radius-4320{border-radius:37px}
.glow-cyan-4321{box-shadow:0 0 9px rgba(0,234,255,0.09)}
.glow-blue-4322{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-4323{border:1px solid rgba(0,234,255,0.19)}
.border-blue-4324{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-4325{background:rgba(0,234,255,0.07)}
.bg-blue-4326{background:rgba(45,123,255,0.08)}
.opacity-4327{opacity:0.17}
.radius-4328{border-radius:3px}
.glow-cyan-4329{box-shadow:0 0 17px rgba(0,234,255,0.17)}
.glow-blue-4330{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-4331{border:1px solid rgba(0,234,255,0.13)}
.border-blue-4332{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-4333{background:rgba(0,234,255,0.05)}
.bg-blue-4334{background:rgba(45,123,255,0.06)}
.opacity-4335{opacity:0.25}
.radius-4336{border-radius:11px}
.glow-cyan-4337{box-shadow:0 0 25px rgba(0,234,255,0.13)}
.glow-blue-4338{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-4339{border:1px solid rgba(0,234,255,0.21)}
.border-blue-4340{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-4341{background:rgba(0,234,255,0.03)}
.bg-blue-4342{background:rgba(45,123,255,0.04)}
.opacity-4343{opacity:0.33}
.radius-4344{border-radius:19px}
.glow-cyan-4345{box-shadow:0 0 7px rgba(0,234,255,0.09)}
.glow-blue-4346{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-4347{border:1px solid rgba(0,234,255,0.15)}
.border-blue-4348{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-4349{background:rgba(0,234,255,0.11)}
.bg-blue-4350{background:rgba(45,123,255,0.02)}
.opacity-4351{opacity:0.41}
.radius-4352{border-radius:27px}
.glow-cyan-4353{box-shadow:0 0 15px rgba(0,234,255,0.17)}
.glow-blue-4354{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-4355{border:1px solid rgba(0,234,255,0.09)}
.border-blue-4356{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-4357{background:rgba(0,234,255,0.09)}
.bg-blue-4358{background:rgba(45,123,255,0.10)}
.opacity-4359{opacity:0.49}
.radius-4360{border-radius:35px}
.glow-cyan-4361{box-shadow:0 0 23px rgba(0,234,255,0.13)}
.glow-blue-4362{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-4363{border:1px solid rgba(0,234,255,0.17)}
.border-blue-4364{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-4365{background:rgba(0,234,255,0.07)}
.bg-blue-4366{background:rgba(45,123,255,0.08)}
.opacity-4367{opacity:0.57}
.radius-4368{border-radius:1px}
.glow-cyan-4369{box-shadow:0 0 5px rgba(0,234,255,0.09)}
.glow-blue-4370{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-4371{border:1px solid rgba(0,234,255,0.11)}
.border-blue-4372{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-4373{background:rgba(0,234,255,0.05)}
.bg-blue-4374{background:rgba(45,123,255,0.06)}
.opacity-4375{opacity:0.65}
.radius-4376{border-radius:9px}
.glow-cyan-4377{box-shadow:0 0 13px rgba(0,234,255,0.17)}
.glow-blue-4378{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-4379{border:1px solid rgba(0,234,255,0.19)}
.border-blue-4380{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-4381{background:rgba(0,234,255,0.03)}
.bg-blue-4382{background:rgba(45,123,255,0.04)}
.opacity-4383{opacity:0.73}
.radius-4384{border-radius:17px}
.glow-cyan-4385{box-shadow:0 0 21px rgba(0,234,255,0.13)}
.glow-blue-4386{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-4387{border:1px solid rgba(0,234,255,0.13)}
.border-blue-4388{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-4389{background:rgba(0,234,255,0.11)}
.bg-blue-4390{background:rgba(45,123,255,0.02)}
.opacity-4391{opacity:0.81}
.radius-4392{border-radius:25px}
.glow-cyan-4393{box-shadow:0 0 29px rgba(0,234,255,0.09)}
.glow-blue-4394{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-4395{border:1px solid rgba(0,234,255,0.21)}
.border-blue-4396{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-4397{background:rgba(0,234,255,0.09)}
.bg-blue-4398{background:rgba(45,123,255,0.10)}
.opacity-4399{opacity:0.89}
.radius-4400{border-radius:33px}
.glow-cyan-4401{box-shadow:0 0 11px rgba(0,234,255,0.17)}
.glow-blue-4402{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-4403{border:1px solid rgba(0,234,255,0.15)}
.border-blue-4404{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-4405{background:rgba(0,234,255,0.07)}
.bg-blue-4406{background:rgba(45,123,255,0.08)}
.opacity-4407{opacity:0.97}
.radius-4408{border-radius:41px}
.glow-cyan-4409{box-shadow:0 0 19px rgba(0,234,255,0.13)}
.glow-blue-4410{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-4411{border:1px solid rgba(0,234,255,0.09)}
.border-blue-4412{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-4413{background:rgba(0,234,255,0.05)}
.bg-blue-4414{background:rgba(45,123,255,0.06)}
.opacity-4415{opacity:0.15}
.radius-4416{border-radius:7px}
.glow-cyan-4417{box-shadow:0 0 27px rgba(0,234,255,0.09)}
.glow-blue-4418{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-4419{border:1px solid rgba(0,234,255,0.17)}
.border-blue-4420{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-4421{background:rgba(0,234,255,0.03)}
.bg-blue-4422{background:rgba(45,123,255,0.04)}
.opacity-4423{opacity:0.23}
.radius-4424{border-radius:15px}
.glow-cyan-4425{box-shadow:0 0 9px rgba(0,234,255,0.17)}
.glow-blue-4426{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-4427{border:1px solid rgba(0,234,255,0.11)}
.border-blue-4428{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-4429{background:rgba(0,234,255,0.11)}
.bg-blue-4430{background:rgba(45,123,255,0.02)}
.opacity-4431{opacity:0.31}
.radius-4432{border-radius:23px}
.glow-cyan-4433{box-shadow:0 0 17px rgba(0,234,255,0.13)}
.glow-blue-4434{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-4435{border:1px solid rgba(0,234,255,0.19)}
.border-blue-4436{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-4437{background:rgba(0,234,255,0.09)}
.bg-blue-4438{background:rgba(45,123,255,0.10)}
.opacity-4439{opacity:0.39}
.radius-4440{border-radius:31px}
.glow-cyan-4441{box-shadow:0 0 25px rgba(0,234,255,0.09)}
.glow-blue-4442{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-4443{border:1px solid rgba(0,234,255,0.13)}
.border-blue-4444{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-4445{background:rgba(0,234,255,0.07)}
.bg-blue-4446{background:rgba(45,123,255,0.08)}
.opacity-4447{opacity:0.47}
.radius-4448{border-radius:39px}
.glow-cyan-4449{box-shadow:0 0 7px rgba(0,234,255,0.17)}
.glow-blue-4450{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-4451{border:1px solid rgba(0,234,255,0.21)}
.border-blue-4452{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-4453{background:rgba(0,234,255,0.05)}
.bg-blue-4454{background:rgba(45,123,255,0.06)}
.opacity-4455{opacity:0.55}
.radius-4456{border-radius:5px}
.glow-cyan-4457{box-shadow:0 0 15px rgba(0,234,255,0.13)}
.glow-blue-4458{box-shadow:0 0 22px rgba(45,123,255,0.14)}
.border-cyan-4459{border:1px solid rgba(0,234,255,0.15)}
.border-blue-4460{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-4461{background:rgba(0,234,255,0.03)}
.bg-blue-4462{background:rgba(45,123,255,0.04)}
.opacity-4463{opacity:0.63}
.radius-4464{border-radius:13px}
.glow-cyan-4465{box-shadow:0 0 23px rgba(0,234,255,0.09)}
.glow-blue-4466{box-shadow:0 0 30px rgba(45,123,255,0.10)}
.border-cyan-4467{border:1px solid rgba(0,234,255,0.09)}
.border-blue-4468{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-4469{background:rgba(0,234,255,0.11)}
.bg-blue-4470{background:rgba(45,123,255,0.02)}
.opacity-4471{opacity:0.71}
.radius-4472{border-radius:21px}
.glow-cyan-4473{box-shadow:0 0 5px rgba(0,234,255,0.17)}
.glow-blue-4474{box-shadow:0 0 8px rgba(45,123,255,0.18)}
.border-cyan-4475{border:1px solid rgba(0,234,255,0.17)}
.border-blue-4476{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-4477{background:rgba(0,234,255,0.09)}
.bg-blue-4478{background:rgba(45,123,255,0.10)}
.opacity-4479{opacity:0.79}
.radius-4480{border-radius:29px}
.glow-cyan-4481{box-shadow:0 0 13px rgba(0,234,255,0.13)}
.glow-blue-4482{box-shadow:0 0 16px rgba(45,123,255,0.14)}
.border-cyan-4483{border:1px solid rgba(0,234,255,0.11)}
.border-blue-4484{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-4485{background:rgba(0,234,255,0.07)}
.bg-blue-4486{background:rgba(45,123,255,0.08)}
.opacity-4487{opacity:0.87}
.radius-4488{border-radius:37px}
.glow-cyan-4489{box-shadow:0 0 21px rgba(0,234,255,0.09)}
.glow-blue-4490{box-shadow:0 0 24px rgba(45,123,255,0.10)}
.border-cyan-4491{border:1px solid rgba(0,234,255,0.19)}
.border-blue-4492{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-4493{background:rgba(0,234,255,0.05)}
.bg-blue-4494{background:rgba(45,123,255,0.06)}
.opacity-4495{opacity:0.95}
.radius-4496{border-radius:3px}
.glow-cyan-4497{box-shadow:0 0 29px rgba(0,234,255,0.17)}
.glow-blue-4498{box-shadow:0 0 32px rgba(45,123,255,0.18)}
.border-cyan-4499{border:1px solid rgba(0,234,255,0.13)}
.border-blue-4500{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-4501{background:rgba(0,234,255,0.03)}
.bg-blue-4502{background:rgba(45,123,255,0.04)}
.opacity-4503{opacity:0.13}
.radius-4504{border-radius:11px}
.glow-cyan-4505{box-shadow:0 0 11px rgba(0,234,255,0.13)}
.glow-blue-4506{box-shadow:0 0 10px rgba(45,123,255,0.14)}
.border-cyan-4507{border:1px solid rgba(0,234,255,0.21)}
.border-blue-4508{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-4509{background:rgba(0,234,255,0.11)}
.bg-blue-4510{background:rgba(45,123,255,0.02)}
.opacity-4511{opacity:0.21}
.radius-4512{border-radius:19px}
.glow-cyan-4513{box-shadow:0 0 19px rgba(0,234,255,0.09)}
.glow-blue-4514{box-shadow:0 0 18px rgba(45,123,255,0.10)}
.border-cyan-4515{border:1px solid rgba(0,234,255,0.15)}
.border-blue-4516{border:1px solid rgba(45,123,255,0.16)}
.bg-cyan-4517{background:rgba(0,234,255,0.09)}
.bg-blue-4518{background:rgba(45,123,255,0.10)}
.opacity-4519{opacity:0.29}
.radius-4520{border-radius:27px}
.glow-cyan-4521{box-shadow:0 0 27px rgba(0,234,255,0.17)}
.glow-blue-4522{box-shadow:0 0 26px rgba(45,123,255,0.18)}
.border-cyan-4523{border:1px solid rgba(0,234,255,0.09)}
.border-blue-4524{border:1px solid rgba(45,123,255,0.10)}
.bg-cyan-4525{background:rgba(0,234,255,0.07)}
.bg-blue-4526{background:rgba(45,123,255,0.08)}
.opacity-4527{opacity:0.37}
.radius-4528{border-radius:35px}
.glow-cyan-4529{box-shadow:0 0 9px rgba(0,234,255,0.13)}
.glow-blue-4530{box-shadow:0 0 4px rgba(45,123,255,0.14)}
.border-cyan-4531{border:1px solid rgba(0,234,255,0.17)}
.border-blue-4532{border:1px solid rgba(45,123,255,0.18)}
.bg-cyan-4533{background:rgba(0,234,255,0.05)}
.bg-blue-4534{background:rgba(45,123,255,0.06)}
.opacity-4535{opacity:0.45}
.radius-4536{border-radius:1px}
.glow-cyan-4537{box-shadow:0 0 17px rgba(0,234,255,0.09)}
.glow-blue-4538{box-shadow:0 0 12px rgba(45,123,255,0.10)}
.border-cyan-4539{border:1px solid rgba(0,234,255,0.11)}
.border-blue-4540{border:1px solid rgba(45,123,255,0.12)}
.bg-cyan-4541{background:rgba(0,234,255,0.03)}
.bg-blue-4542{background:rgba(45,123,255,0.04)}
.opacity-4543{opacity:0.53}
.radius-4544{border-radius:9px}
.glow-cyan-4545{box-shadow:0 0 25px rgba(0,234,255,0.17)}
.glow-blue-4546{box-shadow:0 0 20px rgba(45,123,255,0.18)}
.border-cyan-4547{border:1px solid rgba(0,234,255,0.19)}
.border-blue-4548{border:1px solid rgba(45,123,255,0.20)}
.bg-cyan-4549{background:rgba(0,234,255,0.11)}
.bg-blue-4550{background:rgba(45,123,255,0.02)}
.opacity-4551{opacity:0.61}
.radius-4552{border-radius:17px}
.glow-cyan-4553{box-shadow:0 0 7px rgba(0,234,255,0.13)}
.glow-blue-4554{box-shadow:0 0 28px rgba(45,123,255,0.14)}
.border-cyan-4555{border:1px solid rgba(0,234,255,0.13)}
.border-blue-4556{border:1px solid rgba(45,123,255,0.14)}
.bg-cyan-4557{background:rgba(0,234,255,0.09)}
.bg-blue-4558{background:rgba(45,123,255,0.10)}
.opacity-4559{opacity:0.69}
.radius-4560{border-radius:25px}
.glow-cyan-4561{box-shadow:0 0 15px rgba(0,234,255,0.09)}
.glow-blue-4562{box-shadow:0 0 6px rgba(45,123,255,0.10)}
.border-cyan-4563{border:1px solid rgba(0,234,255,0.21)}
.border-blue-4564{border:1px solid rgba(45,123,255,0.08)}
.bg-cyan-4565{background:rgba(0,234,255,0.07)}
.bg-blue-4566{background:rgba(45,123,255,0.08)}
.opacity-4567{opacity:0.77}
.radius-4568{border-radius:33px}
.glow-cyan-4569{box-shadow:0 0 23px rgba(0,234,255,0.17)}
.glow-blue-4570{box-shadow:0 0 14px rgba(45,123,255,0.18)}
.border-cyan-4571{border:1px solid rgba(0,234,255,0.15)}
.border-blue-4572{border:1px solid rgba(45,123,255,0.16)}
</style>
</head>
<body>
<svg width="0" height="0" style="position:absolute"><defs>
<symbol id="icon-star" viewBox="0 0 24 24"><path fill="currentColor" d="m12 2 2.7 6.2 6.8.6-5.1 4.5 1.5 6.7L12 16.5 6.1 20l1.5-6.7-5.1-4.5 6.8-.6z"/></symbol>
<symbol id="icon-check" viewBox="0 0 24 24"><path fill="currentColor" d="m9.2 16.6-4.1-4.1-1.7 1.7 5.8 5.8L20.7 8.5 19 6.8z"/></symbol>
</defs></svg>

<?php if (!$sudahLogin): ?>
<div class="login-screen">
    <div class="login-orb orb1"></div>
    <div class="login-orb orb2"></div>
    <div class="login-card">
        <section class="login-left">
            <div class="brand-mark">
                <div class="brand-circle">UTD</div>
                <div><div class="brand-name">Universitas Teknologi Digital</div><div class="brand-caption">Sistem Informasi Akademik</div></div>
            </div>
            <h1 class="login-headline">Kelola akademik<br><span>lebih terstruktur.</span></h1>
            <p class="login-copy">Portal akademik simulasi untuk mengakses informasi mahasiswa, KRS, KHS, jadwal, absensi, ujian, kalender akademik, dan pembayaran UKT dalam satu dashboard.</p>
            <div class="feature-list">
                <div class="feature-item"><span class="feature-dot"></span><div><strong>Dashboard Akademik</strong><span>Ringkasan semester, IPK, SKS, dan aktivitas terbaru.</span></div></div>
                <div class="feature-item"><span class="feature-dot"></span><div><strong>KRS & KHS</strong><span>Pengisian KRS dan tampilan hasil studi mahasiswa.</span></div></div>
                <div class="feature-item"><span class="feature-dot"></span><div><strong>UKT & Informasi Ujian</strong><span>Status pembayaran dan jadwal akademik terintegrasi.</span></div></div>
            </div>
        </section>
        <section class="login-right">
            <div class="login-form-title">Masuk ke SIAKAD</div>
            <div class="login-form-sub">Gunakan Nomor BP dan password yang terdaftar.</div>
            <form action="ProsesSiakad.php" method="POST" autocomplete="off">
                <input type="hidden" name="aksi" value="login">
                <div class="field"><label>Nomor BP <span>Wajib</span></label><input type="text" name="bp" placeholder="Masukkan Nomor BP" required></div>
                <div class="field"><label>Password <span>Wajib</span></label><div class="password-wrap"><input type="password" name="password" id="password" placeholder="Masukkan password" required><span class="password-toggle" id="passwordToggle" onclick="togglePassword()">◉</span></div></div>
                <button class="login-submit" type="submit">MASUK KE SISTEM AKADEMIK</button>
            </form>
            <div class="login-note">Aplikasi akademik simulasi • 2026</div>
        </section>
    </div>
</div>
<?php else: ?>
<div class="app">
<aside class="sidebar" id="sidebar">
    <div class="side-brand">
        <div class="side-brand-main"><div class="side-logo">UTD</div><div><h2>SIAKAD UTD</h2><p>Academic Portal</p></div></div>
    </div>
    <div class="side-user"><div class="avatar"><?= e($avatar) ?></div><div><strong><?= e($mahasiswa['nama']) ?></strong><span><?= e($mahasiswa['bp']) ?> • <?= e($mahasiswa['kode_jurusan']) ?></span></div></div>
    <div class="side-menu">
        <div class="menu-title">Utama</div>
        <a class="<?= $halaman==='dashboard'?'active':'' ?>" href="?halaman=dashboard"><span class="menu-ico">⌂</span>Dashboard</a>
        <a class="<?= $halaman==='kalender'?'active':'' ?>" href="?halaman=kalender"><span class="menu-ico">◫</span>Kalender Akademik</a>
        <div class="menu-title">Akademik</div>
        <a class="<?= $halaman==='kurikulum'?'active':'' ?>" href="?halaman=kurikulum"><span class="menu-ico">▤</span>Kurikulum</a>
        <a class="<?= $halaman==='jadwal'?'active':'' ?>" href="?halaman=jadwal"><span class="menu-ico">◷</span>Jadwal Kuliah</a>
        <a class="<?= $halaman==='krs'?'active':'' ?>" href="?halaman=krs"><span class="menu-ico">☑</span>KRS</a>
        <a class="<?= $halaman==='khs'?'active':'' ?>" href="?halaman=khs"><span class="menu-ico">▥</span>KHS / Nilai</a>
        <a class="<?= $halaman==='absensi'?'active':'' ?>" href="?halaman=absensi"><span class="menu-ico">✓</span>Absensi</a>
        <a class="<?= $halaman==='transkrip'?'active':'' ?>" href="?halaman=transkrip"><span class="menu-ico">▥</span>Transkrip Nilai</a>
        <div class="menu-title">Ujian</div>
        <a class="<?= $halaman==='uts'?'active':'' ?>" href="?halaman=uts"><span class="menu-ico">▣</span>Jadwal UTS</a>
        <a class="<?= $halaman==='uas'?'active':'' ?>" href="?halaman=uas"><span class="menu-ico">▦</span>Jadwal UAS</a>
        <a class="<?= $halaman==='ujian'?'active':'' ?>" href="?halaman=ujian"><span class="menu-ico">◇</span>Jadwal Ujian</a>
        <div class="menu-title">Keuangan</div>
        <a class="<?= $halaman==='ukt'?'active':'' ?>" href="?halaman=ukt"><span class="menu-ico">▤</span>UKT / Pembayaran <?php if($uktStatus!=='Sudah Bayar'): ?><span class="menu-badge">AKTIF</span><?php endif; ?></a>
        <div class="menu-title">Informasi & Akun</div>
        <a class="<?= $halaman==='pengumuman'?'active':'' ?>" href="?halaman=pengumuman"><span class="menu-ico">!</span>Pengumuman</a>
        <a class="<?= $halaman==='dokumen'?'active':'' ?>" href="?halaman=dokumen"><span class="menu-ico">▱</span>Dokumen Akademik</a>
        <a class="<?= $halaman==='profil'?'active':'' ?>" href="?halaman=profil"><span class="menu-ico">◉</span>Profil</a>
        <a class="<?= $halaman==='pengaturan'?'active':'' ?>" href="?halaman=pengaturan"><span class="menu-ico">⚙</span>Pengaturan</a>
        <a class="<?= $halaman==='bantuan'?'active':'' ?>" href="?halaman=bantuan"><span class="menu-ico">?</span>Pusat Bantuan</a>
        <a href="ProsesSiakad.php?aksi=logout" class="danger" style="margin-top:10px"><span class="menu-ico">↪</span>Logout</a>
    </div>
</aside>
<div class="mobile-overlay" id="mobileOverlay" onclick="closeSidebar()"></div>
<main class="main">
    <header class="topbar">
        <div class="top-left">
            <button class="mobile-menu" onclick="openSidebar()">☰</button>
            <div class="crumbs">SIAKAD / <strong><?= e(ucwords(str_replace('_',' ',$halaman))) ?></strong></div>
        </div>
        <div class="top-actions">
            <div class="top-icon" onclick="showToast('Notifikasi','Belum ada notifikasi baru.')">♢<span class="notify-dot"></span></div>
            <button class="top-icon" onclick="toggleCommand()">⌕</button>
            <div class="profile-mini"><div class="avatar" style="width:29px;height:29px;border-radius:9px;font-size:9px"><?= e($avatar) ?></div><div><strong><?= e($mahasiswa['nama']) ?></strong><span><?= e($mahasiswa['jurusan']) ?></span></div></div>
        </div>
    </header>

    <section class="content">
        <?php if($pesan): ?><div class="notice mb-4"><strong>Informasi:</strong> <?= e($pesan) ?></div><?php endif; ?>

        <?php if($halaman==='dashboard'): ?>
        <div class="section-head"><div><h1>Dashboard Akademik</h1><p>Ringkasan informasi akademik mahasiswa dalam satu halaman.</p></div><div class="section-actions"><button class="btn btn-secondary" onclick="window.print()">Cetak</button><a class="btn btn-primary" href="?halaman=krs">Buka KRS</a></div></div>
        <div class="hero">
            <div class="hero-top"><div><h1>Selamat datang, <?= e($mahasiswa['nama']) ?> 👋</h1><p><?= e($mahasiswa['jurusan']) ?> • Semester <?= e($mahasiswa['semester']) ?> • BP <?= e($mahasiswa['bp']) ?></p><div class="hero-meta"><span class="hero-pill">Tahun Akademik 2026/2027</span><span class="hero-pill">Semester Ganjil</span><span class="hero-pill">Kode <?= e($mahasiswa['kode_jurusan']) ?></span></div></div><div class="badge badge-success">● Sistem Aktif</div></div>
        </div>
        <div class="quick-grid">
            <div class="stat"><div class="stat-top"><div class="stat-icon">▥</div><span class="badge badge-info">IPK</span></div><div class="stat-label">Indeks Prestasi Kumulatif</div><div class="stat-value cyan"><?= number_format((float)$mahasiswa['ipk'],2) ?></div><div class="stat-note">Data tersimpan di database</div></div>
            <div class="stat"><div class="stat-top"><div class="stat-icon">◈</div><span class="badge badge-info">Semester</span></div><div class="stat-label">Semester Aktif</div><div class="stat-value"><?= e($mahasiswa['semester']) ?></div><div class="stat-note"><?= e($mahasiswa['jurusan']) ?></div></div>
            <div class="stat"><div class="stat-top"><div class="stat-icon">▤</div><span class="badge badge-info">KRS</span></div><div class="stat-label">SKS Diambil</div><div class="stat-value"><?= e($totalSks) ?></div><div class="stat-note">Mata kuliah: <?= count($krs) ?></div></div>
            <div class="stat"><div class="stat-top"><div class="stat-icon">Rp</div><span class="badge <?= $uktStatus==='Sudah Bayar'?'badge-success':'badge-warning' ?>"><?= e($uktStatus) ?></span></div><div class="stat-label">Tagihan UKT</div><div class="stat-value" style="font-size:18px"><?= rupiah($uktJumlah) ?></div><div class="stat-note">Batas: 10 Oktober 2026</div></div>
        </div>
        <div class="grid g3 mt-4">
            <div class="card" style="grid-column:span 2"><div class="card-title">Jadwal Kuliah Hari Ini</div><div class="grid g2">
            <?php if($jadwalRows): foreach(array_slice($jadwalRows,0,4) as $row): ?><div class="schedule-card"><div class="schedule-time"><?= e($row['jam']) ?> • <?= e($row['hari']) ?></div><div class="schedule-name"><?= e($row['nama']) ?></div><div class="schedule-meta"><?= e($row['dosen']) ?> • <?= e($row['ruang']) ?></div></div><?php endforeach; else: ?><div class="empty">Belum ada jadwal karena KRS belum tersimpan.</div><?php endif; ?></div></div>
            <div class="card"><div class="card-title">Status Akademik</div><div class="list-stack"><div class="list-item"><div class="title">KRS</div><div class="desc"><?= count($krs) ?> mata kuliah tersimpan</div></div><div class="list-item"><div class="title">UKT</div><div class="desc"><?= e($uktStatus) ?></div></div><div class="list-item"><div class="title">Absensi</div><div class="desc">Pantau kehadiran pada menu Absensi</div></div></div></div>
        </div>
        <div class="grid g2 mt-4">
            <div class="card"><div class="card-title">Agenda Akademik</div><div class="list-stack"><div class="list-item"><div class="title">Pembayaran UKT</div><div class="desc">Batas pembayaran 10 Oktober 2026 • 23:59 WIB</div><div class="date">KEUANGAN</div></div><div class="list-item"><div class="title">UTS Semester Ganjil</div><div class="desc">Mulai 20 Oktober 2026</div><div class="date">UJIAN</div></div><div class="list-item"><div class="title">UAS Semester Ganjil</div><div class="desc">Mulai 15 Desember 2026</div><div class="date">UJIAN</div></div></div></div>
            <div class="card"><div class="card-title">Progress Semester</div><div class="stat-label">SKS KRS</div><div class="between mt-1"><strong><?= e($totalSks) ?> / 24 SKS</strong><span class="small cyan"><?= min(100,round(($totalSks/24)*100)) ?>%</span></div><div class="progress mt-2"><span style="width:<?= min(100,round(($totalSks/24)*100)) ?>%"></span></div><div class="notice mt-4">Batas rekomendasi pengambilan mata kuliah semester ini adalah 24 SKS pada simulasi.</div></div>
        </div>

        <?php elseif($halaman==='kalender'): ?>
        <div class="section-head"><div><h1>Kalender Akademik</h1><p>Agenda akademik semester ganjil tahun akademik 2026/2027.</p></div><button class="btn btn-secondary" onclick="window.print()">Cetak Kalender</button></div>
        <div class="card"><div class="between mb-4"><div class="card-title" style="margin:0">September - Desember 2026</div><span class="badge badge-info">TA 2026/2027</span></div><div class="calendar">
        <?php foreach(['Sen','Sel','Rab','Kam','Jum','Sab','Min'] as $h): ?><div class="cal-head"><?= $h ?></div><?php endforeach; ?>
        <?php for($i=0;$i<7;$i++): ?><div class="cal-day"><div class="cal-num">-</div></div><?php endfor; ?>
        <?php for($d=1;$d<=30;$d++): ?><div class="cal-day <?= $d===24?'cal-today':'' ?>"><div class="cal-num"><?= $d ?></div><?php if($d===1): ?><span class="cal-event">Awal kuliah</span><?php elseif($d===10): ?><span class="cal-event">Batas UKT</span><?php elseif($d===20): ?><span class="cal-event">UTS dimulai</span><?php elseif($d===30): ?><span class="cal-event">Evaluasi</span><?php endif; ?></div><?php endfor; ?>
        </div></div>
        <div class="grid g3 mt-4"><div class="card"><div class="card-title">01 September 2026</div><div class="notice">Perkuliahan semester ganjil dimulai.</div></div><div class="card"><div class="card-title">10 Oktober 2026</div><div class="notice">Batas pembayaran UKT pukul 23:59 WIB.</div></div><div class="card"><div class="card-title">20 Oktober 2026</div><div class="notice">Pelaksanaan UTS dimulai sesuai jadwal masing-masing mata kuliah.</div></div><div class="card"><div class="card-title">15 Desember 2026</div><div class="notice">Pelaksanaan UAS dimulai sesuai jadwal.</div></div><div class="card"><div class="card-title">30 Desember 2026</div><div class="notice">Pengumuman hasil nilai semester.</div></div></div>

        <?php elseif($halaman==='kurikulum'): ?>
        <div class="section-head"><div><h1>Kurikulum Semester <?= e($mahasiswa['semester']) ?></h1><p>Daftar mata kuliah yang tersedia untuk program studi <?= e($mahasiswa['jurusan']) ?>.</p></div></div>
        <div class="card"><div class="table-wrap"><table class="data-table"><thead><tr><th>No</th><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Status</th><th>Jenis</th></tr></thead><tbody><?php foreach($catalogMahasiswa as $i=>$mk): ?><tr><td><?= $i+1 ?></td><td class="code"><?= e($mk[0]) ?></td><td><?= e($mk[1]) ?></td><td><?= e($mk[2]) ?></td><td><span class="badge badge-success">Tersedia</span></td><td><span class="badge badge-info"><?= $i<2?'Wajib':'Pilihan' ?></span></td></tr><?php endforeach; ?></tbody></table></div></div>

        <?php elseif($halaman==='jadwal'): ?>
        <div class="section-head"><div><h1>Jadwal Kuliah</h1><p>Jadwal disusun berdasarkan mata kuliah yang tersimpan di KRS.</p></div><button class="btn btn-secondary" onclick="window.print()">Cetak Jadwal</button></div>
        <div class="grid g2"><?php if($jadwalRows): foreach($jadwalRows as $row): ?><div class="schedule-card"><div class="between"><span class="schedule-time"><?= e($row['hari']) ?> • <?= e($row['jam']) ?></span><span class="badge badge-info"><?= e($row['kode']) ?></span></div><div class="schedule-name"><?= e($row['nama']) ?></div><div class="schedule-meta">👨‍🏫 <?= e($row['dosen']) ?></div><div class="schedule-meta">📍 <?= e($row['ruang']) ?></div></div><?php endforeach; else: ?><div class="card"><div class="empty">Belum ada mata kuliah pada KRS.</div></div><?php endif; ?></div>

        <?php elseif($halaman==='krs'): ?>
        <div class="section-head"><div><h1>Pengisian KRS</h1><p>Pilih mata kuliah lalu simpan menggunakan proses lama ProsesSiakad.php.</p></div><div class="badge badge-info"><?= count($catalogMahasiswa) ?> pilihan</div></div>
        <form action="ProsesSiakad.php" method="POST" onsubmit="return validateKrs()">
            <input type="hidden" name="aksi" value="tambah_krs">
            <div class="grid g2"><div class="card"><div class="card-title">Mata Kuliah Tersedia</div><div class="checklist" id="krsList">
            <?php if($catalogMahasiswa): foreach($catalogMahasiswa as $i=>$mk): ?><label class="check-row"><input type="checkbox" name="mk[]" value="<?= e($mk[0]) ?>|<?= e($mk[1]) ?>|<?= e($mk[2]) ?>" <?= isset($krsAktif[$mk[0]])?'checked':'' ?> onchange="updateKrsSummary()"><div><strong><?= e($mk[0]) ?> • <?= e($mk[1]) ?></strong><span><?= e($mk[2]) ?> SKS • <?= $i<2?'Mata kuliah inti':'Mata kuliah pendukung' ?></span></div></label><?php endforeach; else: ?><div class="empty">Data mata kuliah belum tersedia.</div><?php endif; ?></div><button class="btn btn-primary mt-4" type="submit">Simpan KRS</button></div>
            <div class="card"><div class="card-title">Ringkasan KRS</div><div class="list-stack"><div class="list-item"><div class="title">Mata Kuliah Dipilih</div><div class="desc" id="selectedCount">0 mata kuliah</div></div><div class="list-item"><div class="title">Total SKS</div><div class="desc" id="selectedSks">0 SKS</div></div><div class="list-item"><div class="title">Ketentuan</div><div class="desc">Centang mata kuliah yang akan dipertahankan, lalu tekan Simpan KRS.</div></div></div><div class="notice mt-4">Proses backend tetap menggunakan <strong>aksi=tambah_krs</strong> sesuai sistem sebelumnya.</div></div></div>
        </form>

        <?php elseif($halaman==='khs'): ?>
        <div class="section-head"><div><h1>KHS / Nilai</h1><p>Kartu hasil studi semester aktif dengan IP semester dan mutu.</p></div><button class="btn btn-secondary" onclick="window.print()">Cetak KHS</button></div>
        <div class="quick-grid"><div class="stat"><div class="stat-label">IP Semester</div><div class="stat-value cyan"><?= number_format($ipSemester,2) ?></div><div class="stat-note">Dihitung dari data KRS simulasi</div></div><div class="stat"><div class="stat-label">IPK Database</div><div class="stat-value cyan"><?= number_format((float)$mahasiswa['ipk'],2) ?></div><div class="stat-note">Nilai kumulatif mahasiswa</div></div><div class="stat"><div class="stat-label">Total SKS</div><div class="stat-value"><?= $totalSks ?></div><div class="stat-note">Pada KRS aktif</div></div><div class="stat"><div class="stat-label">Total Mutu</div><div class="stat-value"><?= number_format($totalMutu,2) ?></div><div class="stat-note">SKS × bobot</div></div></div>
        <div class="card mt-4"><div class="table-wrap"><table class="data-table"><thead><tr><th>No</th><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Nilai</th><th>Bobot</th><th>Mutu</th></tr></thead><tbody><?php if($daftarNilai): foreach($daftarNilai as $i=>$n): ?><tr><td><?= $i+1 ?></td><td class="code"><?= e($n['kode']) ?></td><td><?= e($n['nama']) ?></td><td><?= e($n['sks']) ?></td><td><span class="badge badge-success"><?= e($n['nilai']) ?></span></td><td><?= number_format($n['bobot'],2) ?></td><td><?= number_format($n['sks']*$n['bobot'],2) ?></td></tr><?php endforeach; else: ?><tr><td colspan="7" class="empty">Belum ada nilai karena KRS belum tersedia.</td></tr><?php endif; ?></tbody></table></div></div>

        <?php elseif($halaman==='absensi'): ?>
        <div class="section-head"><div><h1>Absensi</h1><p>Rekap persentase kehadiran per mata kuliah.</p></div></div>
        <div class="grid g2"><?php foreach($daftarNilai as $n): ?><div class="card"><div class="between"><div><div class="code small"><?= e($n['kode']) ?></div><strong><?= e($n['nama']) ?></strong></div><span class="badge <?= $n['absen']>=75?'badge-success':'badge-danger' ?>"><?= e($n['absen']) ?>%</span></div><div class="stat-label mt-3">Persentase Kehadiran</div><div class="progress mt-2"><span style="width:<?= min(100,(int)$n['absen']) ?>%"></span></div><div class="between mt-2"><span class="small muted">Batas minimum 75%</span><span class="small <?= $n['absen']>=75?'success':'danger' ?>"><?= $n['absen']>=75?'Memenuhi':'Tidak Memenuhi' ?></span></div></div><?php endforeach; ?></div>

        <?php elseif($halaman==='transkrip'): ?>
        <div class="section-head"><div><h1>Transkrip Nilai</h1><p>Ringkasan nilai akademik mahasiswa.</p></div><button class="btn btn-secondary" onclick="window.print()">Cetak Transkrip</button></div>
        <div class="grid g2"><div class="card"><div class="card-title">Identitas Akademik</div><div class="key-value"><div class="kv-row"><div class="kv-key">Nama</div><div class="kv-value"><?= e($mahasiswa['nama']) ?></div></div><div class="kv-row"><div class="kv-key">BP</div><div class="kv-value"><?= e($mahasiswa['bp']) ?></div></div><div class="kv-row"><div class="kv-key">Program Studi</div><div class="kv-value"><?= e($mahasiswa['jurusan']) ?></div></div><div class="kv-row"><div class="kv-key">Kode Jurusan</div><div class="kv-value"><?= e($mahasiswa['kode_jurusan']) ?></div></div></div></div><div class="card"><div class="card-title">Ringkasan</div><div class="quick-grid" style="grid-template-columns:1fr 1fr"><div class="stat"><div class="stat-label">IPK</div><div class="stat-value cyan"><?= number_format((float)$mahasiswa['ipk'],2) ?></div></div><div class="stat"><div class="stat-label">SKS Aktif</div><div class="stat-value"><?= $totalSks ?></div></div></div></div></div>
        <div class="card mt-4"><div class="table-wrap"><table class="data-table"><thead><tr><th>No</th><th>Kode</th><th>Mata Kuliah</th><th>SKS</th><th>Nilai</th></tr></thead><tbody><?php foreach($daftarNilai as $i=>$n): ?><tr><td><?= $i+1 ?></td><td class="code"><?= e($n['kode']) ?></td><td><?= e($n['nama']) ?></td><td><?= e($n['sks']) ?></td><td><span class="badge badge-success"><?= e($n['nilai']) ?></span></td></tr><?php endforeach; ?></tbody></table></div></div>

        <?php elseif($halaman==='uts' || $halaman==='uas'): ?>
        <?php $isUts = $halaman==='uts'; $basis = $isUts ? 20 : 15; $bulan = $isUts ? 'Oktober 2026' : 'Desember 2026'; ?>
        <div class="section-head"><div><h1>Jadwal <?= $isUts?'UTS':'UAS' ?></h1><p>Jadwal ujian berdasarkan mata kuliah pada KRS.</p></div><span class="badge badge-info"><?= $isUts?'20 - 23 Oktober 2026':'15 - 18 Desember 2026' ?></span></div>
        <div class="card"><div class="table-wrap"><table class="data-table"><thead><tr><th>No</th><th>Tanggal</th><th>Jam</th><th>Kode</th><th>Mata Kuliah</th><th>Dosen</th><th>Ruang</th></tr></thead><tbody><?php foreach($krs as $i=>$mk): ?><tr><td><?= $i+1 ?></td><td><?= $basis+($i%4).' '.$bulan ?></td><td><?= e($jamList[$i%count($jamList)]) ?></td><td class="code"><?= e($mk['kode_mk']) ?></td><td><?= e($mk['nama_mk']) ?></td><td><?= e($dosenList[$i%count($dosenList)]) ?></td><td><?= e($ruangList[$i%count($ruangList)]) ?></td></tr><?php endforeach; ?></tbody></table></div></div>

        <?php elseif($halaman==='ujian'): ?>
        <div class="section-head"><div><h1>Jadwal Ujian</h1><p>Ringkasan UTS, UAS, dan informasi pelaksanaan ujian.</p></div></div>
        <div class="grid g2"><div class="card"><div class="card-title">UTS</div><div class="pay-amount" style="font-size:28px">20 Okt</div><p class="muted small mt-1">Mulai 20 Oktober 2026 • Sesuai jadwal mata kuliah</p><a class="btn btn-primary mt-4" href="?halaman=uts">Lihat Jadwal UTS</a></div><div class="card"><div class="card-title">UAS</div><div class="pay-amount" style="font-size:28px">15 Des</div><p class="muted small mt-1">Mulai 15 Desember 2026 • Sesuai jadwal mata kuliah</p><a class="btn btn-primary mt-4" href="?halaman=uas">Lihat Jadwal UAS</a></div></div>
        <div class="card mt-4"><div class="card-title">Checklist Persiapan Ujian</div><div class="checklist"><label class="check-row"><input type="checkbox"><div><strong>Pastikan KRS sudah tersimpan</strong><span>Data mata kuliah harus sesuai semester aktif.</span></div></label><label class="check-row"><input type="checkbox"><div><strong>Periksa jadwal dan ruang</strong><span>Gunakan menu UTS/UAS untuk detail jadwal.</span></div></label><label class="check-row"><input type="checkbox"><div><strong>Periksa status UKT</strong><span>Pastikan kewajiban administrasi telah diselesaikan sesuai ketentuan.</span></div></label></div></div>

        <?php elseif($halaman==='ukt'): ?>
        <div class="section-head"><div><h1>UKT & Pembayaran</h1><p>Informasi tagihan UKT mahasiswa dengan simulasi pembayaran.</p></div><span class="badge <?= $uktStatus==='Sudah Bayar'?'badge-success':'badge-warning' ?>"><?= e($uktStatus) ?></span></div>
        <div class="grid g2"><div class="pay-card"><div class="pay-label">Total Tagihan UKT</div><div class="pay-amount mt-2"><?= rupiah($uktJumlah) ?></div><div class="grid g2 mt-5"><div><div class="pay-label">Mahasiswa</div><strong class="small"><?= e($mahasiswa['nama']) ?></strong></div><div><div class="pay-label">BP</div><strong class="small"><?= e($mahasiswa['bp']) ?></strong></div></div><div class="deadline-box mt-5"><strong class="small">Batas pembayaran</strong><div class="small mt-1">10 Oktober 2026 • 23:59 WIB</div></div></div>
        <div class="card"><div class="card-title">Metode Pembayaran</div><?php if($uktStatus==='Sudah Bayar'): ?><div class="notice"><strong>✓ Pembayaran selesai.</strong><div class="mt-1">Status UKT pada database sudah berubah menjadi Sudah Bayar.</div></div><?php else: ?><form action="ProsesSiakad.php" method="POST" onsubmit="return confirmPayment()"><input type="hidden" name="aksi" value="bayar_ukt"><div class="list-stack"><label class="method"><input type="radio" name="metode" value="Virtual Account" checked><div><strong>Virtual Account</strong><span>Simulasi pembayaran melalui nomor VA.</span></div></label><label class="method"><input type="radio" name="metode" value="Transfer Bank"><div><strong>Transfer Bank</strong><span>Simulasi transfer melalui rekening bank.</span></div></label><label class="method"><input type="radio" name="metode" value="QRIS"><div><strong>QRIS</strong><span>Simulasi pembayaran melalui QR.</span></div></label><label class="method"><input type="radio" name="metode" value="E-Wallet"><div><strong>E-Wallet</strong><span>Simulasi pembayaran melalui dompet digital.</span></div></label></div><button class="btn btn-primary w100 mt-4" type="submit">BAYAR SEKARANG</button></form><div class="notice mt-4">Metode pembayaran di atas hanya UI simulasi. Backend lama tetap hanya memproses <strong>aksi=bayar_ukt</strong>.</div><?php endif; ?></div></div>

        <?php elseif($halaman==='pengumuman'): ?>
        <div class="section-head"><div><h1>Pengumuman</h1><p>Informasi terbaru yang berkaitan dengan kegiatan akademik.</p></div></div>
        <div class="grid g2"><?php foreach([['Pembayaran UKT','Batas pembayaran UKT adalah 10 Oktober 2026 hingga pukul 23:59 WIB.','KEUANGAN'],['Pelaksanaan UTS','Ujian Tengah Semester dimulai 20 Oktober 2026. Periksa jadwal setiap mata kuliah.','AKADEMIK'],['Pelaksanaan UAS','Ujian Akhir Semester dimulai 15 Desember 2026.','AKADEMIK'],['Pengumuman Nilai','Nilai semester direncanakan diumumkan pada 30 Desember 2026.','NILAI'] ] as $p): ?><div class="card"><div class="between"><span class="badge badge-info"><?= e($p[2]) ?></span><span class="small muted">2026</span></div><div class="card-title mt-3" style="margin-bottom:6px"><?= e($p[0]) ?></div><p class="muted small"><?= e($p[1]) ?></p><button class="btn btn-secondary mt-4" onclick="showToast('Pengumuman','<?= e($p[0]) ?>')">Tandai dibaca</button></div><?php endforeach; ?></div>

        <?php elseif($halaman==='profil'): ?>
        <div class="section-head"><div><h1>Profil Mahasiswa</h1><p>Data identitas mahasiswa yang dibaca dari database.</p></div><button class="btn btn-secondary" onclick="window.print()">Cetak Profil</button></div>
        <div class="grid g2"><div class="card text-center"><div class="avatar" style="width:76px;height:76px;border-radius:22px;margin:0 auto 14px;font-size:18px"><?= e($avatar) ?></div><h2 style="font-size:21px"><?= e($mahasiswa['nama']) ?></h2><p class="muted small mt-1">BP <?= e($mahasiswa['bp']) ?></p><div class="hero-meta" style="justify-content:center"><span class="hero-pill"><?= e($mahasiswa['kode_jurusan']) ?></span><span class="hero-pill">Semester <?= e($mahasiswa['semester']) ?></span></div></div><div class="card"><div class="card-title">Detail Data Mahasiswa</div><div class="key-value"><div class="kv-row"><div class="kv-key">Nama</div><div class="kv-value"><?= e($mahasiswa['nama']) ?></div></div><div class="kv-row"><div class="kv-key">Nomor BP</div><div class="kv-value"><?= e($mahasiswa['bp']) ?></div></div><div class="kv-row"><div class="kv-key">Jenis Kelamin</div><div class="kv-value"><?= e($mahasiswa['jenis_kelamin']) ?></div></div><div class="kv-row"><div class="kv-key">Kode Jurusan</div><div class="kv-value"><?= e($mahasiswa['kode_jurusan']) ?></div></div><div class="kv-row"><div class="kv-key">Program Studi</div><div class="kv-value"><?= e($mahasiswa['jurusan']) ?></div></div><div class="kv-row"><div class="kv-key">Semester</div><div class="kv-value"><?= e($mahasiswa['semester']) ?></div></div><div class="kv-row"><div class="kv-key">IPK</div><div class="kv-value cyan"><?= number_format((float)$mahasiswa['ipk'],2) ?></div></div></div></div></div>

        <?php elseif($halaman==='dokumen'): ?>
        <div class="section-head"><div><h1>Dokumen Akademik</h1><p>Aksi cetak lokal untuk beberapa dokumen yang tersedia di tampilan.</p></div></div>
        <div class="grid g3"><div class="card"><div class="card-title">KHS Semester Aktif</div><p class="muted small">Cetak tampilan Kartu Hasil Studi.</p><button class="btn btn-primary mt-4" onclick="window.location='?halaman=khs';">Buka KHS</button></div><div class="card"><div class="card-title">Transkrip Nilai</div><p class="muted small">Cetak ringkasan nilai mahasiswa.</p><button class="btn btn-primary mt-4" onclick="window.location='?halaman=transkrip';">Buka Transkrip</button></div><div class="card"><div class="card-title">Kartu UKT</div><p class="muted small">Lihat informasi tagihan dan status UKT.</p><button class="btn btn-primary mt-4" onclick="window.location='?halaman=ukt';">Buka UKT</button></div></div>

        <?php elseif($halaman==='pengaturan'): ?>
        <div class="section-head"><div><h1>Pengaturan</h1><p>Preferensi tampilan lokal pada browser.</p></div></div>
        <div class="grid g2"><div class="card"><div class="card-title">Tampilan</div><div class="list-stack"><label class="method"><input type="checkbox" id="compactMode" onchange="toggleCompact(this)"><div><strong>Mode ringkas</strong><span>Mengurangi jarak beberapa komponen pada halaman.</span></div></label><label class="method"><input type="checkbox" id="soundMode"><div><strong>Notifikasi suara</strong><span>Preferensi lokal untuk interaksi.</span></div></label></div></div><div class="card"><div class="card-title">Sesi Akademik</div><div class="notice">Sesi login tetap dikelola oleh <strong>ProsesSiakad.php</strong>. Halaman ini tidak mengubah autentikasi.</div></div></div>

        <?php elseif($halaman==='bantuan'): ?>
        <div class="section-head"><div><h1>Pusat Bantuan</h1><p>Panduan singkat menggunakan SIAKAD.</p></div></div>
        <div class="grid g2"><?php foreach([['Login','Masukkan Nomor BP dan password pada halaman masuk.'],['KRS','Centang mata kuliah, kemudian Simpan KRS. Proses tetap dikirim ke backend lama.'],['UKT','Pilih metode simulasi kemudian tekan Bayar Sekarang. Backend akan memperbarui status UKT.'],['Logout','Gunakan tombol Logout untuk mengakhiri sesi login.'] ] as $q): ?><div class="card"><div class="card-title"><?= e($q[0]) ?></div><p class="muted small"><?= e($q[1]) ?></p></div><?php endforeach; ?></div>
        <?php endif; ?>
    </section>
</main>
</div>

<div class="modal-layer" id="commandLayer" onclick="hideCommand(event)"><div class="modal" onclick="event.stopPropagation()"><div class="modal-head"><h3>Pencarian Menu SIAKAD</h3><button class="close" onclick="toggleCommand()">×</button></div><div class="field"><input id="commandInput" type="text" placeholder="Cari dashboard, KRS, UKT, nilai..." oninput="filterCommands()"></div><div class="list-stack" id="commandList"><?php foreach([['Dashboard','dashboard'],['Kalender Akademik','kalender'],['Kurikulum','kurikulum'],['Jadwal Kuliah','jadwal'],['KRS','krs'],['KHS / Nilai','khs'],['Absensi','absensi'],['Transkrip Nilai','transkrip'],['Jadwal UTS','uts'],['Jadwal UAS','uas'],['Jadwal Ujian','ujian'],['UKT / Pembayaran','ukt'],['Pengumuman','pengumuman'],['Dokumen Akademik','dokumen'],['Profil','profil'],['Pengaturan','pengaturan'],['Pusat Bantuan','bantuan']] as $c): ?><a class="list-item command-item" data-command="<?= strtolower(e($c[0])) ?>" href="?halaman=<?= e($c[1]) ?>"><div class="title"><?= e($c[0]) ?></div><div class="desc">Buka halaman <?= e($c[0]) ?></div></a><?php endforeach; ?></div></div></div>
<div class="toast" id="toast"><div class="toast-title" id="toastTitle">SIAKAD</div><div class="toast-text" id="toastText"></div></div>

<?php endif; ?>
<script>
function togglePassword(){
    const input=document.getElementById('password');
    const icon=document.getElementById('passwordToggle');
    if(!input) return;
    if(input.type==='password'){input.type='text';icon.textContent='◉';}else{input.type='password';icon.textContent='◌';}
}
function openSidebar(){document.body.classList.add('menu-open');document.getElementById('mobileOverlay')?.classList.add('show');}
function closeSidebar(){document.body.classList.remove('menu-open');document.getElementById('mobileOverlay')?.classList.remove('show');}
function showToast(title,text){
    const toast=document.getElementById('toast'); if(!toast) return;
    document.getElementById('toastTitle').textContent=title;
    document.getElementById('toastText').textContent=text;
    toast.classList.add('show');
    clearTimeout(window.toastTimer); window.toastTimer=setTimeout(()=>toast.classList.remove('show'),3200);
}
function toggleCommand(){
    const layer=document.getElementById('commandLayer'); if(!layer) return;
    layer.classList.toggle('show');
    if(layer.classList.contains('show')){setTimeout(()=>document.getElementById('commandInput')?.focus(),80)}
}
function hideCommand(e){if(e.target===document.getElementById('commandLayer')) toggleCommand();}
function filterCommands(){
    const q=(document.getElementById('commandInput')?.value||'').toLowerCase();
    document.querySelectorAll('.command-item').forEach(el=>el.style.display=el.dataset.command.includes(q)?'block':'none');
}
function confirmPayment(){
    const chosen=document.querySelector('input[name="metode"]:checked');
    const metode=chosen?chosen.value:'metode pembayaran';
    return confirm('Konfirmasi pembayaran UKT?\n\nMetode: '+metode+'\n\nStatus akan diperbarui oleh proses lama ProsesSiakad.php.');
}
function updateKrsSummary(){
    const checks=[...document.querySelectorAll('#krsList input[name="mk[]"]:checked')];
    let sks=0; checks.forEach(c=>{const parts=c.value.split('|');sks+=parseInt(parts[2]||'0',10)||0;});
    const count=document.getElementById('selectedCount'); const sum=document.getElementById('selectedSks');
    if(count) count.textContent=checks.length+' mata kuliah'; if(sum) sum.textContent=sks+' SKS';
}
function validateKrs(){
    updateKrsSummary();
    const checks=document.querySelectorAll('#krsList input[name="mk[]"]:checked');
    if(checks.length===0) return confirm('Tidak ada mata kuliah dipilih. KRS akan dikosongkan. Lanjutkan?');
    return true;
}
function toggleCompact(el){document.body.classList.toggle('compact-mode',el.checked);}
updateKrsSummary();
document.addEventListener('keydown',e=>{if((e.ctrlKey||e.metaKey)&&e.key.toLowerCase()==='k'){e.preventDefault();toggleCommand();}});

function scrollToTop(){window.scrollTo({top:0,behavior:'smooth'});}
function copyText(text){if(navigator.clipboard){navigator.clipboard.writeText(text).then(()=>showToast('Disalin','Data berhasil disalin ke clipboard.'));}}
window.addEventListener('online',()=>showToast('Koneksi','Browser kembali online.'));
window.addEventListener('offline',()=>showToast('Koneksi','Browser sedang offline.'));
</script>
</body>
</html>
