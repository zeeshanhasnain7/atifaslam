<?php
$db = new SQLite3('database.sqlite');
$id = $_GET['id'] ?? 0;

$stmt = $db->prepare("SELECT * FROM orders WHERE id = :id");
$stmt->bindValue(':id', $id);
$result = $stmt->execute();
$order = $result->fetchArray(SQLITE3_ASSOC);

if (!$order) {
    die("Receipt not found.");
}

$tickets = json_decode($order['tickets'], true);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></title>
    <link rel="icon" type="image/png" href="img/favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap');
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        @media print {
            .no-print { display: none; }
            body { background: white !important; color: black !important; }
            .receipt-card { border: none !important; box-shadow: none !important; }
        }
    </style>
</head>
<body class="bg-slate-100 p-4 sm:p-10 flex flex-col items-center">

    <div class="no-print mb-8 space-x-4">
        <button onclick="window.print()" class="bg-[#10b981] text-black px-6 py-2 rounded-lg font-bold shadow-lg hover:bg-emerald-400 transition">Print / Save PDF</button>
        <a href="index.html" class="bg-slate-900 text-white px-6 py-2 rounded-lg font-bold hover:bg-slate-800 transition">Back to Home</a>
    </div>

    <div class="receipt-card w-full max-w-2xl bg-white rounded-[2rem] border border-slate-200 shadow-2xl overflow-hidden">
        <!-- Header -->
        <div class="bg-black p-10 text-center relative overflow-hidden">
            <div class="relative z-10 space-y-4">
                <div class="w-12 h-12 border-2 border-white flex items-center justify-center font-black text-white text-xl mx-auto">ZE</div>
                <h1 class="text-white text-2xl font-black uppercase tracking-[0.3em]">Payment Receipt</h1>
                <p class="text-[#10b981] font-bold text-xs uppercase tracking-widest">Atif Aslam Live - ZeeshanEvents</p>
            </div>
            <div class="absolute inset-0 opacity-10 grayscale contrast-125">
                <img src="img/banner.jpg" class="w-full h-full object-cover" alt="">
            </div>
        </div>

        <!-- Body -->
        <div class="p-10 space-y-10">
            <!-- Receipt Meta -->
            <div class="flex justify-between items-start border-b border-dashed border-slate-200 pb-8">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Receipt ID</p>
                    <p class="font-bold text-slate-900">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-1">Date Issued</p>
                    <p class="font-bold text-slate-900"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></p>
                </div>
            </div>

            <!-- Customer Details -->
            <div class="grid grid-cols-2 gap-8">
                <div>
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Billed To</p>
                    <p class="font-bold text-slate-900"><?php echo $order['name']; ?></p>
                    <p class="text-sm text-slate-500 mt-1"><?php echo $order['email']; ?></p>
                    <p class="text-sm text-slate-500"><?php echo $order['phone']; ?></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-2">Payment Method</p>
                    <p class="font-bold text-slate-900">Bank Transfer / QR</p>
                    <p class="text-sm text-slate-500 mt-1 font-mono"><?php echo $order['iban']; ?></p>
                </div>
            </div>

            <!-- Items -->
            <div class="space-y-4">
                <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400 mb-4">Order Details</p>
                <div class="space-y-4">
                    <?php foreach ($tickets as $t): ?>
                    <div class="flex justify-between items-center text-sm">
                        <div class="flex flex-col">
                            <span class="font-bold text-slate-900"><?php echo $t['name']; ?></span>
                            <span class="text-slate-500 text-xs">x<?php echo $t['qty']; ?> Tickets</span>
                        </div>
                        <span class="font-bold text-slate-900">Rs <?php echo number_format($t['price'] * $t['qty']); ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Summary -->
            <div class="bg-slate-50 rounded-2xl p-6 space-y-3">
                <div class="flex justify-between text-xs font-bold text-slate-400">
                    <span>Base Amount</span>
                    <span>Rs <?php echo number_format(array_reduce($tickets, function($sum, $t){ return $sum + ($t['price'] * $t['qty']); }, 0)); ?></span>
                </div>
                <div class="flex justify-between text-xs font-bold text-slate-400">
                    <span>Processing Fee (2%)</span>
                    <span>Included</span>
                </div>
                <div class="pt-4 border-t border-slate-200 flex justify-between items-center">
                    <span class="font-black text-slate-900 uppercase text-xs tracking-widest">Total Amount</span>
                    <span class="text-3xl font-black text-slate-900 tracking-tighter">Rs <?php echo number_format($order['total']); ?></span>
                </div>
            </div>

            <!-- Footer -->
            <div class="text-center pt-6">
                <div class="inline-block px-4 py-1 rounded-full bg-emerald-50 text-[#10b981] font-bold text-[10px] uppercase tracking-widest mb-4">Payment Pending Verification</div>
                <p class="text-[10px] text-slate-400 leading-relaxed uppercase font-bold tracking-widest">
                    This is a booking request receipt.<br>Final tickets will be issued after verification.
                </p>
            </div>
        </div>
        
        <!-- Bottom Edge -->
        <div class="h-4 bg-[#10b981]"></div>
    </div>

</body>
</html>
