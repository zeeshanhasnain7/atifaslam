<?php
session_start();
/**
 * Atif Aslam Event - Checkout Page (Full Page PHP/SQLite/SMTP)
 */

$isSuccess = false;
$errorMsg = "";

// 0. AJAX OTP Handlers
if (isset($_GET['send_otp'])) {
    header('Content-Type: application/json');
    $email = $_POST['email'] ?? '';
    $otp = rand(100000, 999999);
    $_SESSION['checkout_otp'] = $otp;

    require 'phpmailer/src/Exception.php';
    require 'phpmailer/src/PHPMailer.php';
    require 'phpmailer/src/SMTP.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'YOUR_SMTP_HOST_HERE';
        $mail->SMTPAuth = true;
        $mail->Username = 'YOUR_SMTP_EMAIL_HERE';
        $mail->Password = 'YOUR_SMTP_PASSWORD_HERE';
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('YOUR_SMTP_EMAIL_HERE', 'ZeeshanEvents Security');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->addEmbeddedImage('img/banner.jpg', 'banner');
        $mail->Subject = $otp . ' is your ZeeshanEvents Verification Code';

        $mail->Body = "
            <div style='background-color: #000; padding: 40px; font-family: sans-serif;'>
                <div style='max-width: 500px; margin: 0 auto; background-color: #0a0a0a; border: 1px solid #10b981; border-radius: 30px; overflow: hidden; text-align: center;'>
                    <img src='cid:banner' style='width: 100%; display: block;'>
                    <div style='padding: 40px;'>
                        <div style='background-color: #10b981; color: #000; font-weight: 900; display: inline-block; padding: 5px 15px; font-size: 12px; border-radius: 4px; margin-bottom: 20px;'>SECURITY VERIFICATION</div>
                        <h2 style='color: #fff; margin: 0; font-size: 20px;'>Verify Your Booking</h2>
                        <p style='color: #666; font-size: 14px; margin-top: 10px;'>Enter the code below to complete your checkout.</p>
                        <div style='background: #111; margin: 30px 0; padding: 30px; border-radius: 20px; border: 1px solid #222;'>
                            <span style='color: #10b981; font-size: 54px; font-weight: 900; letter-spacing: 10px;'>$otp</span>
                        </div>
                        <p style='color: #444; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;'>Code expires in 10 minutes</p>
                    </div>
                </div>
            </div>";
        $mail->send();
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $mail->ErrorInfo]);
    }
    exit;
}

if (isset($_GET['verify_otp'])) {
    header('Content-Type: application/json');
    $entered = $_POST['otp_code'] ?? '';
    if ($entered == ($_SESSION['checkout_otp'] ?? '')) {
        $_SESSION['otp_verified'] = true;
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit;
}

// 1. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new SQLite3('database.sqlite');
        $db->exec("CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT,
            email TEXT,
            phone TEXT,
            iban TEXT,
            tickets TEXT,
            total INTEGER,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");

        $name = $_POST['name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $iban = $_POST['iban'] ?? '';
        $tickets_json = $_POST['tickets_json'] ?? '[]';
        $total = (int) ($_POST['total_val'] ?? 0);
        $tickets_array = json_decode($tickets_json, true);

        $stmt = $db->prepare("INSERT INTO orders (name, email, phone, iban, tickets, total) VALUES (:name, :email, :phone, :iban, :tickets, :total)");
        $stmt->bindValue(':name', $name);
        $stmt->bindValue(':email', $email);
        $stmt->bindValue(':phone', $phone);
        $stmt->bindValue(':iban', $iban);
        $stmt->bindValue(':tickets', $tickets_json);
        $stmt->bindValue(':total', $total);
        $stmt->execute();

        // 2. PHPMailer Integration
        require 'phpmailer/src/Exception.php';
        require 'phpmailer/src/PHPMailer.php';
        require 'phpmailer/src/SMTP.php';

        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'YOUR_SMTP_HOST_HERE';
            $mail->SMTPAuth = true;
            $mail->Username = 'YOUR_SMTP_EMAIL_HERE';
            $mail->Password = 'YOUR_SMTP_PASSWORD_HERE';
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // SSL
            $mail->Port = 465;

            // Common Ticket Table Rows
            $ticketRowsHtml = "";
            foreach ($tickets_array as $t) {
                $rowTotal = $t['price'] * $t['qty'];
                $ticketRowsHtml .= "
                    <tr style='border-bottom: 1px solid #1f2937;'>
                        <td style='padding: 15px 0; color: #ffffff; font-weight: 600; font-size: 14px;'>{$t['name']}</td>
                        <td style='padding: 15px 0; color: #9ca3af; font-size: 14px; text-align: center;'>x{$t['qty']}</td>
                        <td style='padding: 15px 0; color: #10b981; font-weight: 700; font-size: 15px; text-align: right;'>Rs " . number_format($rowTotal) . "</td>
                    </tr>";
            }

            // --- ADMIN EMAIL ---
            $mail->setFrom('YOUR_SMTP_EMAIL_HERE', 'ZeeshanEvents Live Alerts');
            $mail->addAddress('YOUR_ADMIN_EMAIL_HERE', 'Admin');
            $mail->isHTML(true);
            $mail->Subject = 'URGENT: New Concert Request - ' . $name;

            $mail->Body = "
                <div style='background-color: #000000; padding: 40px 20px; font-family: \"Inter\", Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; background-color: #0a0a0a; border: 1px solid #10b981; border-radius: 24px; overflow: hidden; box-shadow: 0 20px 50px rgba(16, 185, 129, 0.1);'>
                        <div style='padding: 40px; border-bottom: 1px solid #1f2937;'>
                            <div style='background-color: #10b981; color: #000000; font-weight: 900; display: inline-block; padding: 5px 15px; font-size: 14px; border-radius: 4px; margin-bottom: 20px;'>ZeeshanEvents</div>
                            <h1 style='color: #ffffff; font-size: 22px; margin: 0; text-transform: uppercase; letter-spacing: 2px;'>New Request Arrived</h1>
                            <p style='color: #10b981; font-weight: 700; margin-top: 5px; font-size: 14px;'>Atif Aslam Concert - Live Verification Required</p>
                        </div>
                        <div style='padding: 40px;'>
                            <div style='background-color: #111827; border-radius: 16px; padding: 25px; margin-bottom: 30px; border-left: 4px solid #10b981;'>
                                <h3 style='color: #9ca3af; text-transform: uppercase; font-size: 11px; letter-spacing: 1.5px; margin-bottom: 15px;'>Customer Data</h3>
                                <p style='color: #ffffff; margin: 5px 0; font-size: 16px;'><strong>Name:</strong> $name</p>
                                <p style='color: #ffffff; margin: 5px 0; font-size: 15px;'><strong>Email:</strong> $email</p>
                                <p style='color: #ffffff; margin: 5px 0; font-size: 15px;'><strong>Phone:</strong> $phone</p>
                                <p style='color: #ffffff; margin: 5px 0; font-size: 15px;'><strong>Bank/IBAN:</strong> <span style='color: #10b981;'>$iban</span></p>
                            </div>

                            <h3 style='color: #ffffff; font-size: 16px; margin-bottom: 20px;'>Reserved Spots</h3>
                            <table style='width: 100%; border-collapse: collapse; margin-bottom: 30px;'>
                                $ticketRowsHtml
                                <tr>
                                    <td colspan='2' style='padding: 30px 0 10px 0; color: #9ca3af; font-size: 13px; text-transform: uppercase; font-weight: 700;'>Grand Total</td>
                                    <td style='padding: 30px 0 10px 0; color: #ffffff; font-size: 28px; font-weight: 900; text-align: right;'>Rs " . number_format($total) . "</td>
                                </tr>
                            </table>

                            <div style='text-align: center; margin-top: 40px;'>
                                <a href='https://atifaslamconcert.ct.ws/admin/' style='background-color: #10b981; color: #000000; padding: 18px 40px; border-radius: 12px; font-weight: 800; text-decoration: none; display: inline-block; text-transform: uppercase; font-size: 14px;'>Open Admin Dashboard</a>
                            </div>
                        </div>
                    </div>
                </div>";
            $mail->send();

            // --- CUSTOMER EMAIL ---
            $mail->clearAddresses();
            $mail->addAddress($email, $name);
            $mail->Subject = 'Booking Request Received - Atif Aslam Live';
            $mail->addEmbeddedImage('img/banner.jpg', 'banner');

            $mail->Body = "
                <div style='background-color: #000000; padding: 40px 20px; font-family: \"Inter\", Arial, sans-serif;'>
                    <div style='max-width: 600px; margin: 0 auto; background-color: #0a0a0a; border: 1px solid #1f2937; border-radius: 24px; overflow: hidden;'>
                        <div style='background: linear-gradient(135deg, #000000, #0a0a0a); text-align: center; border-bottom: 1px solid #10b981;'>
                            <img src='cid:banner' style='width: 100%; display: block;' alt='Atif Aslam Concert'>
                            <div style='padding: 30px;'>
                                <div style='background-color: #10b981; color: #000000; font-weight: 900; display: inline-block; padding: 5px 15px; font-size: 14px; border-radius: 4px; margin-bottom: 15px;'>ZeeshanEvents</div>
                                <h1 style='color: #10b981; font-size: 28px; margin: 0; text-transform: uppercase; letter-spacing: 4px; font-weight: 900;'>Request Submitted</h1>
                            </div>
                        </div>
                        <div style='padding: 40px;'>
                            <p style='color: #ffffff; font-size: 17px; line-height: 1.6;'>Hello <strong>$name</strong>,</p>
                            <p style='color: #9ca3af; font-size: 15px; line-height: 1.8;'>Your request for the Atif Aslam Live concert has been successfully logged. We have received your payment details and are currently in the <strong>Evaluation Phase</strong>.</p>
                            
                            <div style='background-color: #10b981; border-radius: 16px; padding: 25px; margin: 35px 0; text-align: center;'>
                                <h3 style='color: #000000; text-transform: uppercase; font-size: 13px; font-weight: 900; margin: 0;'>Verification Status</h3>
                                <p style='color: #000000; font-size: 15px; font-weight: 600; margin: 8px 0 0 0;'>Evaluation in progress. Official e-tickets will follow shortly.</p>
                            </div>

                            <table style='width: 100%; border-collapse: collapse;'>
                                $ticketRowsHtml
                                <tr>
                                    <td colspan='2' style='padding: 30px 0 10px 0; color: #9ca3af; font-size: 13px; text-transform: uppercase; font-weight: 700;'>Total Amount</td>
                                    <td style='padding: 30px 0 10px 0; color: #10b981; font-size: 24px; font-weight: 900; text-align: right;'>Rs " . number_format($total) . "</td>
                                </tr>
                            </table>

                            <div style='margin-top: 50px; padding-top: 30px; border-top: 1px solid #1f2937; text-align: center;'>
                                <p style='color: #4b5563; font-size: 12px; line-height: 1.5;'>This is an automated request confirmation from ZeeshanEvents. If you have any questions, please reply to this email or contact support.</p>
                            </div>
                        </div>
                    </div>
                </div>";
            $mail->send();

            $orderId = $db->lastInsertRowID();
            $isSuccess = true;
        } catch (Exception $e) {
            $errorMsg = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } catch (Exception $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout | ZeeshanEvents</title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .otp-card-input {
            width: 50px;
            height: 65px;
            background: #f1f5f9;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 28px;
            font-weight: 900;
            text-align: center;
            color: #10b981;
            outline: none;
            transition: all 0.3s;
        }

        .otp-card-input:focus {
            border-color: #10b981;
            background: white;
            box-shadow: 0 0 15px rgba(16, 185, 129, 0.1);
        }

        .fade-in {
            animation: fadeIn 0.4s ease-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="text-slate-900 min-h-screen pb-12">

    <!-- Header -->
    <div class="bg-white border-b border-slate-200 py-6 px-4">
        <div class="max-w-5xl mx-auto flex justify-between items-center">
            <a href="index.html" class="flex items-center gap-2 text-slate-500 hover:text-slate-800 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span class="font-bold text-sm">Back to Map</span>
            </a>
            <div class="bg-black text-white px-4 py-2 rounded font-black text-sm tracking-tighter">
                ZeeshanEvents
            </div>
        </div>
    </div>

    <main class="max-w-5xl mx-auto px-4 mt-8">
        <?php if ($errorMsg): ?>
            <div class="bg-red-50 border border-red-200 text-red-600 p-4 rounded-xl mb-6 font-bold">
                <?php echo $errorMsg; ?>
            </div>
        <?php endif; ?>

        <?php if ($isSuccess): ?>
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden text-center space-y-6 pb-12">
                <img src="img/banner.jpg" class="w-full h-auto object-cover aspect-[21/9] border-b-4 border-emerald-500"
                    alt="Atif Aslam Banner">
                <div class="pt-6">
                    <div
                        class="w-20 h-20 bg-emerald-100 text-emerald-500 rounded-full flex items-center justify-center mx-auto">
                        <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <h1 class="text-3xl font-extrabold text-slate-800">Booking Request Sent!</h1>
                    <p class="text-slate-500 max-w-md mx-auto">Thank you for your request. We are currently evaluating your
                        payment. You will receive a confirmation email shortly.</p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center pt-6">
                        <a href="receipt.php?id=<?php echo $orderId; ?>" target="_blank"
                            class="bg-[#10b981] text-black px-8 py-3 rounded-xl font-bold shadow-lg transition hover:bg-emerald-400">Download
                            Receipt</a>
                        <a href="index.html"
                            class="bg-slate-900 text-white px-8 py-3 rounded-xl font-bold transition hover:bg-slate-800">Return
                            Home</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="flex flex-col lg:flex-row gap-8">
                <!-- Left: Form -->
                <div class="flex-grow space-y-8">
                    <div class="space-y-2">
                        <h1 class="text-3xl font-extrabold text-slate-900">Secure Checkout</h1>
                        <div
                            class="bg-red-50 text-red-600 text-xs font-bold py-2 px-4 rounded-full inline-flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Time remaining: 14:52
                        </div>
                    </div>

                    <!-- Order Summary Box -->
                    <div class="bg-white rounded-2xl border border-slate-200 p-6 space-y-6 shadow-sm">
                        <h3 class="font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            Order Summary
                        </h3>
                        <div id="checkout-items" class="space-y-4">
                            <!-- Items via JS -->
                        </div>
                        <div class="border-t border-slate-100 pt-4 space-y-2 text-sm">
                            <div class="flex justify-between text-slate-500"><span>Subtotal</span><span id="subtotal">Rs
                                    0</span></div>
                            <div class="flex justify-between text-slate-500"><span>Processing Fee (2%)</span><span
                                    id="fee">Rs 0</span></div>
                            <div
                                class="flex justify-between font-bold text-xl pt-4 border-t border-slate-100 text-slate-900">
                                <span>Total Amount</span>
                                <span id="total">Rs 0</span>
                            </div>
                        </div>
                    </div>

                    <!-- Bank Details -->
                    <div class="bg-[#eff6ff] rounded-2xl border border-blue-100 p-8 relative overflow-hidden group">
                        <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition">
                        </div>
                        <div class="flex items-center gap-3 mb-8">
                            <img src="img/ubl.png" class="h-10 w-auto" alt="UBL">
                            <span class="font-bold text-blue-900 text-lg">UBL Bank Transfer</span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-8 text-sm">
                            <div>
                                <p class="text-blue-400 font-bold uppercase tracking-widest text-[10px] mb-1">Account Title
                                </p>
                                <p class="font-extrabold text-blue-900 text-base">YOUR_ACCOUNT_TITLE_HERE</p>
                            </div>
                            <div>
                                <p class="text-blue-400 font-bold uppercase tracking-widest text-[10px] mb-1">Account No</p>
                                <p class="font-extrabold text-blue-900 text-base">YOUR_ACCOUNT_NUMBER_HERE</p>
                            </div>
                            <div class="sm:col-span-2">
                                <p class="text-blue-400 font-bold uppercase tracking-widest text-[10px] mb-1">IBAN / BIC</p>
                                <p class="font-extrabold text-blue-900 text-base">YOUR_IBAN_HERE</p>
                            </div>
                        </div>
                    </div>

                    <!-- Checkout Form -->
                    <form method="POST" class="space-y-8 bg-white p-8 rounded-2xl border border-slate-200 shadow-sm">
                        <input type="hidden" name="tickets_json" id="tickets_json">
                        <input type="hidden" name="total_val" id="total_val">

                        <div class="space-y-6">
                            <h3 class="font-extrabold text-xl text-slate-800">Your Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label class="text-[11px] font-bold uppercase text-slate-400 tracking-wider">Full Name
                                        *</label>
                                    <input type="text" name="name" required placeholder="John Doe"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[11px] font-bold uppercase text-slate-400 tracking-wider">Email
                                        Address *</label>
                                    <input type="email" name="email" required placeholder="john@example.com"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[11px] font-bold uppercase text-slate-400 tracking-wider">Phone
                                        Number *</label>
                                    <input type="tel" name="phone" required placeholder="+92 300 1234567"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition">
                                </div>
                                <div class="space-y-2">
                                    <label class="text-[11px] font-bold uppercase text-slate-400 tracking-wider">Your IBAN /
                                        Bank Account *</label>
                                    <input type="text" name="iban" required placeholder="Account from which you paid"
                                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-5 py-4 focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition">
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 border-t border-slate-100">
                            <label class="flex gap-4 cursor-pointer group">
                                <input type="checkbox" required class="mt-1 w-5 h-5 accent-emerald-500">
                                <span class="text-sm text-slate-500 leading-relaxed">
                                    I confirm that I have made the bank transfer to the above account and provided my
                                    correct transaction details.
                                </span>
                            </label>
                        </div>

                        <button type="button" onclick="initiateOTP()" id="btn-main-submit"
                            class="w-full bg-slate-900 hover:bg-slate-800 text-white py-5 rounded-2xl font-extrabold text-xl shadow-xl transition transform active:scale-[0.98]">
                            Confirm Booking Request
                        </button>
                    </form>

                    <!-- OTP Verification Screen -->
                    <div id="otp-screen"
                        class="hidden fade-in bg-white p-10 rounded-[2.5rem] border border-slate-200 shadow-2xl text-center space-y-8">
                        <div
                            class="w-20 h-20 bg-emerald-50 text-emerald-500 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002 2H5a2 2 0 00-2-2V7a2 2 0 002-2h14a2 2 0 002 2v10">
                                </path>
                            </svg>
                        </div>
                        <div class="space-y-2">
                            <h2 class="text-3xl font-black text-slate-900 tracking-tight">Verify Your Email</h2>
                            <p class="text-slate-500 text-sm">We've sent a 6-digit code to <span id="display-email"
                                    class="font-bold text-slate-900"></span></p>
                        </div>
                        <div class="flex justify-center gap-3 pt-4">
                            <?php for ($i = 1; $i <= 6; $i++): ?>
                                <input type="text" maxlength="1" class="otp-card-input"
                                    oninput="moveNext(this, <?php echo $i; ?>)" onkeydown="moveBack(event, <?php echo $i; ?>)">
                            <?php endfor; ?>
                        </div>
                        <div class="pt-8 space-y-4">
                            <button onclick="verifyAndSubmit()" id="btn-verify"
                                class="w-full bg-[#10b981] hover:bg-emerald-400 text-black py-5 rounded-2xl font-black text-lg shadow-lg transition">Complete
                                Booking</button>
                            <button onclick="backToForm()"
                                class="text-slate-400 hover:text-slate-600 font-bold text-sm uppercase tracking-widest transition">←
                                Back to Form</button>
                        </div>
                    </div>
                </div>

                <!-- Right: QR Code -->
                <div class="lg:w-[350px] space-y-6">
                    <div
                        class="bg-white rounded-2xl border border-slate-200 p-8 flex flex-col items-center text-center space-y-6 shadow-sm sticky top-8">
                        <p class="font-bold text-slate-400 uppercase tracking-widest text-[10px]">Scan with Banking App</p>
                        <div class="p-3 bg-white border border-slate-100 rounded-2xl shadow-inner">
                            <img src="img/qr.png" class="w-full h-auto" alt="Payment QR">
                        </div>
                        <div>
                            <h4 class="font-extrabold text-slate-800 text-xl tracking-tight">YOUR_ACCOUNT_TITLE_HERE</h4>
                            <p class="text-emerald-500 font-bold tracking-widest text-sm">UBL DIGITAL - 8203</p>
                        </div>
                        <p class="text-xs text-slate-400 leading-relaxed px-4">
                            Please scan this QR code or use the bank details to complete your payment before submitting this
                            form.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <script>
        // Load cart data from localStorage
        const cartData = JSON.parse(localStorage.getItem('cart_data') || '{"tickets":[], "total":0}');
        const isSuccess = <?php echo $isSuccess ? 'true' : 'false'; ?>;
        const hasError = <?php echo $errorMsg ? 'true' : 'false'; ?>;

        if (cartData.tickets.length === 0 && !isSuccess && !hasError) {
            window.location.href = 'index.html';
        }

        const itemsContainer = document.getElementById('checkout-items');
        const subtotalEl = document.getElementById('subtotal');
        const feeEl = document.getElementById('fee');
        const totalEl = document.getElementById('total');
        const ticketsJsonInput = document.getElementById('tickets_json');
        const totalValInput = document.getElementById('total_val');

        const fee = Math.round(cartData.total * 0.02);
        const grandTotal = cartData.total + fee;

        if (itemsContainer) {
            cartData.tickets.forEach(t => {
                const row = document.createElement('div');
                row.className = "flex justify-between items-center text-sm";
                row.innerHTML = `
                    <div class="flex flex-col">
                        <span class="font-bold text-slate-800 uppercase text-xs tracking-tight">${t.name}</span>
                        <span class="text-slate-400 text-[11px]">${t.qty} x Rs ${t.price.toLocaleString()}</span>
                    </div>
                    <span class="font-extrabold text-slate-900">Rs ${(t.qty * t.price).toLocaleString()}</span>
                `;
                itemsContainer.appendChild(row);
            });

            subtotalEl.textContent = `Rs ${cartData.total.toLocaleString()}`;
            feeEl.textContent = `Rs ${fee.toLocaleString()}`;
            totalEl.textContent = `Rs ${grandTotal.toLocaleString()}`;

            if (ticketsJsonInput) ticketsJsonInput.value = JSON.stringify(cartData.tickets);
            if (totalValInput) totalValInput.value = grandTotal;
        }

        // --- OTP Logic ---
        function moveNext(el, i) { if (el.value.length === 1 && i < 6) document.querySelectorAll('.otp-card-input')[i].focus(); }
        function moveBack(e, i) { if (e.key === "Backspace" && e.target.value === "" && i > 1) document.querySelectorAll('.otp-card-input')[i - 2].focus(); }

        document.querySelectorAll('.otp-card-input').forEach((input, index) => {
            input.addEventListener('paste', (e) => {
                e.preventDefault();
                const pastedData = e.clipboardData.getData('text').slice(0, 6).split('');
                const inputs = document.querySelectorAll('.otp-card-input');
                pastedData.forEach((char, i) => {
                    if (inputs[i]) inputs[i].value = char;
                });
                if (inputs[pastedData.length - 1]) inputs[pastedData.length - 1].focus();
            });
        });

        async function initiateOTP() {
            const form = document.querySelector('form');
            if (!form.checkValidity()) { form.reportValidity(); return; }

            const btn = document.getElementById('btn-main-submit');
            btn.disabled = true;
            btn.textContent = "Processing...";

            const email = document.querySelector('input[name="email"]').value;
            document.getElementById('display-email').textContent = email;

            const fd = new FormData();
            fd.append('email', email);

            try {
                const res = await fetch('?send_otp=1', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    form.classList.add('hidden');
                    document.getElementById('otp-screen').classList.remove('hidden');
                    document.querySelectorAll('.otp-card-input')[0].focus();
                } else {
                    alert("Error: " + (data.error || "Could not send OTP"));
                    btn.disabled = false;
                    btn.textContent = "Confirm Booking Request";
                }
            } catch (e) {
                alert("Network error. Please try again.");
                btn.disabled = false;
                btn.textContent = "Confirm Booking Request";
            }
        }

        function backToForm() {
            document.getElementById('otp-screen').classList.add('hidden');
            document.querySelector('form').classList.remove('hidden');
            const btn = document.getElementById('btn-main-submit');
            btn.disabled = false;
            btn.textContent = "Confirm Booking Request";
        }

        async function verifyAndSubmit() {
            let otp = "";
            document.querySelectorAll('.otp-card-input').forEach(i => otp += i.value);
            if (otp.length < 6) { alert("Please enter the full 6-digit code"); return; }

            const btn = document.getElementById('btn-verify');
            btn.disabled = true;
            btn.textContent = "Verifying...";

            const fd = new FormData();
            fd.append('otp_code', otp);

            try {
                const res = await fetch('?verify_otp=1', { method: 'POST', body: fd });
                const data = await res.json();
                if (data.success) {
                    document.querySelector('form').submit();
                } else {
                    alert("Invalid Verification Code");
                    btn.disabled = false;
                    btn.textContent = "Complete Booking";
                    document.querySelectorAll('.otp-card-input').forEach(i => i.value = "");
                    document.querySelectorAll('.otp-card-input')[0].focus();
                }
            } catch (e) {
                alert("Verification failed. Check your connection.");
                btn.disabled = false;
            }
        }
    </script>
</body>

</html>