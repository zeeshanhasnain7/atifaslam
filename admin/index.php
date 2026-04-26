<?php
ob_start();
session_start();
error_reporting(0);
ini_set('display_errors', 0);

/**
 * ZeeshanEvents - Secure Terminal v16.0
 * ULTRA-MODERN LANDSCAPE TICKET & DATA INTEGRITY.
 */

$root = dirname(__DIR__);
require $root . '/phpmailer/src/Exception.php';
require $root . '/phpmailer/src/PHPMailer.php';
require $root . '/phpmailer/src/SMTP.php';
require $root . '/fpdf/fpdf.php';

class GoogleAuthenticator
{
    public function getCode($secret, $timeSlice = null)
    {
        if ($timeSlice === null)
            $timeSlice = floor(time() / 30);
        $secretkey = $this->_base32Decode($secret);
        $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N', $timeSlice);
        $hm = hash_hmac('SHA1', $time, $secretkey, true);
        $offset = ord(substr($hm, -1)) & 0x0F;
        $hashpart = substr($hm, $offset, 4);
        $value = unpack('N', $hashpart);
        $value = $value[1];
        $value = $value & 0x7FFFFFFF;
        $modulo = pow(10, 6);
        return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
    }
    public function verifyCode($secret, $code, $discrepancy = 2, $currentTimeSlice = null)
    {
        if ($currentTimeSlice === null)
            $currentTimeSlice = floor(time() / 30);
        for ($i = -$discrepancy; $i <= $discrepancy; ++$i) {
            if ($this->getCode($secret, $currentTimeSlice + $i) == $code)
                return true;
        }
        return false;
    }
    private function _base32Decode($secret)
    {
        if (empty($secret))
            return '';
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = "";
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = "";
            for ($j = 0; $j < 8; ++$j)
                $x .= str_pad(decbin($base32charsFlipped[$secret[$i + $j]] ?? 0), 5, '0', STR_PAD_LEFT);
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); ++$z)
                $binaryString .= chr(bindec($eightBits[$z]));
        }
        return $binaryString;
    }
}

function generateTicketPDF($order)
{
    // Modern Landscape Pass (210mm x 95mm)
    $pdf = new FPDF('L', 'mm', array(210, 95));
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(false);

    // Main Background (Deep Cinematic Black)
    $pdf->SetFillColor(0, 0, 0);
    $pdf->Rect(0, 0, 210, 95, 'F');

    // Cyberpunk Gradient Accent (Left Edge)
    $pdf->SetFillColor(16, 185, 129); // Emerald
    $pdf->Rect(0, 0, 4, 95, 'F');
    $pdf->SetFillColor(255, 20, 147); // Deep Pink
    $pdf->Rect(4, 0, 2, 95, 'F');

    // Glow Effect (Simulation)
    $pdf->SetDrawColor(20, 20, 20);
    for ($i = 0; $i < 10; $i++) {
        $pdf->Rect(6 + $i, 0, 1, 95, 'F');
    }

    // Header Section
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text(25, 15, "ZEESHANEVENTS PRESENTS");

    // "LIVE IN CONCERT" Neon Pink
    $pdf->SetTextColor(255, 20, 147);
    $pdf->SetFont('Arial', 'B', 38);
    $pdf->Text(25, 32, "LIVE IN CONCERT");

    // ATIF ASLAM (Massive White Typography)
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 54);
    $pdf->Text(25, 55, "ATIF ASLAM");

    // Venue & Date (Corrected)
    $pdf->SetTextColor(16, 185, 129);
    $pdf->SetFont('Arial', 'B', 14);
    $pdf->Text(25, 70, "8TH MAY 2026 | 08:00 PM");

    $pdf->SetTextColor(150, 150, 150);
    $pdf->SetFont('Arial', '', 11);
    $pdf->Text(25, 78, "MOIN KHAN CRICKET ACADEMY, KARACHI");

    // Right Section: DETACHABLE STUB
    $pdf->SetFillColor(15, 15, 15);
    $pdf->Rect(160, 0, 50, 95, 'F');
    $pdf->SetDrawColor(255, 255, 255);
    $pdf->SetLineWidth(0.1);
    $pdf->Line(160, 0, 160, 95); // Perforation Line

    // Stub Content
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text(165, 15, "OFFICIAL PASS");

    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Text(165, 25, "CUSTOMER");
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Text(165, 30, strtoupper(substr($order['name'], 0, 20)));

    $pdf->SetTextColor(100, 100, 100);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->Text(165, 40, "ORDER REFERENCE");
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->Text(165, 46, "#" . str_pad($order['id'], 5, '0', STR_PAD_LEFT));

    // Barcode Simulation
    $pdf->SetFillColor(255, 255, 255);
    $startX = 165;
    $startY = 60;
    for ($i = 0; $i < 25; $i++) {
        $w = rand(1, 3) / 2;
        $pdf->Rect($startX, $startY, $w, 18, 'F');
        $startX += $w + 0.5;
    }

    $pdf->SetTextColor(50, 50, 50);
    $pdf->SetFont('Arial', 'B', 6);
    $pdf->Text(165, 88, "SECURED BY ZEESHANEVENTS");

    $tempName = tempnam(sys_get_temp_dir(), 'pass_v16');
    $pdf->Output('F', $tempName);
    return $tempName;
}

$ga = new GoogleAuthenticator();
$secret = 'YOUR_2FA_SECRET_HERE';

// Auth Logic
if (isset($_GET['handshake'])) {
    header('Content-Type: application/json');
    if (($_POST['email'] ?? '') === 'ADMIN_EMAIL_HERE' && ($_POST['password'] ?? '') === 'ADMIN_PASSWORD_HERE') {
        $_SESSION['handshake_pending'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid Login']);
    }
    exit;
}
if (isset($_GET['verify'])) {
    header('Content-Type: application/json');
    if ($ga->verifyCode($secret, $_POST['otp_code'] ?? '', 4)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['2fa_verified'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid Code']);
    }
    exit;
}
$isLoggedIn = isset($_SESSION['admin_logged_in']) && isset($_SESSION['2fa_verified']);

// Triage Logic
if ($isLoggedIn && isset($_GET['action_update'])) {
    header('Content-Type: application/json');
    try {
        $db = new SQLite3($root . '/database.sqlite');
        $id = (int) $_POST['order_id'];
        $status = $_POST['status'];
        $msg = $_POST['admin_message'] ?? '';
        $order = $db->querySingle("SELECT * FROM orders WHERE id = $id", true);
        if (!$order)
            throw new Exception("Entry Lost");

        $currentIbanRaw = $order['iban'];
        if (strpos($currentIbanRaw, ' || ') !== false) {
            $parts = explode(' || ', $currentIbanRaw);
            $originalIban = trim(end($parts));
        } else {
            $originalIban = trim($currentIbanRaw);
        }

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'mail.spacemail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'YOUR_SMTP_EMAIL_HERE';
        $mail->Password = 'YOUR_SMTP_PASSWORD_HERE';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;
        $mail->setFrom('YOUR_SMTP_EMAIL_HERE', 'ZeeshanEvents Official');
        $mail->addAddress($order['email']);
        $mail->isHTML(true);
        $mail->addEmbeddedImage('img/banner.jpg', 'banner');

        $primaryColor = $status === 'approved' ? '#10b981' : '#ef4444';
        $statusText = $status === 'approved' ? 'PAYMENT VERIFIED' : 'SORRY, WE COULDN\'T VERIFY YOUR PAYMENT';

        $mail->Subject = ($status === 'approved' ? 'Confirmed: ' : 'Attention: ') . 'Atif Aslam Live Booking #' . $id;
        $mail->Body = "<div style='background:#000; padding:40px; font-family:sans-serif;'><div style='max-width:600px; margin:0 auto; background:#0a0a0a; border:1px solid #222; border-radius:30px; overflow:hidden;'><img src='cid:banner' style='width:100%; display:block;'><div style='padding:45px;'><h1 style='color:$primaryColor; text-align:center; font-weight:900;'>$statusText</h1><div style='background:#111; padding:25px; border-radius:15px; margin:30px 0; border-left:5px solid $primaryColor;'><p style='color:#666; text-transform:uppercase; font-size:10px; letter-spacing:2px; margin-bottom:10px;'>Admin Message</p><p style='color:#fff; margin:0; font-size:16px;'>$msg</p></div></div></div></div>";

        if ($status === 'approved') {
            $tPath = generateTicketPDF($order);
            $mail->addAttachment($tPath, 'Official_Concert_Pass.pdf');
        }
        $storageVal = strtoupper($status) . ": " . SQLite3::escapeString($msg) . " || " . SQLite3::escapeString($originalIban);
        $db->exec("UPDATE orders SET iban = '$storageVal' WHERE id = $id");
        $mail->send();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($isLoggedIn && isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    try {
        $db = new SQLite3($root . '/database.sqlite');
        $res = $db->query("SELECT * FROM orders ORDER BY id DESC");
        $data = [];
        while ($r = $res->fetchArray(SQLITE3_ASSOC))
            $data[] = $r;
        echo json_encode($data);
    } catch (Exception $e) {
    }
    exit;
}

if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Terminal | ZeeshanEvents</title>
    <link rel="icon" type="image/png" href="../img/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap');

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #000;
            color: #cbd5e1;
        }

        .otp-box {
            width: 50px;
            height: 60px;
            background: #111;
            border: 2px solid rgba(255, 255, 255, 0.05);
            border-radius: 12px;
            font-size: 24px;
            font-weight: 900;
            color: #10b981;
            text-align: center;
            outline: none;
        }

        .custom-slider {
            -webkit-appearance: none;
            width: 100%;
            height: 6px;
            background: #111;
            border-radius: 5px;
            outline: none;
            border: 1px solid #222;
        }

        .custom-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 22px;
            height: 22px;
            background: #10b981;
            border-radius: 50%;
            border: 4px solid #000;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
            cursor: pointer;
        }

        .filter-btn.active {
            background-color: #10b981;
            color: black;
            box-shadow: 0 0 20px rgba(16, 185, 129, 0.4);
        }
    </style>
</head>

<body>

    <?php if (!$isLoggedIn): ?>
        <div class="flex min-h-screen">
            <div class="hidden lg:block w-1/2 relative border-r border-white/5">
                <img src="img/banner.jpg" class="absolute inset-0 w-full h-full object-cover opacity-20 grayscale"
                    alt="Atif">
                <div class="absolute bottom-20 left-20">
                    <h1
                        class="text-7xl font-black text-white uppercase tracking-[0.05em] word-spacing-[0.2em] leading-[0.9]">
                        ZEESHAN<br><span class="text-[#10b981]">EVENTS</span></h1>
                </div>
            </div>
            <div class="w-full lg:w-1/2 flex items-center justify-center p-10">
                <div id="auth-container" class="w-full max-sm space-y-12">
                    <div id="step-1" class="space-y-10">
                        <h2 class="text-5xl font-black text-white uppercase leading-none tracking-wide">Admin<br><span
                                class="text-[#10b981]">Login</span></h2>
                        <div class="space-y-4">
                            <input type="email" id="l-email" placeholder="Email"
                                class="w-full bg-[#111] border-2 border-white/5 rounded-2xl px-8 py-5 outline-none focus:border-[#10b981] text-white">
                            <input type="password" id="l-pass" placeholder="Password"
                                class="w-full bg-[#111] border-2 border-white/5 rounded-2xl px-8 py-5 outline-none focus:border-[#10b981] text-white">
                        </div>
                        <button onclick="handleHandshake()"
                            class="w-full bg-white text-black py-6 rounded-[2.5rem] font-black text-xl hover:bg-[#10b981]">Start
                            Session</button>
                    </div>
                    <div id="step-2" class="hidden space-y-10">
                        <h2 class="text-5xl font-black text-white uppercase leading-none tracking-wide">Security<br><span
                                class="text-[#10b981]">Verify</span></h2>
                        <div class="flex justify-between gap-2"><?php for ($i = 1; $i <= 6; $i++): ?><input type="text"
                                    maxlength="1" class="otp-box" oninput="moveNext(this, <?php echo $i; ?>)"
                                    onkeydown="moveBack(event, <?php echo $i; ?>)"><?php endfor; ?></div>
                        <button onclick="handleVerify()"
                            class="w-full bg-[#10b981] text-black py-6 rounded-[2.5rem] font-black text-xl">Confirm
                            Identity</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            function moveNext(el, i) { if (el.value.length === 1 && i < 6) document.querySelectorAll('.otp-box')[i].focus(); if (i === 6) handleVerify(); }
            function moveBack(e, i) { if (e.key === "Backspace" && e.target.value === "" && i > 1) document.querySelectorAll('.otp-box')[i - 2].focus(); }
            async function handleHandshake() {
                const fd = new FormData(); fd.append('email', document.getElementById('l-email').value); fd.append('password', document.getElementById('l-pass').value);
                const res = await fetch('?handshake=1', { method: 'POST', body: fd }); const data = await res.json();
                if (data.success) { document.getElementById('step-1').classList.add('hidden'); document.getElementById('step-2').classList.remove('hidden'); document.querySelectorAll('.otp-box')[0].focus(); } else alert("Login Failed");
            }
            async function handleVerify() {
                let otp = ""; document.querySelectorAll('.otp-box').forEach(i => otp += i.value);
                const fd = new FormData(); fd.append('otp_code', otp);
                const res = await fetch('?verify=1', { method: 'POST', body: fd }); const data = await res.json();
                if (data.success) window.location.reload(); else alert("OTP Error");
            }
        </script>

    <?php else: ?>
        <nav class="bg-black border-b border-white/5 sticky top-0 z-50">
            <div class="max-w-[1500px] mx-auto px-8 py-5 flex justify-between items-center">
                <h1 class="text-white font-black uppercase text-xl tracking-[0.2em] word-spacing-[0.5em]">ZeeshanEvents
                    Terminal</h1><a href="?logout=1"
                    class="text-slate-500 hover:text-white px-6 py-2 rounded-xl text-xs font-bold uppercase bg-white/5 transition">Sign
                    Out</a>
            </div>
        </nav>
        <main class="max-w-[1500px] mx-auto px-8 py-10 space-y-12">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-[#0a0a0a] border border-white/5 p-8 rounded-[2rem]">
                    <p class="text-slate-500 text-[10px] font-black uppercase mb-2 tracking-[0.15em]">Revenue Flow</p>
                    <h4 id="s-rev" class="text-4xl font-black text-white">Rs 0</h4>
                </div>
                <div class="bg-[#0a0a0a] border border-white/5 p-8 rounded-[2rem]">
                    <p class="text-slate-500 text-[10px] font-black uppercase mb-2 tracking-[0.15em]">Master Count</p>
                    <h4 id="s-ord" class="text-4xl font-black text-white">0</h4>
                </div>
                <div class="bg-[#0a0a0a] border border-white/5 p-8 rounded-[2rem] border-[#10b981]/20">
                    <p class="text-slate-500 text-[10px] font-black uppercase mb-2 tracking-[0.15em]">Authenticated</p>
                    <h4 id="s-ver" class="text-4xl font-black text-[#10b981]">0</h4>
                </div>
            </div>
            <div
                class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center bg-[#0a0a0a] border border-white/5 p-10 rounded-[3rem] shadow-2xl">
                <input type="text" id="u-search" oninput="renderTable()" placeholder="Search Record Identity..."
                    class="w-full bg-black border border-white/10 rounded-2xl px-12 py-5 outline-none focus:border-[#10b981] text-white font-bold tracking-wide">
                <div class="space-y-4 px-4">
                    <div class="flex justify-between text-[10px] font-black uppercase text-slate-500 tracking-widest">
                        <span>Price Threshold</span><span class="text-[#10b981] text-lg">Rs <span
                                id="p-disp">0</span>+</span>
                    </div><input type="range" id="p-slider" min="0" max="100000" step="500" value="0"
                        oninput="renderTable()" class="custom-slider">
                </div>
            </div>
            <div class="flex flex-wrap gap-4">
                <button onclick="setFilter('spot', '')"
                    class="filter-btn active px-10 py-4 rounded-full text-[10px] font-black uppercase tracking-[0.15em] transition">All
                    Categories</button>
                    <?php foreach (['General Enclosure', 'Pink Enclosure', 'Silver Enclosure', 'Gold Enclosure', 'Royal Prime', 'Royal Signature'] as $s): ?>
                    <button onclick="setFilter('spot', '<?php echo $s; ?>')"
                        class="filter-btn bg-white/5 text-slate-400 px-8 py-4 rounded-full text-[10px] font-black uppercase tracking-[0.1em] transition"><?php echo $s; ?></button>
                    <?php endforeach; ?>
            </div>
            <div class="bg-[#0a0a0a] border border-white/5 rounded-[3rem] overflow-hidden shadow-2xl">
                <table class="w-full text-left">
                    <thead>
                        <tr
                            class="bg-white/[0.02] border-b border-white/5 text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">
                            <th class="px-10 py-8">ID</th>
                            <th class="px-10 py-8">Profile Details</th>
                            <th class="px-10 py-8">Reserved Spots & IBAN</th>
                            <th class="px-10 py-8">Valuation & Status</th>
                            <th class="px-10 py-8 text-center">Process</th>
                        </tr>
                    </thead>
                    <tbody id="t-body" class="divide-y divide-white/5"></tbody>
                </table>
            </div>
        </main>
        <div id="m-action"
            class="fixed inset-0 z-[100] bg-black/95 backdrop-blur-md hidden flex items-center justify-center p-6">
            <div
                class="bg-[#050505] border border-white/10 w-full max-w-2xl rounded-[3rem] overflow-hidden shadow-2xl flex flex-col max-h-[90vh]">
                <div class="p-10 border-b border-white/5 flex justify-between items-center bg-[#0a0a0a]">
                    <h3 class="text-3xl font-black text-white uppercase tracking-[0.1em]">Order Triage</h3><button
                        onclick="closeModal()" class="text-slate-500 hover:text-white text-3xl">✕</button>
                </div>
                <div id="m-content" class="p-10 overflow-y-auto space-y-10 flex-grow"></div>
                <div class="p-10 border-t border-white/5 bg-[#0a0a0a] space-y-8">
                    <textarea id="m-msg" placeholder="Write admin message..."
                        class="w-full bg-black border border-white/10 rounded-2xl p-6 text-white text-sm h-32 outline-none focus:border-[#10b981] font-bold"></textarea>
                    <div class="flex gap-6"><button onclick="submitUpdate('rejected')"
                            class="flex-1 bg-red-500/10 text-red-500 py-6 rounded-2xl font-black uppercase text-xs hover:bg-red-500 tracking-widest transition">Reject
                            Order</button><button onclick="submitUpdate('approved')"
                            class="flex-1 bg-[#10b981] text-black py-6 rounded-2xl font-black uppercase text-xs hover:bg-emerald-400 tracking-widest transition">Approve
                            & Dispatch</button></div>
                </div>
            </div>
        </div>
        <script>
            let allOrders = []; let filters = { spot: '', search: '', price: 0 }; let activeOrder = null;
            async function fetchOrders() { const res = await fetch('?ajax=1&v=' + Date.now()); allOrders = await res.json(); updateStats(); renderTable(); }
            function updateStats() {
                const revenue = allOrders.reduce((sum, o) => sum + o.total, 0); const verified = allOrders.filter(o => o.iban.includes('APPROVED')).length;
                document.getElementById('s-rev').textContent = 'Rs ' + revenue.toLocaleString(); document.getElementById('s-ord').textContent = allOrders.length; document.getElementById('s-ver').textContent = verified;
            }
            function setFilter(key, val) {
                filters[key] = val; if (key === 'spot') document.querySelectorAll('.filter-btn').forEach(btn => { btn.classList.remove('active'); if (btn.textContent.toLowerCase().includes(val.toLowerCase() || 'all')) btn.classList.add('active'); });
                renderTable();
            }
            function renderTable() {
                const tbody = document.getElementById('t-body');
                filters.search = document.getElementById('u-search').value.toLowerCase();
                filters.price = parseInt(document.getElementById('p-slider').value);
                document.getElementById('p-disp').textContent = filters.price.toLocaleString();
                const filtered = allOrders.filter(o => { const s = `${o.name} ${o.iban} ${o.phone} ${o.email}`.toLowerCase(); const mS = !filters.spot || (o.tickets && o.tickets.toLowerCase().includes(filters.spot.toLowerCase())); return o.total >= filters.price && mS && (!filters.search || s.includes(filters.search)); });
                tbody.innerHTML = filtered.map(o => {
                    let sCls = 'text-white'; let sTxt = 'PENDING VERIFICATION'; let iDisp = o.iban;
                    if (o.iban.includes(' || ')) { const p = o.iban.split(' || '); sTxt = p[0]; iDisp = p[1]; if (sTxt.includes('APPROVED')) sCls = 'text-emerald-500'; else sCls = 'text-red-500'; }
                    let tix = []; try { tix = JSON.parse(o.tickets); } catch (e) { }
                    return `<tr class="hover:bg-white/[0.01] transition">
                    <td class="px-10 py-12 text-xs font-black text-slate-700">#${o.id.toString().padStart(5, '0')}</td>
                    <td class="px-10 py-12"><div class="flex flex-col"><span class="text-white font-black text-2xl uppercase mb-1 tracking-wide leading-none">${o.name}</span><span class="text-lg text-[#10b981] font-black">${o.phone}</span><span class="text-xs text-slate-500 font-bold uppercase mt-2 tracking-widest">${o.email}</span></div></td>
                    <td class="px-10 py-12">
                        <div class="space-y-3 mb-6">${tix.map(t => `<div class="flex items-center gap-3"><div class="w-2 h-2 rounded-full bg-[#10b981]"></div><span class="text-sm font-black text-slate-200 uppercase">${t.name} (x${t.qty})</span></div>`).join('')}</div>
                        <div class="bg-white/5 p-4 rounded-2xl border border-white/5"><p class="text-[9px] font-black uppercase text-slate-500 mb-2 tracking-widest">IBAN NUMBER</p><p class="text-[13px] font-mono text-white font-black break-all tracking-wider">${iDisp}</p></div>
                    </td>
                    <td class="px-10 py-12">
                        <div class="flex flex-col"><span class="text-white font-black text-3xl tracking-wide leading-none mb-4">Rs ${o.total.toLocaleString()}</span>
                        <div class="p-4 bg-black/40 border border-white/10 rounded-2xl"><p class="text-[9px] font-black uppercase text-slate-600 mb-1 tracking-widest">TRANSACTION STATUS</p><p class="text-[11px] font-black ${sCls} uppercase leading-tight tracking-wide">${sTxt}</p></div></div>
                    </td>
                    <td class="px-10 py-12 text-center"><button onclick="openModal(${o.id})" class="px-8 py-5 bg-white/5 hover:bg-[#10b981] hover:text-black rounded-2xl transition text-xs font-black uppercase tracking-[0.15em] shadow-2xl">Process</button></td>
                </tr>`;
                }).join('');
            }
            function openModal(id) {
                activeOrder = allOrders.find(o => o.id == id); const content = document.getElementById('m-content');
                let tickets = []; try { tickets = JSON.parse(activeOrder.tickets); } catch (e) { }
                let ibanVal = activeOrder.iban.includes(' || ') ? activeOrder.iban.split(' || ')[1] : activeOrder.iban;
                content.innerHTML = `<div class="grid grid-cols-2 gap-10"><div><p class="text-[10px] font-black uppercase text-slate-600 tracking-widest">Customer</p><p class="text-3xl text-white font-black uppercase tracking-wide">${activeOrder.name}</p></div><div><p class="text-[10px] font-black uppercase text-slate-600 tracking-widest">Total Bill</p><p class="text-3xl text-[#10b981] font-black tracking-wide">Rs ${activeOrder.total.toLocaleString()}</p></div></div><div class="space-y-4"><p class="text-[10px] font-black uppercase text-slate-600 tracking-widest">Access Control</p><div class="grid grid-cols-1 md:grid-cols-2 gap-4">${tickets.map(t => `<div class="bg-white/5 p-5 rounded-2xl flex justify-between border border-white/5"><span class="text-xs text-white font-black uppercase tracking-tight">${t.name}</span><span class="text-xs text-[#10b981] font-black">x${t.qty}</span></div>`).join('')}</div></div><div class="bg-[#111] p-8 rounded-[2rem] border border-white/5 shadow-inner"><p class="text-[10px] font-black uppercase text-slate-500 mb-3 tracking-widest">IBAN / Reference</p><p class="text-white font-mono text-xl font-black break-all tracking-wider font-bold">${ibanVal}</p></div>`;
                document.getElementById('m-action').classList.remove('hidden');
            }
            function closeModal() { document.getElementById('m-action').classList.add('hidden'); }
            async function submitUpdate(status) {
                const btn = event.target; const original = btn.textContent; btn.disabled = true; btn.textContent = 'Syncing...';
                const fd = new FormData(); fd.append('order_id', activeOrder.id); fd.append('status', status); fd.append('admin_message', document.getElementById('m-msg').value);
                const res = await fetch('?action_update=1', { method: 'POST', body: fd }); const data = await res.json();
                if (data.success) { closeModal(); fetchOrders(); } else alert("Error");
                btn.disabled = false; btn.textContent = original;
            }
            fetchOrders();
        </script>
    </body>

    </html>
<?php endif; ?>