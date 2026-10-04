<?php
/* ============================================================
 * 【关键】所有 PHP 处理逻辑必须放在任何 HTML 输出之前！
 * ============================================================ */

$servername = "localhost";
$username   = "root";
$password   = "123456";
$dbname     = "user";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

/* ============================================================
 * 贡献数 → 颜色 分级表
 * ============================================================ */
function num_to_color($num) {
    $tiers = [
        ['min' => 500, 'color' => '#c0392b'],
        ['min' => 200, 'color' => '#e74c3c'],
        ['min' => 100, 'color' => '#e67e22'],
        ['min' => 50,  'color' => '#f1c40f'],
        ['min' => 20,  'color' => '#2ecc71'],
        ['min' => 10,  'color' => '#1abc9c'],
        ['min' => 5,   'color' => '#3498db'],
        ['min' => 1,   'color' => '#95a5a6'],
    ];
    foreach ($tiers as $t) { if ($num >= $t['min']) return $t['color']; }
    return '#95a5a6';
}
function resolve_user_color($alc, $num) {
    if ((int)$alc === 2) return '#8e44ad';
    return num_to_color((int)$num);
}

/* ============================================================
 * DeepSeek AI 助手配置
 * ============================================================ */
$LLM_BASE_URL   = 'http://127.0.0.1:8080';
$LLM_MODEL      = 'deepseek-r1';
$LLM_TIMEOUT    = 300;
$LLM_MAX_TOKENS = 4096;
$LLM_DISABLE_THINK = false;

function extract_final_answer($text) {
    $text = (string)$text;
    $patterns = [
        '/<think\b[^>]*>[\s\S]*?<\/think>\s*/i',
        '/<thinking\b[^>]*>[\s\S]*?<\/thinking>\s*/i',
        '/◀think▶[\s\S]*?◀\/think▶\s*/i',
        '/\[think\][\s\S]*?\[\/think\]\s*/i',
    ];
    foreach ($patterns as $p) $text = preg_replace($p, '', $text);
    $text = preg_replace('/<think\b[^>]*>[\s\S]*$/i', '', $text);
    $t = trim($text);
    return $t !== '' ? $t : trim((string)$text);
}

function call_llm($prompt, $base_url, $model, $timeout, $max_tokens) {
    global $LLM_DISABLE_THINK;
    $payload = [
        'model'       => $model,
        'messages'    => [['role' => 'user', 'content' => $prompt]],
        'stream'      => false,
        'temperature' => 0.6,
        'max_tokens'  => (int)$max_tokens,
    ];
    if ($LLM_DISABLE_THINK) {
        $payload['chat_template_kwargs'] = ['enable_thinking' => false, 'thinking' => false];
        $payload['reasoning_effort']  = 'none';
        $payload['thinking_budget']   = 0;
    }
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $ch = curl_init($base_url . '/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $json,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => (int)$timeout,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_ENCODING       => '',
        CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    ]);
    $resp = curl_exec($ch);
    $err  = curl_error($ch);
    curl_close($ch);
    if ($resp === false) return ['ok' => false, 'error' => '无法连接本地 AI 服务：' . $err];
    $data = json_decode($resp, true);
    if (!is_array($data)) return ['ok' => false, 'error' => 'AI 服务返回异常：' . mb_substr((string)$resp, 0, 200)];
    if (isset($data['error'])) {
        $e = $data['error'];
        $emsg = is_array($e) && isset($e['message']) ? $e['message'] : (is_string($e) ? $e : '未知错误');
        return ['ok' => false, 'error' => 'AI 服务错误：' . $emsg];
    }
    $msg     = isset($data['choices'][0]['message']) ? $data['choices'][0]['message'] : [];
    $content = isset($msg['content']) ? (string)$msg['content'] : '';
    if ($content === '' && isset($msg['reasoning_content'])) $content = (string)$msg['reasoning_content'];
    $content = extract_final_answer($content);
    if ($content === '') return ['ok' => false, 'error' => 'AI 返回内容为空'];
    return ['ok' => true, 'content' => $content];
}

/* ============================================================
 * 多台 AI 服务器调度
 * ============================================================ */
function ai_server_probe($base_url, $timeout = 2) {
    if ($base_url === '') return false;
    $ch = curl_init($base_url . '/v1/models');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => $timeout,
        CURLOPT_CONNECTTIMEOUT => $timeout,
        CURLOPT_ENCODING       => '',
    ]);
    $r    = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($r !== false && $code >= 200 && $code < 400);
}
function ai_lock_dir() {
    $d = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'llama_ai_locks';
    if (!is_dir($d)) @mkdir($d, 0777, true);
    return $d;
}
function ai_lock_path($uid, $ip) {
    return ai_lock_dir() . DIRECTORY_SEPARATOR . md5($uid . '|' . $ip) . '.lock';
}
function ai_lock_is_busy($uid, $ip) {
    $file = ai_lock_path($uid, $ip);
    if (!is_file($file)) return false;
    $fp = @fopen($file, 'c');
    if (!$fp) return false;
    $busy = !@flock($fp, LOCK_EX | LOCK_NB);
    if (!$busy) { @flock($fp, LOCK_UN); }
    @fclose($fp);
    return $busy;
}
function ai_server_base_url($ip, $port = 8080) {
    $ip = trim((string)$ip);
    if ($ip === '') return '';
    if (preg_match('#^https?://#i', $ip)) return rtrim($ip, '/');
    return 'http://' . $ip . ':' . $port;
}
function pick_ai_server($conn, $port = 8080, $probe_timeout = 2) {
    $rows = [];
    $res  = $conn->query("SELECT `uid`, `ip`, `num` FROM `ai_server` ORDER BY `num` ASC, `uid` ASC");
    if ($res) { while ($r = $res->fetch_assoc()) $rows[] = $r; }
    if (empty($rows)) return ['ok' => false, 'empty_table' => true, 'error' => '当前服务繁忙，请稍后尝试。'];
    foreach ($rows as $row) {
        $ip = trim((string)$row['ip']);
        if ($ip === '') continue;
        $base_url = ai_server_base_url($ip, $port);
        if ($base_url === '') continue;
        $fp = @fopen(ai_lock_path($row['uid'], $row['ip']), 'c');
        if (!$fp) continue;
        if (!@flock($fp, LOCK_EX | LOCK_NB)) { @fclose($fp); continue; }
        if (!ai_server_probe($base_url, $probe_timeout)) {
            @flock($fp, LOCK_UN); @fclose($fp); continue;
        }
        return ['ok' => true, 'server' => $row, 'base_url' => $base_url, 'lock_fp' => $fp];
    }
    return ['ok' => false, 'error' => '当前服务繁忙，请稍后尝试。'];
}
function release_ai_server($fp) {
    if ($fp) { @flock($fp, LOCK_UN); @fclose($fp); }
}
function bump_ai_server_num($conn, $uid) {
    $uid = (int)$uid;
    $stmt = $conn->prepare("UPDATE `ai_server` SET `num` = COALESCE(`num`, 0) + 1 WHERE `uid` = ?");
    if ($stmt) { $stmt->bind_param("i", $uid); $stmt->execute(); $stmt->close(); }
}
function collect_ai_server_status($conn, $port = 8080, $probe_timeout = 2) {
    $rows = [];
    $res  = $conn->query("SELECT `uid`, `ip`, `num` FROM `ai_server` ORDER BY `num` ASC, `uid` ASC");
    if ($res) { while ($r = $res->fetch_assoc()) $rows[] = $r; }
    if (empty($rows)) return [];
    $results = [];
    $multi   = curl_multi_init();
    $handles = [];
    foreach ($rows as $i => $row) {
        $ip = trim((string)$row['ip']);
        $base_url = ai_server_base_url($ip, $port);
        $busy = ai_lock_is_busy($row['uid'], $row['ip']);
        $results[$i] = ['uid' => (int)$row['uid'], 'ip' => $row['ip'], 'num' => (int)$row['num'], 'busy' => $busy, 'alive' => false];
        if (!$busy && $base_url !== '') {
            $ch = curl_init($base_url . '/v1/models');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => $probe_timeout,
                CURLOPT_CONNECTTIMEOUT => $probe_timeout,
                CURLOPT_ENCODING       => '',
            ]);
            curl_multi_add_handle($multi, $ch);
            $handles[$i] = $ch;
        }
    }
    $running = null;
    do {
        curl_multi_exec($multi, $running);
        if ($running) curl_multi_select($multi, 0.2);
    } while ($running > 0);
    foreach ($handles as $i => $ch) {
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $results[$i]['alive'] = ($code >= 200 && $code < 400);
        curl_multi_remove_handle($multi, $ch);
        curl_close($ch);
    }
    curl_multi_close($multi);
    $out = [];
    foreach ($results as $r) {
        if ($r['busy'])      $state = 'busy';
        elseif ($r['alive']) $state = 'ok';
        else                 $state = 'down';
        $out[] = ['uid' => $r['uid'], 'ip' => $r['ip'], 'num' => $r['num'], 'state' => $state];
    }
    return $out;
}

/* ============================================================
 * SSE 流式输出 + 深度思考分离
 * ============================================================ */

/**
 * ★ 强制把所有 PHP 输出缓冲层全部清空并关闭，
 *   让后续的 echo 一产生就立刻到达客户端。
 *   在 fastcgi/nginx/apache 环境中这是让 SSE 实时生效的关键。
 */
function sse_kill_all_buffers() {
    @ini_set('zlib.output_compression', '0');
    @ini_set('output_buffering', '0');
    @ini_set('implicit_flush', '1');
    @ini_set('display_errors', '0');
    if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', '1'); }
    while (ob_get_level() > 0) { @ob_end_flush(); }
    @ob_implicit_flush(true);
}

/**
 * 发送一个 SSE 事件。
 */
function sse_event($arr) {
    echo 'data: ' . json_encode($arr, JSON_UNESCAPED_UNICODE) . "\n\n";
    @flush();
}

/**
 * ★ 关键修复：发送一个 4KB 的 SSE 注释填充行。
 *   很多 nginx + fastcgi / Apache + mod_proxy_fcgi 场景会有一个 4KB 的
 *   内部初始缓冲区，只有等它填满或请求结束才会把内容发给浏览器。
 *   之前发的 ": stream-start\n\n" 只有 17 字节，不足以突破缓冲区。
 *   改成 4KB 以上的填充，能立刻把响应头发出去，之后每个事件都是小 chunk，
 *   不会再触发缓冲阈值，从而实现真正的“边生成边显示”。
 */
function sse_prime_4k() {
    $pad = str_repeat('-', 4096);
    echo ': ' . $pad . "\n\n";
    @flush();
}

function utf8_safe_len($s, $len) {
    $n = strlen($s);
    if ($len >= $n) return $n;
    if ($len <= 0)  return 0;
    while ($len > 0 && (ord($s[$len]) & 0xC0) === 0x80) { $len--; }
    return $len;
}

function think_parser_feed(&$st, $text) {
    $st['buf'] .= $text;
    $out = [];
    $STARTS = ['<thinking>', '<think>', ' thinking', '◀think▶', '[think]'];
    $ENDS   = ['</thinking>', '</think>', ' response', '◀/think▶', '[/think]'];
    $MAXTAG = 11;

    while (true) {
        if (!$st['in_think']) {
            $found = null; $pos = null;
            foreach ($STARTS as $s) {
                $p = stripos($st['buf'], $s);
                if ($p !== false && ($pos === null || $p < $pos)) { $pos = $p; $found = $s; }
            }
            if ($found !== null) {
                if ($pos > 0) $out[] = ['content', substr($st['buf'], 0, $pos)];
                $st['buf'] = substr($st['buf'], $pos + strlen($found));
                $st['in_think'] = true;
                continue;
            }
            $keep    = min(strlen($st['buf']), $MAXTAG);
            $emitLen = utf8_safe_len($st['buf'], strlen($st['buf']) - $keep);
            if ($emitLen > 0) {
                $out[] = ['content', substr($st['buf'], 0, $emitLen)];
                $st['buf'] = substr($st['buf'], $emitLen);
            }
            break;
        } else {
            $found = null; $pos = null;
            foreach ($ENDS as $s) {
                $p = stripos($st['buf'], $s);
                if ($p !== false && ($pos === null || $p < $pos)) { $pos = $p; $found = $s; }
            }
            if ($found !== null) {
                if ($pos > 0) $out[] = ['reasoning', substr($st['buf'], 0, $pos)];
                $st['buf'] = substr($st['buf'], $pos + strlen($found));
                $st['in_think'] = false;
                continue;
            }
            $keep    = min(strlen($st['buf']), $MAXTAG);
            $emitLen = utf8_safe_len($st['buf'], strlen($st['buf']) - $keep);
            if ($emitLen > 0) {
                $out[] = ['reasoning', substr($st['buf'], 0, $emitLen)];
                $st['buf'] = substr($st['buf'], $emitLen);
            }
            break;
        }
    }
    return $out;
}

function think_parser_flush(&$st) {
    $out = [];
    if ($st['buf'] !== '') {
        $out[] = [$st['in_think'] ? 'reasoning' : 'content', $st['buf']];
        $st['buf'] = '';
    }
    return $out;
}

function handle_stream_chunk(&$state, $chunk) {
    $state['sse'] .= $chunk;
    while (($pos = strpos($state['sse'], "\n")) !== false) {
        $line = rtrim(substr($state['sse'], 0, $pos), "\r");
        $state['sse'] = substr($state['sse'], $pos + 1);

        if ($line === '' || strncmp($line, 'data:', 5) !== 0) continue;
        $payload = trim(substr($line, 5));
        if ($payload === '' || $payload === '[DONE]') continue;

        $d = json_decode($payload, true);
        if (!is_array($d)) continue;

        if (isset($d['error'])) {
            $e = $d['error'];
            $state['error'] = is_array($e) && isset($e['message']) ? $e['message'] : (is_string($e) ? $e : '未知错误');
            continue;
        }
        if (!isset($d['choices'][0])) continue;

        $choice = $d['choices'][0];
        $delta  = isset($choice['delta']) ? $choice['delta'] : (isset($choice['message']) ? $choice['message'] : []);

        $reasoning = '';
        if (isset($delta['reasoning_content'])) $reasoning = (string)$delta['reasoning_content'];
        elseif (isset($delta['reasoning']))     $reasoning = (string)$delta['reasoning'];
        if ($reasoning !== '') {
            sse_event(['type' => 'reasoning', 'content' => $reasoning]);
        }

        $content = isset($delta['content']) ? (string)$delta['content'] : '';
        if ($content !== '') {
            foreach (think_parser_feed($state['think'], $content) as $e) {
                sse_event(['type' => $e[0], 'content' => $e[1]]);
                if ($e[0] === 'content' && trim($e[1]) !== '') $state['has_content'] = true;
            }
        }
    }
}

function stream_llm_chat($prompt, $base_url, $model, $timeout, $max_tokens, $disable_think = false) {
    $payload = [
        'model'       => $model,
        'messages'    => [['role' => 'user', 'content' => $prompt]],
        'stream'      => true,
        'temperature' => 0.6,
        'max_tokens'  => (int)$max_tokens,
    ];
    if ($disable_think) {
        $payload['chat_template_kwargs'] = ['enable_thinking' => false, 'thinking' => false];
        $payload['reasoning_effort'] = 'none';
        $payload['thinking_budget']  = 0;
    }
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);

    $state = [
        'sse'         => '',
        'think'       => ['in_think' => false, 'buf' => ''],
        'has_content' => false,
        'error'       => '',
        'aborted'     => false,
    ];

    $ch = curl_init($base_url . '/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_POST            => true,
        CURLOPT_POSTFIELDS      => $json,
        CURLOPT_HTTPHEADER      => [
            'Content-Type: application/json',
            'Accept: text/event-stream',
            'Accept-Encoding: identity',
        ],
        CURLOPT_RETURNTRANSFER  => false,
        CURLOPT_TIMEOUT         => (int)$timeout,
        CURLOPT_CONNECTTIMEOUT  => 10,
        CURLOPT_ENCODING        => 'identity',
        CURLOPT_TCP_NODELAY     => true,
        CURLOPT_BUFFERSIZE      => 128,
        CURLOPT_HTTP_VERSION    => CURL_HTTP_VERSION_1_1,
        CURLOPT_WRITEFUNCTION   => function ($ch, $chunk) use (&$state) {
            if (connection_aborted()) { $state['aborted'] = true; return 0; }
            handle_stream_chunk($state, $chunk);
            return strlen($chunk);
        },
    ]);

    $ok   = curl_exec($ch);
    $err  = curl_error($ch);
    $http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($state['aborted']) return ['ok' => false, 'error' => '', 'aborted' => true];
    foreach (think_parser_flush($state['think']) as $e) {
        sse_event(['type' => $e[0], 'content' => $e[1]]);
        if ($e[0] === 'content' && trim($e[1]) !== '') $state['has_content'] = true;
    }
    if ($ok === false) {
        if (connection_aborted()) return ['ok' => false, 'error' => '', 'aborted' => true];
        return ['ok' => false, 'error' => '无法连接本地 AI 服务：' . $err];
    }
    if ($state['error'] !== '') return ['ok' => false, 'error' => 'AI 服务错误：' . $state['error']];
    if ($http >= 400)          return ['ok' => false, 'error' => 'AI 服务返回 HTTP ' . $http];
    if (!$state['has_content']) return ['ok' => false, 'error' => 'AI 未返回最终答案（可能被 max_tokens 截断，请调大 LLM_MAX_TOKENS）'];
    return ['ok' => true, 'error' => '', 'aborted' => false];
}
/* ============================================================ */

$is_logged_in = false;
$user_info    = null;
$msg          = '';
$ai_prompt    = '';
$ai_reply     = '';
$ai_error     = '';
$ai_remaining = null;
$ai_server_status = [];

/* ---------------- 1. 用 cookie 自动登录 ---------------- */
if (!empty($_COOKIE['login_cookie'])) {
    $token = $_COOKIE['login_cookie'];
    $stmt  = $conn->prepare("SELECT `uid` FROM `cookie` WHERE `cookie` = ?");
    if ($stmt) {
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($c = $res->fetch_assoc()) {
            $uid = (int)$c['uid'];
            $stmt->close();
            $stmt = $conn->prepare("SELECT * FROM `user` WHERE `uid` = ?");
            if ($stmt) {
                $stmt->bind_param("i", $uid);
                $stmt->execute();
                $res = $stmt->get_result();
                if ($row = $res->fetch_assoc()) { $is_logged_in = true; $user_info = $row; }
                $stmt->close();
            }
        } else {
            $stmt->close();
            setcookie("login_cookie", "", time() - 3600, "/", "", false, true);
        }
    }
}

/* ---------------- AI 配置加载 ---------------- */
$ai_config = ['rate_limit' => 1, 'window_minutes' => 15, 'enabled' => 1];
$ai_remaining = null;
if ($is_logged_in) {
    $ai_cfg_stmt = $conn->prepare("SELECT `rate_limit`, `window_minutes`, `enabled` FROM `ai_config` WHERE `id` = 1");
    if ($ai_cfg_stmt) {
        $ai_cfg_stmt->execute();
        $ai_cr = $ai_cfg_stmt->get_result()->fetch_assoc();
        $ai_cfg_stmt->close();
        if ($ai_cr) {
            $ai_config = [
                'rate_limit'     => (int)$ai_cr['rate_limit'],
                'window_minutes' => (int)$ai_cr['window_minutes'],
                'enabled'        => (int)$ai_cr['enabled'],
            ];
        }
    }
    if ((int)$user_info['alc'] !== 2 && $ai_config['enabled'] === 1) {
        $ai_uid = (int)$user_info['uid'];
        $ai_win = (int)$ai_config['window_minutes'];
        $ai_cnt_stmt = $conn->prepare("SELECT COUNT(*) AS c FROM `ai_usage` WHERE `uid` = ? AND `used_at` >= DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        if ($ai_cnt_stmt) {
            $ai_cnt_stmt->bind_param("ii", $ai_uid, $ai_win);
            $ai_cnt_stmt->execute();
            $ai_crow = $ai_cnt_stmt->get_result()->fetch_assoc();
            $ai_cnt_stmt->close();
            $used = (int)($ai_crow['c'] ?? 0);
            $ai_remaining = (int)$ai_config['rate_limit'] - $used;
        }
    }
    if ((int)$user_info['alc'] === 1 || (int)$user_info['alc'] === 2) {
        $ai_server_status = collect_ai_server_status($conn);
    }
}

/* ---------------- 2. 处理 POST ---------------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {

    $action = $_POST['action'] ?? '';

    /* ---------- ★ AI 流式问答 ---------- */
    if ($action === 'ai_chat_stream') {

        /* ★ 强制关闭所有缓冲层 */
        sse_kill_all_buffers();

        header('Content-Type: text/event-stream; charset=utf-8');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('X-Accel-Buffering: no');
        header('Connection: keep-alive');
        header('Content-Encoding: identity');

        /* ★★★ 4KB 填充，突破 nginx / fastcgi 初始缓冲区 ★★★ */
        sse_prime_4k();

        $stream_deny     = '';
        $stream_prompt   = trim($_POST['prompt'] ?? '');
        $stream_is_admin = ($is_logged_in && (int)$user_info['alc'] === 2);

        if (!$is_logged_in) {
            $stream_deny = '请先登录后再使用 AI 助手';
        } else if ((int)$user_info['alc'] < 1) {
            $stream_deny = '你的账号无权限使用 AI 助手';
        } else if ($ai_config['enabled'] !== 1) {
            $stream_deny = 'AI 助手当前已停用';
        } else if ($stream_prompt === '') {
            $stream_deny = '请输入你要问的问题';
        } else if (!$stream_is_admin && $ai_remaining !== null && $ai_remaining <= 0) {
            $stream_deny = '已达每 ' . (int)$ai_config['window_minutes'] . ' 分钟 ' . (int)$ai_config['rate_limit'] . ' 次上限，请稍后再试';
        }

        if ($stream_deny !== '') {
            sse_event(['type' => 'error', 'message' => $stream_deny]);
            sse_event(['type' => 'done']);
            exit;
        }

        @set_time_limit(0);

        $picked = pick_ai_server($conn);
        if (!$picked['ok']) {
            if (!empty($picked['empty_table'])) {
                $base_url   = $LLM_BASE_URL;
                $lock_fp    = null;
                $server_uid = null;
            } else {
                sse_event(['type' => 'error', 'message' => $picked['error']]);
                sse_event(['type' => 'done', 'remaining' => $ai_remaining, 'is_admin' => $stream_is_admin, 'rate_limit' => (int)$ai_config['rate_limit']]);
                exit;
            }
        } else {
            $base_url   = $picked['base_url'];
            $lock_fp    = $picked['lock_fp'];
            $server_uid = (int)$picked['server']['uid'];
        }

        $r = stream_llm_chat($stream_prompt, $base_url, $LLM_MODEL, $LLM_TIMEOUT, $LLM_MAX_TOKENS, $LLM_DISABLE_THINK);

        if ($lock_fp) { release_ai_server($lock_fp); $lock_fp = null; }

        if (!empty($r['aborted'])) exit;

        if ($r['ok']) {
            if ($server_uid !== null) bump_ai_server_num($conn, $server_uid);
            $ins = $conn->prepare("INSERT INTO `ai_usage` (`uid`, `used_at`) VALUES (?, NOW())");
            if ($ins) {
                $u = (int)$user_info['uid'];
                $ins->bind_param("i", $u);
                $ins->execute();
                $ins->close();
            }
            if ($ai_remaining !== null) $ai_remaining = max(0, $ai_remaining - 1);
            sse_event(['type' => 'done', 'remaining' => $ai_remaining, 'is_admin' => $stream_is_admin, 'rate_limit' => (int)$ai_config['rate_limit']]);
        } else {
            sse_event(['type' => 'error', 'message' => $r['error']]);
            sse_event(['type' => 'done', 'remaining' => $ai_remaining, 'is_admin' => $stream_is_admin, 'rate_limit' => (int)$ai_config['rate_limit']]);
        }
        exit;
    }

    /* ---------- 退出登录 ---------- */
    if ($action === 'logout') {
        if ($is_logged_in) {
            $del = $conn->prepare("DELETE FROM `cookie` WHERE `uid` = ?");
            if ($del) { $uid_del = (int)$user_info['uid']; $del->bind_param("i", $uid_del); $del->execute(); $del->close(); }
        }
        setcookie("login_cookie", "", time() - 3600, "/", "", false, true);
        $here = strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        header("Location: " . $here);
        exit;
    }

    /* ---------- 修改密码 ---------- */
    else if ($action === 'change_password') {
        if (!$is_logged_in) {
            $msg = "<div class='alert alert-error'>请先登录</div>";
        } else {
            $old   = $_POST['old_password']  ?? '';
            $new   = $_POST['new_password']  ?? '';
            $new2  = $_POST['new_password2'] ?? '';
            if ($old === '' || $new === '' || $new2 === '') {
                $msg = "<div class='alert alert-error'>请填写完整</div>";
            } else if ($new !== $new2) {
                $msg = "<div class='alert alert-error'>两次新密码不一致</div>";
            } else if (strlen($new) < 6) {
                $msg = "<div class='alert alert-error'>新密码至少 6 位</div>";
            } else if (!password_verify($old, $user_info['password'])) {
                $msg = "<div class='alert alert-error'>旧密码错误</div>";
            } else {
                $newHash = password_hash($new, PASSWORD_DEFAULT);
                $upd = $conn->prepare("UPDATE `user` SET `password` = ? WHERE `uid` = ?");
                if ($upd) {
                    $uid_cp = (int)$user_info['uid'];
                    $upd->bind_param("si", $newHash, $uid_cp);
                    if ($upd->execute()) {
                        $newToken = bin2hex(random_bytes(32));
                        $ck = $conn->prepare("UPDATE `cookie` SET `cookie` = ? WHERE `uid` = ?");
                        if ($ck) { $ck->bind_param("si", $newToken, $uid_cp); $ck->execute(); $ck->close(); }
                        else {
                            $ck2 = $conn->prepare("INSERT INTO `cookie` (`cookie`, `uid`) VALUES (?, ?)");
                            if ($ck2) { $ck2->bind_param("si", $newToken, $uid_cp); $ck2->execute(); $ck2->close(); }
                        }
                        setcookie("login_cookie", $newToken, time() + 86400 * 30, "/", "", false, true);
                        $user_info['password'] = $newHash;
                        $msg = "<div class='alert alert-success'>密码修改成功，其他设备已被强制下线</div>";
                    } else {
                        $msg = "<div class='alert alert-error'>修改失败: " . htmlspecialchars($upd->error) . "</div>";
                    }
                    $upd->close();
                } else {
                    $msg = "<div class='alert alert-error'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</div>";
                }
            }
        }
    }

    /* ---------- 登录 ---------- */
    else if ($action === 'login') {
        $name         = $_POST['name'] ?? '';
        $UserPassword = $_POST['password'] ?? '';
        if ($name === '' || $UserPassword === '') {
            $msg = "<div class='alert alert-error'>请输入用户名和密码</div>";
        } else {
            $stmt = $conn->prepare("SELECT * FROM `user` WHERE `name` = ?");
            if (!$stmt) {
                $msg = "<div class='alert alert-error'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</div>";
            } else {
                $stmt->bind_param("s", $name);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if (password_verify($UserPassword, $row['password'])) {
                        if (password_needs_rehash($row['password'], PASSWORD_DEFAULT)) {
                            $newHash = password_hash($UserPassword, PASSWORD_DEFAULT);
                            $upd = $conn->prepare("UPDATE `user` SET `password` = ? WHERE `uid` = ?");
                            if ($upd) { $upd->bind_param("si", $newHash, $row['uid']); $upd->execute(); $upd->close(); }
                        }
                        $is_logged_in = true;
                        $user_info    = $row;
                        $token = bin2hex(random_bytes(32));
                        $uid_for_cookie = (int)$row['uid'];
                        $chk = $conn->prepare("SELECT `cookie` FROM `cookie` WHERE `uid` = ? LIMIT 1");
                        if ($chk) {
                            $chk->bind_param("i", $uid_for_cookie);
                            $chk->execute();
                            $chkRes = $chk->get_result();
                            $existing = $chkRes->fetch_assoc();
                            $chk->close();
                            if ($existing) {
                                $stmt2 = $conn->prepare("UPDATE `cookie` SET `cookie` = ? WHERE `uid` = ?");
                                if ($stmt2) { $stmt2->bind_param("si", $token, $uid_for_cookie); $stmt2->execute(); $stmt2->close(); }
                            } else {
                                $stmt2 = $conn->prepare("INSERT INTO `cookie` (`cookie`, `uid`) VALUES (?, ?)");
                                if ($stmt2) { $stmt2->bind_param("si", $token, $uid_for_cookie); $stmt2->execute(); $stmt2->close(); }
                            }
                            setcookie("login_cookie", $token, time() + 86400 * 30, "/", "", false, true);
                        }
                    } else {
                        $msg = "<div class='alert alert-error'>用户名或密码错误</div>";
                    }
                } else {
                    $msg = "<div class='alert alert-error'>用户不存在</div>";
                }
                $stmt->close();
            }
        }
    }

    /* ---------- 注册 ---------- */
    else if ($action === 'reslogin') {
        $name          = $_POST['name'] ?? '';
        $UserPassword  = $_POST['password'] ?? '';
        $UserPassword2 = $_POST['passwordag'] ?? '';
        if ($name === '' || $UserPassword === '' || $UserPassword2 === '') {
            $msg = "<div class='alert alert-error'>请输入用户名和密码</div>";
        } else if ($UserPassword !== $UserPassword2) {
            $msg = "<div class='alert alert-error'>两次密码不一致</div>";
        } else {
            $chk = $conn->prepare("SELECT `uid` FROM `user` WHERE `name` = ?");
            $exists = false;
            if ($chk) {
                $chk->bind_param("s", $name);
                $chk->execute();
                $chkRes = $chk->get_result();
                $exists = (bool)$chkRes->fetch_assoc();
                $chk->close();
            }
            if ($exists) {
                $msg = "<div class='alert alert-error'>用户名已存在</div>";
            } else {
                $hashed = password_hash($UserPassword, PASSWORD_DEFAULT);
                $stmt1  = $conn->prepare("INSERT INTO `user` (`name`, `calling`, `password`, `alc`, `color`, `num`) VALUES (?, 'none', ?, '1', '#0e90d2', '0')");
                if ($stmt1) {
                    $stmt1->bind_param("ss", $name, $hashed);
                    if ($stmt1->execute()) {
                        $msg = "<div class='alert alert-success'>注册成功，请登录</div>";
                    } else {
                        $msg = "<div class='alert alert-error'>注册失败: " . htmlspecialchars($stmt1->error) . "</div>";
                    }
                    $stmt1->close();
                } else {
                    $msg = "<div class='alert alert-error'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</div>";
                }
            }
        }
    }

    /* ---------- 上传 ---------- */
    else if ($action === 'upload') {
        if ($is_logged_in && ($user_info['alc'] == 1 || $user_info['alc'] == 2)) {
            $uid     = intval($_POST['uid'] ?? 0);
            $field   = $_POST['field'] ?? '';
            $content = $_POST['content'] ?? '';
            $title   = trim($_POST['title'] ?? '');
            $allowed = range('A', 'Z');
            if ($uid > 0 && in_array($field, $allowed, true)) {
                $has_content = false;
                $chk2 = $conn->prepare("SELECT `$field` AS `cur` FROM `cook` WHERE `uid` = ?");
                if ($chk2) {
                    $chk2->bind_param("i", $uid);
                    $chk2->execute();
                    $c2r = $chk2->get_result()->fetch_assoc();
                    $chk2->close();
                    if ($c2r) {
                        $cur_value = (string)$c2r['cur'];
                        if ($cur_value !== '' && strtolower(trim($cur_value)) !== 'none') $has_content = true;
                    }
                }
                if ($has_content && (int)$user_info['alc'] !== 2) {
                    $msg = "<div class='alert alert-error'>该字段已被他人填写，普通用户只能新增空白字段，不能覆盖。如需修改请联系管理员。</div>";
                } else {
                    $contributor = (string)$user_info['uid'];
                    if ($title !== '') $content = $contributor . ' ' . $title . "\n" . $content;
                    else               $content = $contributor . "\n" . $content;
                    $stmt = $conn->prepare("UPDATE `cook` SET `$field` = ? WHERE `uid` = ?");
                    if ($stmt) {
                        $stmt->bind_param("si", $content, $uid);
                        if ($stmt->execute()) {
                            $msg = "<div class='alert alert-success'>上传成功</div>";
                            $contributor_uid = (int)$user_info['uid'];
                            $inc = $conn->prepare("UPDATE `user` SET `num` = COALESCE(`num`, 0) + 1 WHERE `uid` = ?");
                            if ($inc) {
                                $inc->bind_param("i", $contributor_uid);
                                $inc->execute();
                                $inc->close();
                                $get_stmt = $conn->prepare("SELECT `num`, `alc` FROM `user` WHERE `uid` = ?");
                                if ($get_stmt) {
                                    $get_stmt->bind_param("i", $contributor_uid);
                                    $get_stmt->execute();
                                    $g = $get_stmt->get_result()->fetch_assoc();
                                    $get_stmt->close();
                                    if ($g) {
                                        $new_num = (int)$g['num'];
                                        $new_alc = (int)$g['alc'];
                                        $new_color = resolve_user_color($new_alc, $new_num);
                                        $col_stmt = $conn->prepare("UPDATE `user` SET `color` = ? WHERE `uid` = ?");
                                        if ($col_stmt) { $col_stmt->bind_param("si", $new_color, $contributor_uid); $col_stmt->execute(); $col_stmt->close(); }
                                        $user_info['num']   = $new_num;
                                        $user_info['color'] = $new_color;
                                    }
                                }
                            }
                        } else {
                            $msg = "<div class='alert alert-error'>上传失败: " . htmlspecialchars($stmt->error) . "</div>";
                        }
                        $stmt->close();
                    } else {
                        $msg = "<div class='alert alert-error'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</div>";
                    }
                }
            } else {
                $msg = "<div class='alert alert-error'>参数错误</div>";
            }
        } else {
            $msg = "<div class='alert alert-error'>无权限上传</div>";
        }
    }

    /* ---------- 新建 cook ---------- */
    else if ($action === 'create_cook') {
        if ($is_logged_in && (int)$user_info['alc'] === 2) {
            $new_uid  = intval($_POST['new_uid']  ?? 0);
            $new_name = trim($_POST['new_name']   ?? '');
            if ($new_uid <= 0) {
                $msg = "<div class='alert alert-error'>请输入合法的 UID（正整数）</div>";
            } else if ($new_name === '') {
                $msg = "<div class='alert alert-error'>请输入名称</div>";
            } else {
                $chk = $conn->prepare("SELECT `uid` FROM `cook` WHERE `uid` = ? LIMIT 1");
                $exists = false;
                if ($chk) { $chk->bind_param("i", $new_uid); $chk->execute(); $chkRes = $chk->get_result(); $exists = (bool)$chkRes->fetch_assoc(); $chk->close(); }
                if ($exists) {
                    $msg = "<div class='alert alert-error'>UID " . $new_uid . " 已存在，不能重复新建</div>";
                } else {
                    $creator_uid = (int)$user_info['uid'];
                    $stmt = $conn->prepare(
                        "INSERT INTO `cook`
                           (`uid`, `name`, `UserUid`,
                            `A`, `B`, `C`, `D`, `E`, `F`, `G`, `H`, `I`, `J`, `K`,
                            `L`, `M`, `N`, `O`, `P`, `Q`, `R`, `S`, `T`, `U`, `V`, `W`, `X`, `Y`, `Z`)
                         VALUES
                           (?, ?, ?,
                            'none','none','none','none','none','none','none','none','none','none','none',
                            'none','none','none','none','none','none','none','none','none','none','none','none','none','none','none')"
                    );
                    if ($stmt) {
                        $stmt->bind_param("isi", $new_uid, $new_name, $creator_uid);
                        if ($stmt->execute()) $msg = "<div class='alert alert-success'>新建 cook 成功：UID=" . $new_uid . "</div>";
                        else                   $msg = "<div class='alert alert-error'>新建失败: " . htmlspecialchars($stmt->error) . "</div>";
                        $stmt->close();
                    } else {
                        $msg = "<div class='alert alert-error'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</div>";
                    }
                }
            }
        } else {
            $msg = "<div class='alert alert-error'>无权限新建 cook</div>";
        }
    }

    /* ---------- AI 问答（非流式回退） ---------- */
    else if ($action === 'ai_chat') {
        if (!$is_logged_in) {
            $ai_error = '请先登录后再使用 AI 助手';
        } else if ((int)$user_info['alc'] < 1) {
            $ai_error = '你的账号无权限使用 AI 助手';
        } else if ($ai_config['enabled'] !== 1) {
            $ai_error = 'AI 助手当前已停用';
        } else {
            $prompt = trim($_POST['prompt'] ?? '');
            if ($prompt === '') {
                $ai_error = '请输入你要问的问题';
            } else {
                $is_admin = ((int)$user_info['alc'] === 2);
                if (!$is_admin && $ai_remaining !== null && $ai_remaining <= 0) {
                    $ai_error = '已达每 ' . (int)$ai_config['window_minutes'] . ' 分钟 ' . (int)$ai_config['rate_limit'] . ' 次上限，请稍后再试';
                } else {
                    set_time_limit(0);
                    $picked = pick_ai_server($conn);
                    if (!$picked['ok']) {
                        if (!empty($picked['empty_table'])) { $base_url = $LLM_BASE_URL; $lock_fp = null; $server_uid = null; }
                        else { $ai_error = $picked['error']; $base_url = null; }
                    } else {
                        $base_url   = $picked['base_url'];
                        $lock_fp    = $picked['lock_fp'];
                        $server_uid = (int)$picked['server']['uid'];
                    }
                    if ($base_url !== null) {
                        $result = call_llm($prompt, $base_url, $LLM_MODEL, $LLM_TIMEOUT, $LLM_MAX_TOKENS);
                        if ($lock_fp) { release_ai_server($lock_fp); $lock_fp = null; }
                        if ($result['ok']) {
                            if ($server_uid !== null) bump_ai_server_num($conn, $server_uid);
                            $ai_prompt = $prompt;
                            $ai_reply  = $result['content'];
                            $ins = $conn->prepare("INSERT INTO `ai_usage` (`uid`, `used_at`) VALUES (?, NOW())");
                            if ($ins) { $u = (int)$user_info['uid']; $ins->bind_param("i", $u); $ins->execute(); $ins->close(); }
                            if ($ai_remaining !== null) $ai_remaining = max(0, $ai_remaining - 1);
                        } else {
                            $ai_error = $result['error'];
                        }
                    }
                }
            }
        }
    }
}

/* ---------------- 3. 排行榜数据 ---------------- */
$rank_rows  = [];
$rank_error = '';
if ($is_logged_in) {
    $rank_stmt = $conn->prepare(
        "SELECT `uid`, `name`, `calling`, `color`, `alc`, COALESCE(`num`, 0) AS `num`
         FROM `user`
         ORDER BY COALESCE(`num`, 0) DESC, `uid` ASC
         LIMIT 50"
    );
    if ($rank_stmt) {
        $rank_stmt->execute();
        $rank_res = $rank_stmt->get_result();
        while ($rr = $rank_res->fetch_assoc()) $rank_rows[] = $rr;
        $rank_stmt->close();
    } else {
        $rank_error = '排行榜查询失败: ' . $conn->error;
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>欢迎</title>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <style>
        :root {
            --bg: #eef2f8;
            --glass-bg: rgba(255, 255, 255, 0.55);
            --glass-bg-strong: rgba(255, 255, 255, 0.72);
            --glass-bg-soft: rgba(255, 255, 255, 0.40);
            --glass-border: rgba(255, 255, 255, 0.75);
            --glass-border-soft: rgba(255, 255, 255, 0.55);
            --glass-shadow: 0 8px 32px rgba(31, 38, 135, 0.10), 0 2px 8px rgba(31, 38, 135, 0.06);
            --glass-shadow-hover: 0 12px 40px rgba(31, 38, 135, 0.16), 0 4px 12px rgba(31, 38, 135, 0.10);
            --text: #262626;
            --text-muted: #8c8c8c;
            --text-soft: #595959;
            --text-faint: #b0b0b0;
            --card-bg: rgba(255, 255, 255, 0.55);
            --card-border: rgba(255, 255, 255, 0.75);
            --card-shadow: 0 8px 32px rgba(31, 38, 135, 0.10), 0 2px 8px rgba(31, 38, 135, 0.06);
            --divider: rgba(0, 0, 0, 0.06);
            --divider-strong: rgba(0, 0, 0, 0.08);
            --input-bg: rgba(255, 255, 255, 0.65);
            --input-border: rgba(0, 0, 0, 0.10);
            --hover-bg: rgba(52, 152, 219, 0.10);
            --hover-bg-strong: rgba(52, 152, 219, 0.16);
            --row-hover: rgba(255, 255, 255, 0.45);
            --primary: #3498db;
            --primary-hover: #2980b9;
            --primary-active: #2471a3;
            --code-bg: rgba(255, 255, 255, 0.5);
            --code-bar-bg: rgba(240, 242, 245, 0.65);
            --code-text: #262626;
            --field-head-bg: rgba(255, 255, 255, 0.4);
            --alert-error-bg: rgba(255, 241, 240, 0.72);
            --alert-error-border: rgba(255, 163, 158, 0.75);
            --alert-error-text: #a8071a;
            --alert-success-bg: rgba(246, 255, 237, 0.72);
            --alert-success-border: rgba(183, 235, 143, 0.75);
            --alert-success-text: #135200;
            --rank-me-bg: rgba(52, 152, 219, 0.12);
            --rank-me-bg-hover: rgba(52, 152, 219, 0.18);
            --btn-logout-hover-bg: rgba(255, 245, 245, 0.85);
            --btn-danger: #cf222e;
            --tok-kw: #cf222e;
            --tok-type: #0550ae;
            --tok-str: #0a3069;
            --tok-num: #0550ae;
            --tok-com: #6e7781;
            --tok-pre: #8250df;
            --dur-fast: .18s;
            --dur: .32s;
            --dur-slow: .5s;
            --ease: cubic-bezier(.4, 0, .2, 1);
            --ease-spring: cubic-bezier(.34, 1.56, .64, 1);
        }
        html[data-theme="dark"] {
            --bg: #101317;
            --glass-bg: rgba(36, 40, 46, 0.55);
            --glass-bg-strong: rgba(36, 40, 46, 0.75);
            --glass-bg-soft: rgba(36, 40, 46, 0.35);
            --glass-border: rgba(255, 255, 255, 0.10);
            --glass-border-soft: rgba(255, 255, 255, 0.06);
            --glass-shadow: 0 8px 32px rgba(0, 0, 0, 0.45), 0 2px 8px rgba(0, 0, 0, 0.30);
            --glass-shadow-hover: 0 12px 40px rgba(0, 0, 0, 0.55), 0 4px 12px rgba(0, 0, 0, 0.38);
            --text: #e6e6e6;
            --text-muted: #9aa0a6;
            --text-soft: #b8bcc2;
            --text-faint: #6a7078;
            --card-bg: rgba(36, 40, 46, 0.55);
            --card-border: rgba(255, 255, 255, 0.10);
            --card-shadow: 0 8px 32px rgba(0, 0, 0, 0.45), 0 2px 8px rgba(0, 0, 0, 0.30);
            --divider: rgba(255, 255, 255, 0.06);
            --divider-strong: rgba(255, 255, 255, 0.10);
            --input-bg: rgba(30, 34, 39, 0.75);
            --input-border: rgba(255, 255, 255, 0.10);
            --hover-bg: rgba(74, 168, 232, 0.14);
            --hover-bg-strong: rgba(74, 168, 232, 0.22);
            --row-hover: rgba(255, 255, 255, 0.04);
            --primary: #4aa8e8;
            --primary-hover: #5cb5ef;
            --primary-active: #3a91cf;
            --code-bg: rgba(30, 34, 39, 0.65);
            --code-bar-bg: rgba(42, 47, 54, 0.65);
            --code-text: #e6e6e6;
            --field-head-bg: rgba(42, 47, 54, 0.55);
            --alert-error-bg: rgba(44, 26, 28, 0.72);
            --alert-error-border: rgba(110, 42, 46, 0.85);
            --alert-error-text: #ff9aa0;
            --alert-success-bg: rgba(22, 40, 26, 0.72);
            --alert-success-border: rgba(46, 94, 58, 0.85);
            --alert-success-text: #86d99e;
            --rank-me-bg: rgba(74, 168, 232, 0.14);
            --rank-me-bg-hover: rgba(74, 168, 232, 0.22);
            --btn-logout-hover-bg: rgba(44, 26, 28, 0.85);
            --btn-danger: #ff7b72;
            --tok-kw: #ff7b72;
            --tok-type: #79c0ff;
            --tok-str: #a5d6ff;
            --tok-num: #79c0ff;
            --tok-com: #8b949e;
            --tok-pre: #d2a8ff;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; }
        body {
            margin: 0;
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", "PingFang SC",
                         "Hiragino Sans GB", "Microsoft YaHei", sans-serif;
            font-size: 14px;
            line-height: 1.6;
            background: var(--bg);
            background-attachment: fixed;
            background-image:
                radial-gradient(1200px 800px at 8% 0%,   rgba(120, 180, 255, 0.42), transparent 55%),
                radial-gradient(1000px 700px at 92% 12%, rgba(255, 150, 200, 0.32), transparent 55%),
                radial-gradient(900px 700px at 25% 95%,  rgba(150, 230, 200, 0.32), transparent 55%),
                radial-gradient(1000px 800px at 95% 92%, rgba(200, 170, 255, 0.32), transparent 55%);
            transition: background-color var(--dur) var(--ease), color var(--dur) var(--ease);
            position: relative;
            overflow-x: hidden;
        }
        html[data-theme="dark"] body {
            background-image:
                radial-gradient(1200px 800px at 8% 0%,   rgba(60, 100, 200, 0.32), transparent 55%),
                radial-gradient(1000px 700px at 92% 12%, rgba(180, 60, 140, 0.22), transparent 55%),
                radial-gradient(900px 700px at 25% 95%,  rgba(60, 180, 150, 0.22), transparent 55%),
                radial-gradient(1000px 800px at 95% 92%, rgba(120, 90, 220, 0.28), transparent 55%);
        }
        a { color: var(--primary); text-decoration: none; transition: color var(--dur-fast) var(--ease); }
        a:hover { color: var(--primary-hover); text-decoration: underline; }
        .navbar {
            background: var(--glass-bg-strong);
            backdrop-filter: saturate(180%) blur(22px);
            -webkit-backdrop-filter: saturate(180%) blur(22px);
            border-bottom: 1px solid var(--glass-border-soft);
            box-shadow: 0 1px 3px rgba(0,0,0,0.04), 0 8px 24px rgba(31,38,135,0.05);
            position: sticky; top: 0; z-index: 100;
            transition: background-color var(--dur) var(--ease), border-color var(--dur) var(--ease);
        }
        .navbar-inner {
            max-width: 1000px; margin: 0 auto; padding: 0 20px; height: 56px;
            display: flex; align-items: center; justify-content: space-between;
        }
        .navbar-brand { font-size: 18px; font-weight: 700; color: var(--primary); letter-spacing: .5px; transition: transform var(--dur-fast) var(--ease); }
        .navbar-brand:hover { transform: translateY(-1px); }
        .navbar-user { font-size: 13px; color: var(--text-muted); display: flex; align-items: center; gap: 10px; }
        .navbar-user strong { font-weight: 600; }
        .navbar-user form { display: inline; margin: 0; }
        .navbar-user .btn-logout,
        .navbar-user .btn-theme,
        .navbar-user .btn-compat {
            font-size: 12px; line-height: 1; padding: 5px 11px; color: var(--text-soft);
            background: var(--glass-bg-soft); border: 1px solid var(--glass-border-soft);
            border-radius: 8px; cursor: pointer;
            transition: color var(--dur-fast) var(--ease), border-color var(--dur-fast) var(--ease),
                background-color var(--dur-fast) var(--ease), transform var(--dur-fast) var(--ease-spring),
                box-shadow var(--dur-fast) var(--ease);
        }
        .navbar-user .btn-logout:hover { color: var(--btn-danger); border-color: var(--btn-danger); background: var(--btn-logout-hover-bg); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(207, 34, 46, 0.15); }
        .navbar-user .btn-theme, .navbar-user .btn-compat { font-size: 14px; padding: 4px 9px; min-width: 34px; text-align: center; }
        .navbar-user .btn-theme:hover, .navbar-user .btn-compat:hover { color: var(--primary); border-color: var(--primary); background: var(--hover-bg); transform: translateY(-1px) scale(1.05); box-shadow: 0 4px 12px rgba(52, 152, 219, 0.18); }
        .navbar-user .btn-theme:active, .navbar-user .btn-compat:active { transform: translateY(0) scale(0.96); }
        .container { max-width: 1000px; margin: 0 auto; padding: 24px 20px 60px; position: relative; z-index: 1; }
        .card {
            background: var(--card-bg);
            backdrop-filter: saturate(180%) blur(18px);
            -webkit-backdrop-filter: saturate(180%) blur(18px);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 22px 26px;
            margin-bottom: 18px;
            box-shadow: var(--card-shadow);
            transition: background-color var(--dur) var(--ease), border-color var(--dur) var(--ease),
                box-shadow var(--dur) var(--ease), transform var(--dur) var(--ease);
            animation: cardIn var(--dur-slow) var(--ease-spring) both;
        }
        .card:hover { box-shadow: var(--glass-shadow-hover); transform: translateY(-2px); }
        @keyframes cardIn { from { opacity: 0; transform: translateY(12px) scale(0.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
        .card-title {
            font-size: 16px; font-weight: 600; color: var(--text);
            margin: 0 0 16px 0; padding-bottom: 12px;
            border-bottom: 1px solid var(--divider);
            display: flex; align-items: center; justify-content: space-between;
        }
        .card-title .sub { font-size: 12px; color: var(--text-muted); font-weight: 400; }
        label { display: inline-block; color: var(--text-soft); font-size: 13px; margin-bottom: 4px; }
        input[type="text"], input[type="password"], input[type="number"], textarea, select {
            font-family: inherit; font-size: 13px; color: var(--text);
            background: var(--input-bg); border: 1px solid var(--input-border);
            border-radius: 10px; padding: 8px 12px; outline: none;
            transition: border-color var(--dur-fast) var(--ease), box-shadow var(--dur-fast) var(--ease),
                background-color var(--dur) var(--ease), color var(--dur) var(--ease),
                transform var(--dur-fast) var(--ease);
            vertical-align: middle;
        }
        input[type="text"]:focus, input[type="password"]:focus, input[type="number"]:focus,
        textarea:focus, select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(52,152,219,0.18), 0 4px 14px rgba(52,152,219,0.12);
            transform: translateY(-1px);
        }
        textarea { width: 100%; font-family: Consolas, Monaco, "Courier New", monospace; resize: vertical; line-height: 1.65; }
        select { padding: 7px 10px; }
        button, .btn {
            font-family: inherit; font-size: 13px; line-height: 1; padding: 8px 18px; color: #ffffff;
            background: linear-gradient(180deg, var(--primary) 0%, var(--primary-hover) 100%);
            border: 1px solid var(--primary); border-radius: 10px; cursor: pointer;
            transition: background-color var(--dur-fast) var(--ease), border-color var(--dur-fast) var(--ease),
                transform var(--dur-fast) var(--ease-spring), box-shadow var(--dur-fast) var(--ease);
            display: inline-block;
            box-shadow: 0 2px 8px rgba(52, 152, 219, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.25);
        }
        button:hover, .btn:hover {
            background: linear-gradient(180deg, var(--primary-hover) 0%, var(--primary-active) 100%);
            border-color: var(--primary-hover); transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(52, 152, 219, 0.32), inset 0 1px 0 rgba(255, 255, 255, 0.30);
        }
        button:active, .btn:active { transform: translateY(0) scale(0.97); box-shadow: 0 2px 6px rgba(52, 152, 219, 0.22), inset 0 1px 0 rgba(255, 255, 255, 0.20); }
        button:disabled, .btn:disabled { opacity: .6; cursor: not-allowed; transform: none !important; }
        button.btn-ghost {
            color: var(--text-soft); background: var(--glass-bg-soft); border-color: var(--glass-border-soft);
            box-shadow: 0 2px 8px rgba(31, 38, 135, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.35);
        }
        html[data-theme="dark"] button.btn-ghost { box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25), inset 0 1px 0 rgba(255, 255, 255, 0.06); }
        button.btn-ghost:hover { color: var(--primary); border-color: var(--primary); background: var(--hover-bg); transform: translateY(-1px); box-shadow: 0 6px 18px rgba(52, 152, 219, 0.18), inset 0 1px 0 rgba(255, 255, 255, 0.40); }
        button.btn-ghost:active { background: var(--hover-bg-strong); transform: translateY(0) scale(0.97); }
        button.btn-danger { color: var(--btn-danger); background: var(--glass-bg-soft); border-color: var(--btn-danger); box-shadow: 0 2px 8px rgba(207, 34, 46, 0.08); }
        button.btn-danger:hover { background: var(--btn-logout-hover-bg); transform: translateY(-1px); }
        .alert { border-radius: 12px; padding: 12px 16px; margin: 14px 0; font-size: 13px; border: 1px solid transparent; animation: alertIn var(--dur) var(--ease-spring) both; }
        @keyframes alertIn { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
        .alert-error { color: var(--alert-error-text); background: var(--alert-error-bg); border-color: var(--alert-error-border); box-shadow: 0 4px 14px rgba(207, 34, 46, 0.10); }
        .alert-success { color: var(--alert-success-text); background: var(--alert-success-bg); border-color: var(--alert-success-border); box-shadow: 0 4px 14px rgba(26, 127, 55, 0.10); }
        .rank-table { width: 100%; border-collapse: separate; border-spacing: 0; font-size: 13px; }
        .rank-table th, .rank-table td { padding: 10px 14px; text-align: left; vertical-align: middle; border-bottom: 1px solid var(--divider); transition: background-color var(--dur-fast) var(--ease); }
        .rank-table thead th { background: var(--glass-bg-soft); color: var(--text-muted); font-weight: 500; font-size: 12px; border-bottom: 1px solid var(--card-border); user-select: none; }
        .rank-table thead th:first-child { border-top-left-radius: 10px; }
        .rank-table thead th:last-child  { border-top-right-radius: 10px; }
        .rank-table tbody tr { transition: background-color var(--dur-fast) var(--ease); }
        .rank-table tbody tr:hover { background: var(--row-hover); }
        .rank-table tbody tr:last-child td { border-bottom: none; }
        .rank-no { width: 60px; font-family: Consolas, Monaco, "Courier New", monospace; color: var(--text-muted); text-align: center; }
        .rank-no.top1 { color: #d4a017; font-weight: 700; font-size: 15px; }
        .rank-no.top2 { color: #8a8f98; font-weight: 700; font-size: 15px; }
        .rank-no.top3 { color: #b06e3f; font-weight: 700; font-size: 15px; }
        .rank-num { width: 90px; text-align: right; font-family: Consolas, Monaco, "Courier New", monospace; font-weight: 600; color: var(--primary); }
        .rank-me { background: var(--rank-me-bg) !important; }
        .rank-me:hover { background: var(--rank-me-bg-hover) !important; }
        .rank-tag { font-size: 11px; line-height: 1.4; color: var(--primary); border: 1px solid var(--primary); border-radius: 6px; padding: 1px 6px; margin-left: 6px; background: rgba(52, 152, 219, 0.08); }
        .rank-admin-tag { font-size: 11px; line-height: 1.4; color: #ffffff; background: linear-gradient(180deg, #9b59b6 0%, #8e44ad 100%); border-radius: 6px; padding: 1px 6px; margin-left: 6px; box-shadow: 0 2px 6px rgba(142, 68, 173, 0.25); }
        .rank-uid { font-size: 12px; color: var(--text-faint); margin-left: 8px; font-family: Consolas, Monaco, "Courier New", monospace; }
        .cook-item {
            background: var(--glass-bg);
            backdrop-filter: saturate(160%) blur(16px); -webkit-backdrop-filter: saturate(160%) blur(16px);
            border: 1px solid var(--glass-border); border-radius: 14px; margin-bottom: 14px; overflow: hidden;
            content-visibility: auto; contain-intrinsic-size: auto 200px;
            box-shadow: var(--glass-shadow);
            transition: background-color var(--dur) var(--ease), border-color var(--dur) var(--ease),
                box-shadow var(--dur) var(--ease), transform var(--dur) var(--ease);
            animation: cardIn var(--dur-slow) var(--ease-spring) both;
        }
        .cook-item:hover { box-shadow: var(--glass-shadow-hover); transform: translateY(-2px); }
        .cook-head { display: flex; align-items: center; justify-content: space-between; padding: 12px 18px; background: var(--glass-bg-soft); border-bottom: 1px solid var(--divider); transition: background-color var(--dur) var(--ease); }
        .cook-head-title { font-size: 14px; font-weight: 600; color: var(--text); display: flex; align-items: center; flex-wrap: wrap; gap: 0; min-width: 0; }
        .cook-head-title .idx { display: inline-block; min-width: 28px; color: var(--primary); font-family: Consolas, Monaco, "Courier New", monospace; margin-right: 6px; }
        .cook-creator { display: inline-flex; align-items: center; margin-left: 10px; font-size: 12px; font-weight: 400; color: var(--text-muted); letter-spacing: 0; white-space: nowrap; }
        .cook-creator .creator-label { color: var(--text-faint); margin-right: 4px; }
        .cook-creator .creator-name { font-weight: 500; }
        .cook-creator .creator-badge { display: inline-block; font-size: 10px; font-weight: 500; line-height: 1.4; color: #ffffff; padding: 0 5px; border-radius: 4px; margin-left: 4px; letter-spacing: 0; }
        .cook-creator .creator-missing { color: var(--text-faint); font-family: Consolas, Monaco, "Courier New", monospace; font-size: 11px; }
        .cook-body { padding: 12px 18px 6px 18px; max-height: 12000px; overflow: hidden; transition: max-height var(--dur-slow) var(--ease), opacity var(--dur) var(--ease), padding var(--dur) var(--ease); opacity: 1; }
        .cook-body.collapsed { max-height: 0 !important; padding-top: 0; padding-bottom: 0; opacity: 0; }
        .field-block { border: 1px solid var(--glass-border-soft); border-radius: 12px; margin-bottom: 10px; overflow: hidden; background: var(--glass-bg-soft); transition: background-color var(--dur) var(--ease), border-color var(--dur) var(--ease), box-shadow var(--dur) var(--ease); }
        .field-block:hover { box-shadow: 0 4px 14px rgba(31, 38, 135, 0.08); }
        html[data-theme="dark"] .field-block:hover { box-shadow: 0 4px 14px rgba(0, 0, 0, 0.28); }
        .field-head { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--field-head-bg); border-bottom: 1px solid transparent; transition: background-color var(--dur) var(--ease), border-color var(--dur) var(--ease); }
        .field-block.expanded .field-head { border-bottom-color: var(--divider-strong); }
        .field-title { display: inline-flex; align-items: baseline; gap: 10px; font-family: Consolas, Monaco, "Courier New", monospace; font-weight: 700; font-size: 14px; color: var(--primary); letter-spacing: .5px; min-width: 0; }
        .field-title-text { font-family: -apple-system, "PingFang SC", "Microsoft YaHei", sans-serif; font-weight: 400; font-size: 13px; color: var(--text-soft); letter-spacing: 0; max-width: 620px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .field-toggle { flex-shrink: 0; font-size: 12px; padding: 5px 14px; color: var(--text-soft); background: var(--glass-bg-soft); border: 1px solid var(--glass-border-soft); border-radius: 8px; transition: color var(--dur-fast) var(--ease), border-color var(--dur-fast) var(--ease), background-color var(--dur-fast) var(--ease), transform var(--dur-fast) var(--ease-spring), box-shadow var(--dur-fast) var(--ease); }
        .field-toggle:hover { color: var(--primary); border-color: var(--primary); background: var(--hover-bg); transform: translateY(-1px); box-shadow: 0 4px 12px rgba(52, 152, 219, 0.15); }
        .field-toggle:active { transform: translateY(0) scale(0.95); }
        .field-body { padding: 0 14px; max-height: 0; opacity: 0; overflow: hidden; transition: max-height var(--dur-slow) var(--ease), opacity var(--dur) var(--ease), padding var(--dur) var(--ease); }
        .field-body.show { max-height: 12000px; opacity: 1; padding: 12px 14px 6px 14px; }
        .code-block { position: relative; margin: 4px 0 12px 0; background: var(--code-bg); border: 1px solid var(--glass-border-soft); border-radius: 12px; overflow: hidden; transition: background-color var(--dur) var(--ease), border-color var(--dur) var(--ease), box-shadow var(--dur) var(--ease); }
        .code-block:hover { box-shadow: 0 6px 20px rgba(31, 38, 135, 0.10); }
        html[data-theme="dark"] .code-block:hover { box-shadow: 0 6px 20px rgba(0, 0, 0, 0.35); }
        .code-bar { display: flex; align-items: center; justify-content: space-between; padding: 6px 12px 6px 14px; background: var(--code-bar-bg); border-bottom: 1px solid var(--divider-strong); font-size: 12px; color: var(--text-muted); }
        .code-bar-left { display: flex; align-items: center; gap: 10px; min-width: 0; }
        .code-lang { font-family: Consolas, Monaco, "Courier New", monospace; font-weight: 600; color: var(--text-soft); letter-spacing: .5px; }
        .code-contributor { display: inline-flex; align-items: center; font-size: 12px; line-height: 1.4; color: var(--text-soft); background: var(--glass-bg-soft); border: 1px solid var(--glass-border-soft); border-radius: 8px; padding: 2px 9px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 480px; }
        .copy-btn { font-size: 12px; padding: 5px 12px; color: var(--text-soft); background: var(--glass-bg-soft); border: 1px solid var(--glass-border-soft); border-radius: 8px; flex-shrink: 0; box-shadow: none; transition: color var(--dur-fast) var(--ease), border-color var(--dur-fast) var(--ease), background-color var(--dur-fast) var(--ease), transform var(--dur-fast) var(--ease-spring); }
        .copy-btn:hover { color: var(--primary); border-color: var(--primary); background: var(--hover-bg); transform: translateY(-1px); box-shadow: none; }
        .copy-btn:active { transform: translateY(0) scale(0.95); }
        .copy-btn.ok   { color: var(--alert-success-text); border-color: var(--alert-success-border); background: var(--alert-success-bg); }
        .copy-btn.fail { color: var(--alert-error-text); border-color: var(--alert-error-border); background: var(--alert-error-bg); }
        pre { margin: 0; }
        code.language-cpp { display: block; background: transparent; color: var(--code-text); border: none; padding: 14px 18px; margin: 0; font-family: Consolas, Monaco, "Courier New", monospace; font-size: 13px; line-height: 1.65; overflow-x: auto; white-space: pre; tab-size: 4; }
        code.language-cpp .tok-kw   { color: var(--tok-kw); font-weight: 600; }
        code.language-cpp .tok-type { color: var(--tok-type); }
        code.language-cpp .tok-str  { color: var(--tok-str); }
        code.language-cpp .tok-num  { color: var(--tok-num); }
        code.language-cpp .tok-com  { color: var(--tok-com); font-style: italic; }
        code.language-cpp .tok-pre  { color: var(--tok-pre); }

        /* AI 助手 */
        .ai-think-box { margin: 6px 0 0; padding: 12px 14px; background: var(--glass-bg-soft); border: 1px dashed var(--glass-border-soft); border-radius: 10px; font-size: 13px; line-height: 1.75; color: var(--text-soft); white-space: pre-wrap; word-break: break-word; max-height: 420px; overflow-y: auto; transition: background-color var(--dur) var(--ease), border-color var(--dur) var(--ease); }
        .ai-answer-box { margin: 6px 0 0; padding: 14px 16px; background: var(--code-bg); border: 1px solid var(--glass-border-soft); border-radius: 10px; font-size: 13.5px; line-height: 1.75; max-height: 560px; overflow-y: auto; }
        .ai-question-box { margin: 6px 0 0; padding: 10px 14px; background: var(--input-bg); border: 1px solid var(--input-border); border-radius: 10px; white-space: pre-wrap; word-break: break-word; }
        .ai-status { display: inline-flex; align-items: center; gap: 6px; font-size: 12px; color: var(--text-muted); margin-left: 10px; }
        .ai-dot { width: 7px; height: 7px; border-radius: 50%; background: var(--primary); animation: aiPulse 1.2s ease-in-out infinite; }
        @keyframes aiPulse { 0%, 100% { opacity: .25; transform: scale(.8); } 50% { opacity: 1; transform: scale(1.15); } }
        .ai-typing::after { content: '▍'; margin-left: 1px; color: var(--primary); animation: aiBlink 1s steps(2, start) infinite; }
        @keyframes aiBlink { to { visibility: hidden; } }

        /* ★ Markdown 渲染样式 */
        .ai-answer-box > *:first-child { margin-top: 0; }
        .ai-answer-box > *:last-child  { margin-bottom: 0; }
        .ai-answer-box p { margin: 0 0 10px 0; }
        .ai-answer-box h1, .ai-answer-box h2, .ai-answer-box h3,
        .ai-answer-box h4, .ai-answer-box h5, .ai-answer-box h6 {
            margin: 16px 0 8px 0; font-weight: 600; line-height: 1.3;
            color: var(--text);
        }
        .ai-answer-box h1 { font-size: 1.5em; border-bottom: 1px solid var(--divider-strong); padding-bottom: 6px; }
        .ai-answer-box h2 { font-size: 1.3em; border-bottom: 1px solid var(--divider); padding-bottom: 4px; }
        .ai-answer-box h3 { font-size: 1.15em; }
        .ai-answer-box h4 { font-size: 1.05em; }
        .ai-answer-box h5, .ai-answer-box h6 { font-size: 1em; color: var(--text-soft); }
        .ai-answer-box ul, .ai-answer-box ol { margin: 0 0 10px 0; padding-left: 1.6em; }
        .ai-answer-box li { margin: 2px 0; }
        .ai-answer-box blockquote {
            margin: 8px 0; padding: 6px 12px; color: var(--text-soft);
            border-left: 3px solid var(--primary); background: var(--hover-bg);
            border-radius: 0 8px 8px 0;
        }
        .ai-answer-box code {
            font-family: Consolas, Monaco, "Courier New", monospace;
            font-size: 0.92em;
            background: var(--glass-bg-soft);
            border: 1px solid var(--glass-border-soft);
            border-radius: 5px;
            padding: 1px 6px;
            color: var(--tok-kw);
        }
        .ai-answer-box pre {
            margin: 10px 0; padding: 12px 14px; overflow-x: auto;
            background: var(--code-bar-bg);
            border: 1px solid var(--glass-border-soft);
            border-radius: 10px;
            font-family: Consolas, Monaco, "Courier New", monospace;
            font-size: 12.5px;
            line-height: 1.6;
            white-space: pre;
        }
        .ai-answer-box pre code {
            background: none; border: none; padding: 0; color: var(--code-text);
            font-size: inherit;
        }
        .ai-answer-box table {
            border-collapse: collapse; margin: 10px 0; font-size: 0.95em;
            display: block; overflow-x: auto; max-width: 100%;
        }
        .ai-answer-box th, .ai-answer-box td {
            border: 1px solid var(--divider-strong); padding: 6px 10px;
        }
        .ai-answer-box th { background: var(--glass-bg-soft); font-weight: 600; }
        .ai-answer-box a { color: var(--primary); text-decoration: underline; }
        .ai-answer-box hr {
            border: none; border-top: 1px solid var(--divider-strong); margin: 14px 0;
        }
        .ai-answer-box img { max-width: 100%; border-radius: 8px; }

        .ai-node-badge { display: inline-block; font-size: 11px; line-height: 1.6; padding: 1px 8px; border-radius: 6px; border: 1px solid transparent; }
        .ai-node-ok   { color: #135200; background: #eaf9ee; border-color: #b7e5c0; }
        .ai-node-busy { color: #a8071a; background: #fdecea; border-color: #f5b7b1; }
        .ai-node-down { color: #a8071a; background: #fdecea; border-color: #f5b7b1; }
        html[data-theme="dark"] .ai-node-ok   { color: #86d99e; background: rgba(22, 40, 26, 0.72); border-color: rgba(46, 94, 58, 0.85); }
        html[data-theme="dark"] .ai-node-busy,
        html[data-theme="dark"] .ai-node-down { color: #ff9aa0; background: rgba(44, 26, 28, 0.72); border-color: rgba(110, 42, 46, 0.85); }
        .row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .muted { color: var(--text-muted); font-size: 12px; }
        ::-webkit-scrollbar { width: 10px; height: 10px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.15); border-radius: 999px; border: 2px solid transparent; background-clip: content-box; }
        html[data-theme="dark"] ::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.15); background-clip: content-box; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(0, 0, 0, 0.25); background-clip: content-box; }
        html[data-theme="dark"] ::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.25); background-clip: content-box; }
        @media (prefers-reduced-motion: reduce) { * { animation-duration: 0.001ms !important; transition-duration: 0.001ms !important; } }
        html.compat {
            --glass-bg: #ffffff; --glass-bg-strong: #ffffff; --glass-bg-soft: #f2f2f7;
            --glass-border: #d1d1d6; --glass-border-soft: #e5e5ea;
            --glass-shadow: 0 0 0 rgba(0,0,0,0); --glass-shadow-hover: 0 0 0 rgba(0,0,0,0);
            --card-bg: #ffffff; --card-border: #d1d1d6; --card-shadow: 0 0 0 rgba(0,0,0,0);
            --divider: #e5e5ea; --divider-strong: #d1d1d6;
            --input-bg: #ffffff; --input-border: #c7c7cc;
            --hover-bg: #eaf2fd; --hover-bg-strong: #d7e8fb; --row-hover: #f2f2f7;
            --code-bg: #f7f7f8; --code-bar-bg: #eeeef0; --field-head-bg: #f2f2f7;
            --alert-error-bg: #fdecea; --alert-error-border: #f5b7b1;
            --alert-success-bg: #eaf9ee; --alert-success-border: #b7e5c0;
        }
        html.compat[data-theme="dark"] {
            --glass-bg: #2c2c2e; --glass-bg-strong: #2c2c2e; --glass-bg-soft: #3a3a3c;
            --glass-border: #48484a; --glass-border-soft: #3a3a3c;
            --card-bg: #2c2c2e; --card-border: #48484a;
            --divider: #3a3a3c; --divider-strong: #48484a;
            --input-bg: #1c1c1e; --input-border: #48484a;
            --hover-bg: rgba(74, 168, 232, 0.18); --hover-bg-strong: rgba(74, 168, 232, 0.28); --row-hover: #3a3a3c;
            --code-bg: #1c1c1e; --code-bar-bg: #2a2a2c; --field-head-bg: #3a3a3c;
            --alert-error-bg: #3a1e1e; --alert-error-border: #6e2e2e;
            --alert-success-bg: #1a2e1e; --alert-success-border: #2e5e3a;
        }
        html.compat body { background: #f5f5f7; background-image: none; }
        html.compat[data-theme="dark"] body { background: #1c1c1e; background-image: none; }
        html.compat .navbar, html.compat .card, html.compat .cook-item, html.compat .field-block,
        html.compat .code-block, html.compat .code-contributor, html.compat .navbar-user .btn-logout,
        html.compat .navbar-user .btn-theme, html.compat .navbar-user .btn-compat, html.compat .field-toggle,
        html.compat .copy-btn, html.compat .ai-think-box, html.compat .ai-answer-box,
        html.compat button, html.compat .btn { backdrop-filter: none !important; -webkit-backdrop-filter: none !important; }
        html.compat *, html.compat *::before, html.compat *::after { animation-duration: 0.001ms !important; animation-delay: 0ms !important; transition-duration: 0.001ms !important; transition-delay: 0ms !important; }
        html.compat .navbar { background: #ffffff; border-bottom: 1px solid #d1d1d6; box-shadow: none; }
        html.compat[data-theme="dark"] .navbar { background: #2c2c2e; border-bottom-color: #3a3a3c; }
        html.compat .card { background: #ffffff; border: 1px solid #d1d1d6; box-shadow: none; transform: none !important; }
        html.compat[data-theme="dark"] .card { background: #2c2c2e; border-color: #48484a; }
        html.compat .card:hover { box-shadow: none; transform: none !important; }
        html.compat .cook-item { background: #ffffff; border: 1px solid #d1d1d6; box-shadow: none; transform: none !important; content-visibility: visible; }
        html.compat[data-theme="dark"] .cook-item { background: #2c2c2e; border-color: #48484a; }
        html.compat .cook-item:hover { box-shadow: none; transform: none !important; }
        html.compat .cook-head { background: #f7f7f8; }
        html.compat[data-theme="dark"] .cook-head { background: #3a3a3c; }
        html.compat .field-block { background: #ffffff; border: 1px solid #e5e5ea; }
        html.compat[data-theme="dark"] .field-block { background: #2c2c2e; border-color: #48484a; }
        html.compat .field-block:hover { box-shadow: none; }
        html.compat .field-head { background: #f7f7f8; }
        html.compat[data-theme="dark"] .field-head { background: #3a3a3c; }
        html.compat .code-block { background: #f7f7f8; border: 1px solid #e5e5ea; box-shadow: none; }
        html.compat[data-theme="dark"] .code-block { background: #1c1c1e; border-color: #3a3a3c; }
        html.compat .code-block:hover { box-shadow: none; }
        html.compat .code-bar { background: #eeeef0; }
        html.compat[data-theme="dark"] .code-bar { background: #2a2a2c; }
        html.compat button, html.compat .btn { background: var(--primary); border-color: var(--primary); box-shadow: none; }
        html.compat button:hover, html.compat .btn:hover { background: var(--primary-hover); border-color: var(--primary-hover); box-shadow: none; transform: none; }
        html.compat button:active, html.compat .btn:active { background: var(--primary-active); border-color: var(--primary-active); transform: none; }
        html.compat button.btn-ghost, html.compat .btn-ghost { background: #f2f2f7; border-color: #d1d1d6; color: var(--text-soft); box-shadow: none; }
        html.compat[data-theme="dark"] button.btn-ghost, html.compat[data-theme="dark"] .btn-ghost { background: #3a3a3c; border-color: #48484a; }
        html.compat button.btn-ghost:hover, html.compat .btn-ghost:hover { background: #eaf2fd; color: var(--primary); border-color: var(--primary); box-shadow: none; transform: none; }
        html.compat[data-theme="dark"] button.btn-ghost:hover, html.compat[data-theme="dark"] .btn-ghost:hover { background: rgba(74, 168, 232, 0.18); }
        html.compat .navbar-user .btn-logout, html.compat .navbar-user .btn-theme, html.compat .navbar-user .btn-compat { background: #f2f2f7; border-color: #d1d1d6; box-shadow: none; transform: none; }
        html.compat[data-theme="dark"] .navbar-user .btn-logout, html.compat[data-theme="dark"] .navbar-user .btn-theme, html.compat[data-theme="dark"] .navbar-user .btn-compat { background: #3a3a3c; border-color: #48484a; }
        html.compat .navbar-user .btn-logout:hover, html.compat .navbar-user .btn-theme:hover, html.compat .navbar-user .btn-compat:hover { box-shadow: none; transform: none; }
        html.compat input:focus, html.compat textarea:focus, html.compat select:focus { transform: none; box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.35); }
        html.compat[data-theme="dark"] input:focus, html.compat[data-theme="dark"] textarea:focus, html.compat[data-theme="dark"] select:focus { box-shadow: 0 0 0 2px rgba(74, 168, 232, 0.45); }
        html.compat .alert { box-shadow: none; }
        html.compat .rank-table thead th { background: #f2f2f7; }
        html.compat[data-theme="dark"] .rank-table thead th { background: #3a3a3c; }
        html.compat ::-webkit-scrollbar-thumb { background: #c7c7cc; border: none; background-clip: border-box; }
        html.compat[data-theme="dark"] ::-webkit-scrollbar-thumb { background: #48484a; background-clip: border-box; }
        html.compat ::-webkit-scrollbar-thumb:hover { background: #a1a1a6; }
        html.compat[data-theme="dark"] ::-webkit-scrollbar-thumb:hover { background: #5a5a5c; }
    </style>

    <script>
    (function () {
        try {
            var t = localStorage.getItem('theme');
            if (t === 'dark' || t === 'light') document.documentElement.setAttribute('data-theme', t);
            else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
            var c = localStorage.getItem('compat');
            if (c === '1') document.documentElement.classList.add('compat');
            else if (c === null) {
                if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    document.documentElement.classList.add('compat');
                }
            }
        } catch (e) {}
    })();
    </script>

    <!-- ★ Markdown 渲染库（marked.js），带本地回退 -->
    <script src="https://cdn.jsdelivr.net/npm/marked@11.1.1/marked.min.js"></script>
</head>
<body>

<div class="navbar">
  <div class="navbar-inner">
    <div class="navbar-brand">Cook&nbsp;Panel</div>
    <div class="navbar-user">
      <?php if ($is_logged_in): ?>
        <span>已登录：</span>
        <?php $my_display_color = resolve_user_color($user_info['alc'] ?? 0, $user_info['num'] ?? 0); ?>
        <strong style="color: <?php echo htmlspecialchars($my_display_color); ?>">
          <?php echo htmlspecialchars($user_info['name']); ?>
        </strong>
        <span style="color: var(--text-faint);">UID <?php echo (int)$user_info['uid']; ?></span>

        <button type="button" class="btn-compat" id="compat-toggle-btn" onclick="toggleCompat();" title="切换兼容模式">
          <span id="compat-icon">✨</span>
        </button>
        <button type="button" class="btn-theme" id="theme-toggle-btn" onclick="toggleTheme();" title="切换浅色 / 深色模式">
          <span id="theme-icon">🌙</span>
        </button>
        <form method="POST" onsubmit="return confirm('确认退出登录吗？');">
          <input type="hidden" name="action" value="logout">
          <button type="submit" class="btn-logout">退出登录</button>
        </form>
      <?php else: ?>
        <span>未登录</span>
        <button type="button" class="btn-compat" id="compat-toggle-btn" onclick="toggleCompat();" title="切换兼容模式">
          <span id="compat-icon">✨</span>
        </button>
        <button type="button" class="btn-theme" id="theme-toggle-btn" onclick="toggleTheme();" title="切换浅色 / 深色模式">
          <span id="theme-icon">🌙</span>
        </button>
      <?php endif; ?>
    </div>
  </div>
</div>

<div class="container">

  <?php echo $msg; ?>

  <?php if (!$is_logged_in): ?>
    <div class="card" style="max-width: 420px; margin: 40px auto;">
      <div class="card-title"><?php echo '登录 / 注册'; ?></div>
      <form method="POST" id="l">
          <input type="hidden" name="action" value="login">
          <div style="margin-bottom:10px;">
              <label>用户名</label>
              <input type="text" name="name" required style="width:100%;">
          </div>
          <div style="margin-bottom:14px;">
              <label>密&emsp;码</label>
              <input type="password" name="password" required style="width:100%;">
          </div>
          <div class="row">
              <button type="submit">登录</button>
              <button type="button" class="btn-ghost" onclick="ToL();">转到注册</button>
          </div>
      </form>
      <form method="POST" id="r" style="display: none">
          <input type="hidden" name="action" value="reslogin">
          <div style="margin-bottom:10px;">
              <label>用户名</label>
              <input type="text" name="name" required style="width:100%;">
          </div>
          <div style="margin-bottom:10px;">
              <label>密&emsp;码</label>
              <input type="password" name="password" required style="width:100%;">
          </div>
          <div style="margin-bottom:14px;">
              <label>确认密码</label>
              <input type="password" name="passwordag" required style="width:100%;">
          </div>
          <div class="row">
              <button type="submit">注册</button>
              <button type="button" class="btn-ghost" onclick="ToR();">转到登录</button>
          </div>
      </form>
    </div>
  <?php endif; ?>

  <?php if ($is_logged_in): ?>
    <div class="card">
      <div class="card-title"><span>我的信息</span></div>
      <?php $my_display_color = resolve_user_color($user_info['alc'] ?? 0, $user_info['num'] ?? 0); ?>
      <div class="row" style="gap: 20px;">
        <div>
          <div class="muted">UID</div>
          <div style="font-family: Consolas, Monaco, monospace;"><?php echo (int)$user_info['uid']; ?></div>
        </div>
        <div>
          <div class="muted">用户名</div>
          <div>
            <strong style="color: <?php echo htmlspecialchars($my_display_color); ?>">
              <?php echo htmlspecialchars($user_info['name']); ?>
            </strong>
            <?php if ($user_info['calling'] !== 'none' && $user_info['calling'] !== ''): ?>
              <strong style="font-size: 80%; border-radius: 6px; color: white; background-color: <?php echo htmlspecialchars($my_display_color); ?>">&ensp;<?php echo htmlspecialchars($user_info['calling']); ?>&ensp;</strong>
            <?php endif; ?>
          </div>
        </div>
        <div>
          <div class="muted">权限</div>
          <div>
            <?php
              if ($user_info['alc'] == 2) echo "管理员";
              else if ($user_info['alc'] == 1) echo "普通用户";
              else echo "受限";
            ?>
          </div>
        </div>
        <div>
          <div class="muted">贡献数</div>
          <div style="font-family: Consolas, Monaco, monospace; font-weight: 600; color: var(--primary);">
            <?php echo (int)($user_info['num'] ?? 0); ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($is_logged_in && ((int)$user_info['alc'] === 1 || (int)$user_info['alc'] === 2)): ?>
    <div class="card" id="ai-chat">
      <div class="card-title">
        <span>AI 助手（DeepSeek-R1）</span>
        <span class="sub" id="ai-quota">
          <?php if ((int)$user_info['alc'] === 2): ?>
            管理员 · 不限次数
          <?php else: ?>
            普通用户 · 每 <?php echo (int)$ai_config['window_minutes']; ?> 分钟 <?php echo (int)$ai_config['rate_limit']; ?> 次<?php echo ($ai_remaining !== null ? ' · 剩余 ' . max(0, $ai_remaining) . ' 次' : ''); ?>
          <?php endif; ?>
        </span>
      </div>

      <form method="POST" id="ai-form" style="margin-top:12px;">
        <input type="hidden" name="action" value="ai_chat">
        <div style="margin-bottom: 12px;">
          <label>向 AI 提问</label>
          <textarea name="prompt" id="ai-prompt" rows="3" required placeholder="输入你的问题，例如：用 C++ 写一个快速排序"></textarea>
        </div>
        <div class="row">
          <button type="submit" id="ai-submit">发送</button>
          <button type="button" class="btn-ghost" id="ai-stop" style="display:none;">停止生成</button>
          <span class="ai-status" id="ai-status" style="display:none;">
            <span class="ai-dot"></span><span id="ai-status-text">模型正在思考…</span>
          </span>
          <span class="muted">生成可能需要几十秒到几分钟，请耐心等待</span>
        </div>
      </form>

      <div id="ai-error" class="alert alert-error" style="display:none; margin-top:14px;"></div>

      <div id="ai-output" style="display:none; margin-top:16px;">
        <div class="muted">你问：</div>
        <div class="ai-question-box" id="ai-question"></div>

        <div id="ai-think-wrap" style="display:none; margin-top:14px;">
          <div class="muted" style="display:flex; align-items:center; gap:8px;">
            <span>💭 深度思考</span>
            <button type="button" class="field-toggle" id="ai-think-toggle">折叠</button>
          </div>
          <div class="ai-think-box" id="ai-think"></div>
        </div>

        <div id="ai-answer-wrap" style="display:none; margin-top:14px;">
          <div class="muted">✅ AI 回答：</div>
          <div class="ai-answer-box" id="ai-answer"></div>
        </div>
      </div>

      <?php if ($ai_error !== ''): ?>
        <div class="alert alert-error" style="margin-top:14px;"><?php echo htmlspecialchars($ai_error); ?></div>
      <?php endif; ?>

      <?php if ($ai_prompt !== ''): ?>
        <div style="margin-top:16px;">
          <div class="muted">你问：</div>
          <div class="ai-question-box"><?php echo nl2br(htmlspecialchars($ai_prompt)); ?></div>
        </div>
      <?php endif; ?>

      <?php if ($ai_reply !== ''): ?>
        <div style="margin-top:16px;">
          <div class="muted">AI 回答（非流式回退）：</div>
          <div class="ai-answer-box"><?php echo htmlspecialchars($ai_reply); ?></div>
        </div>
      <?php endif; ?>

      <div style="margin-top:20px; padding-top:16px; border-top: 1px dashed var(--divider-strong);">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:10px;">
          <div class="muted" style="display:flex; align-items:center; gap:8px;">
            <span>🖥️ AI 部署节点状态</span>
            <span class="muted">（页面加载时快照，非实时）</span>
          </div>
          <button type="button" class="field-toggle" id="ai-servers-toggle" onclick="toggleAiServers();">展开</button>
        </div>

        <div id="ai-servers-body" style="display:none; margin-top:10px;">
          <?php if (empty($ai_server_status)): ?>
            <div class="muted">尚未配置任何 AI 部署节点（ai_server 表为空，将回退到默认地址）。</div>
          <?php else: ?>
            <table class="rank-table">
              <thead>
                <tr>
                  <th style="width:70px; text-align:center;">UID</th>
                  <th>IP / 地址</th>
                  <th style="text-align:right;">已处理问题</th>
                  <th style="text-align:center; width:100px;">状态</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($ai_server_status as $s): ?>
                  <tr>
                    <td style="text-align:center; font-family: Consolas, Monaco, monospace;"><?php echo (int)$s['uid']; ?></td>
                    <td style="font-family: Consolas, Monaco, monospace;"><?php echo htmlspecialchars($s['ip']); ?></td>
                    <td style="text-align:right; font-family: Consolas, Monaco, monospace; font-weight:600;"><?php echo (int)$s['num']; ?></td>
                    <td style="text-align:center;">
                      <?php if ($s['state'] === 'ok'): ?>
                        <span class="ai-node-badge ai-node-ok">● 可用</span>
                      <?php elseif ($s['state'] === 'busy'): ?>
                        <span class="ai-node-badge ai-node-busy">● 占用中</span>
                      <?php else: ?>
                        <span class="ai-node-badge ai-node-down">● 不可用</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

    </div>
  <?php endif; ?>

  <?php if ($is_logged_in): ?>
  <div class="card">
    <div class="card-title">
      <span>修改密码</span>
      <span class="sub">修改成功后其他设备会被强制下线</span>
    </div>
    <form method="POST" onsubmit="return confirm('确认修改密码吗？');">
      <input type="hidden" name="action" value="change_password">
      <div class="row" style="gap: 20px; margin-bottom: 12px;">
        <div style="flex:1; min-width:220px;">
          <label>旧密码</label><br>
          <input type="password" name="old_password" required style="width:100%;">
        </div>
        <div style="flex:1; min-width:220px;">
          <label>新密码（至少 6 位）</label><br>
          <input type="password" name="new_password" required minlength="6" style="width:100%;">
        </div>
        <div style="flex:1; min-width:220px;">
          <label>确认新密码</label><br>
          <input type="password" name="new_password2" required minlength="6" style="width:100%;">
        </div>
      </div>
      <button type="submit">修改密码</button>
    </form>
  </div>
  <?php endif; ?>

  <?php
      if ($is_logged_in) {
        if ($user_info['alc'] == 1 || $user_info['alc'] == 2) {
          if ($user_info['alc'] == 2) {
              echo "<div class='card'><a href='/phpMyAdmin4.8.5/'>→ 管理员界面（phpMyAdmin）</a></div>";
          }

          $all_users = [];
          $user_stmt = $conn->query("SELECT `uid`, `name`, `calling`, `color`, `alc`, `num` FROM `user`");
          if ($user_stmt) {
              while ($u = $user_stmt->fetch_assoc()) $all_users[(int)$u['uid']] = $u;
          }

          $cook_stmt = $conn->query("SELECT * FROM `cook` ORDER BY `uid` ASC");
          if ($cook_stmt) {
              $i1 = 0;
              while ($row = $cook_stmt->fetch_assoc()) {
                  $i1++;
                $creator_uid = isset($row['UserUid']) ? (int)$row['UserUid'] : 0;
                $creator     = ($creator_uid > 0 && isset($all_users[$creator_uid])) ? $all_users[$creator_uid] : null;

                echo "<div class='cook-item'>";
                echo "  <div class='cook-head'>";
                echo "    <div class='cook-head-title'><span class='idx'>#" . $i1 . "</span>" . htmlspecialchars($row['name']);

                if ($creator) {
                    $cc = resolve_user_color($creator['alc'] ?? 0, $creator['num'] ?? 0);
                    echo "<span class='cook-creator'>";
                    echo "  <span class='creator-label'>创建者</span>";
                    echo "  <span class='creator-name' style='color: " . htmlspecialchars($cc) . "'>"
                       . htmlspecialchars($creator['name']) . "</span>";
                    if ($creator['calling'] !== 'none' && $creator['calling'] !== '') {
                        echo "<span class='creator-badge' style='background-color: " . htmlspecialchars($cc) . "'>"
                           . htmlspecialchars($creator['calling']) . "</span>";
                    }
                    echo "  <span class='rank-uid'>UID " . (int)$creator_uid . "</span>";
                    echo "</span>";
                } else if ($creator_uid > 0) {
                    echo "<span class='cook-creator'>";
                    echo "  <span class='creator-label'>创建者</span>";
                    echo "  <span class='creator-missing'>UID " . (int)$creator_uid . "（已注销）</span>";
                    echo "</span>";
                } else {
                    echo "<span class='cook-creator'>";
                    echo "  <span class='creator-label'>创建者</span>";
                    echo "  <span class='creator-missing'>未知</span>";
                    echo "</span>";
                }

                echo "    </div>";
                echo "    <button type='button' class='btn-ghost' id='but$i1' onclick='ks($i1);'>显示</button>";
                echo "  </div>";
                echo "  <div class='cook-body collapsed' id='$i1'>";

                foreach (range('A', 'Z') as $i) {
                    if (!isset($row[$i])) continue;
                    if ($row[$i] == "none" or $row[$i] == "") continue;

                    $raw = $row[$i];
                    $nl  = strpos($raw, "\n");

                    if ($nl === false) { $first = trim($raw); $code = ''; }
                    else { $first = trim(substr($raw, 0, $nl)); $code = substr($raw, $nl + 1); }

                    $contributor = null;
                    $field_title = '';

                    if ($first !== '') {
                        $sp = strpos($first, ' ');
                        if ($sp === false) {
                            if (ctype_digit($first)) $contributor = $first;
                            else $code = $raw;
                        } else {
                            $uid_part   = substr($first, 0, $sp);
                            $title_part = trim(substr($first, $sp + 1));
                            if (ctype_digit($uid_part)) { $contributor = $uid_part; $field_title = $title_part; }
                            else $code = $raw;
                        }
                    }

                    $field_key = $i1 . '_' . $i;

                    echo "<div class='field-block' id='fb_" . $field_key . "'>";
                    echo "  <div class='field-head'>";
                    echo "    <span class='field-title'>" . $i;
                    if ($field_title !== '') echo "<span class='field-title-text'>" . htmlspecialchars($field_title) . "</span>";
                    echo "    </span>";
                    echo "    <button type='button' class='field-toggle' id='ft_but_" . $field_key . "' onclick='toggleField(\"" . $field_key . "\");'>展开</button>";
                    echo "  </div>";
                    echo "  <div class='field-body' id='ft_body_" . $field_key . "'>";

                    if ($contributor !== null) {
                        $cu = isset($all_users[(int)$contributor]) ? $all_users[(int)$contributor] : null;
                        if ($cu) {
                            $cu_color = resolve_user_color($cu['alc'] ?? 0, $cu['num'] ?? 0);
                            $ch = "贡献者：<strong style='color: " . htmlspecialchars($cu_color) . "'>"
                                . htmlspecialchars($cu['name']) . "</strong>";
                            if ($cu['calling'] !== 'none' && $cu['calling'] !== '') {
                                $ch .= "&thinsp;<strong style='font-size: 80%; border-radius: 4px; color: white; background-color: "
                                     . htmlspecialchars($cu_color) . "'>&ensp;"
                                     . htmlspecialchars($cu['calling']) . "&ensp;</strong>";
                            }
                        } else {
                            $ch = "UID: " . htmlspecialchars($contributor);
                        }
                        echo "<span class='code-contributor-source' style='display:none'>" . $ch . "</span>";
                    }

                    echo "<pre><code class=\"language-cpp\">";
                    echo htmlspecialchars($code);
                    echo "</code></pre>";

                    echo "  </div>";
                    echo "</div>";
                }

                echo "  </div>";
                echo "</div>";
              }
          } else {
              echo "<div class='card'>SQL 准备失败: " . htmlspecialchars($conn->error) . "</div>";
          }

        } else {
          echo "<div class='card'>请联系管理员提权</div>";
          echo "<div class='card'>你无权限查看</div>";
        }
      } else {
          echo "<div class='card' style='text-align:center; color: var(--text-muted);'>请先登录以查看内容</div>";
      }
  ?>

  <?php if ($is_logged_in): ?>
  <div class="card" id="rank">
    <div class="card-title">
      <span>贡献排行榜</span>
      <span class="sub">按 num 降序 · 最多 50 名</span>
    </div>
    <?php if ($rank_error !== ''): ?>
      <div class="alert alert-error"><?php echo htmlspecialchars($rank_error); ?></div>
    <?php elseif (empty($rank_rows)): ?>
      <p class="muted">暂无排行数据。</p>
    <?php else: ?>
      <table class="rank-table">
        <thead>
          <tr>
            <th style="text-align:center;">排名</th>
            <th>用户</th>
            <th style="text-align:right;">贡献数</th>
          </tr>
        </thead>
        <tbody>
        <?php
          $rank_no = 0;
          foreach ($rank_rows as $rr):
            $rank_no++;
            $is_me    = ((int)$rr['uid'] === (int)$user_info['uid']);
            $no_class = '';
            if ($rank_no === 1)      $no_class = ' top1';
            else if ($rank_no === 2) $no_class = ' top2';
            else if ($rank_no === 3) $no_class = ' top3';
            $r_color    = resolve_user_color($rr['alc'] ?? 0, $rr['num'] ?? 0);
            $r_calling  = (string)($rr['calling'] ?? '');
            $r_is_admin = ((int)($rr['alc'] ?? 0) === 2);
        ?>
          <tr class="<?php echo $is_me ? 'rank-me' : ''; ?>">
            <td class="rank-no<?php echo $no_class; ?>"><?php echo $rank_no; ?></td>
            <td>
              <strong style="color: <?php echo htmlspecialchars($r_color); ?>">
                <?php echo htmlspecialchars((string)$rr['name']); ?>
              </strong>
              <?php if ($r_calling !== 'none' && $r_calling !== ''): ?>
                <strong style="font-size: 80%; border-radius: 4px; color: white; background-color: <?php echo htmlspecialchars($r_color); ?>">&ensp;<?php echo htmlspecialchars($r_calling); ?>&ensp;</strong>
              <?php endif; ?>
              <?php if ($r_is_admin): ?><span class="rank-admin-tag">管理员</span><?php endif; ?>
              <?php if ($is_me): ?><span class="rank-tag">我</span><?php endif; ?>
              <span class="rank-uid">UID <?php echo (int)$rr['uid']; ?></span>
            </td>
            <td class="rank-num"><?php echo (int)$rr['num']; ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <?php if ($is_logged_in && ($user_info['alc'] == 1 || $user_info['alc'] == 2)): ?>
  <div class="card">
    <div class="card-title">
      <span>上传 / 修改 cook 内容</span>
      <span class="sub">
        <?php if ((int)$user_info['alc'] === 2): ?>
          管理员：可覆盖任意内容
        <?php else: ?>
          普通用户：只能填充空白字段，不能覆盖
        <?php endif; ?>
      </span>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="upload">
      <div class="row" style="gap: 20px; margin-bottom: 12px;">
        <div>
          <label>目标 UID</label><br>
          <input type="number" name="uid" min="1" required style="width:120px;">
        </div>
        <div>
          <label>字段</label><br>
          <select name="field">
              <?php foreach (range('A', 'Z') as $f): ?>
                <option value="<?php echo $f; ?>"><?php echo $f; ?></option>
              <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div style="margin-bottom: 12px;">
        <label>标题（可空，显示在题号后面）</label><br>
        <input type="text" name="title" maxlength="100" style="width: 100%;" placeholder="例如：两数之和">
      </div>
      <div style="margin-bottom: 12px;">
        <label>内容</label>
        <textarea name="content" rows="8" placeholder="在此粘贴代码..."></textarea>
      </div>
      <button type="submit">上传</button>
      <?php if ((int)$user_info['alc'] !== 2): ?>
        <span class="muted" style="margin-left:10px;">提示：若目标字段已被他人填写，上传会被拒绝。</span>
      <?php endif; ?>
    </form>
  </div>
  <?php endif; ?>

  <?php if ($is_logged_in && (int)$user_info['alc'] === 2): ?>
  <div class="card">
    <div class="card-title">
      <span>新建 cook</span>
      <span class="sub" style="color:#8e44ad;">仅管理员</span>
    </div>
    <form method="POST" onsubmit="return confirm('确认新建该 cook 吗？');">
      <input type="hidden" name="action" value="create_cook">
      <div class="row" style="gap: 20px; margin-bottom: 12px;">
        <div>
          <label>新 UID</label><br>
          <input type="number" name="new_uid" min="1" required style="width:120px;">
        </div>
        <div style="flex:1; min-width:260px;">
          <label>名称</label><br>
          <input type="text" name="new_name" maxlength="100" required style="width:100%;">
        </div>
      </div>
      <button type="submit">新建</button>
    </form>
  </div>
  <?php endif; ?>

  <?php if ($is_logged_in && ($user_info['alc'] == 1 || $user_info['alc'] == 2)): ?>
    <div style="text-align:center; margin: 20px 0;">
      <button type="button" class="btn-ghost" onclick="NewWorld();">New World?</button>
    </div>
  <?php endif; ?>

</div>

<script>
function applyThemeIcon() {
  var icon = document.getElementById('theme-icon');
  if (!icon) return;
  var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
  icon.textContent = isDark ? '☀️' : '🌙';
  var btn = document.getElementById('theme-toggle-btn');
  if (btn) btn.title = isDark ? '切换到浅色模式' : '切换到深色模式';
}
function toggleTheme() {
  var html = document.documentElement;
  var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
  document.body.style.transition = 'background-color .3s cubic-bezier(.4,0,.2,1), color .3s cubic-bezier(.4,0,.2,1)';
  html.setAttribute('data-theme', next);
  try { localStorage.setItem('theme', next); } catch (e) {}
  applyThemeIcon();
}
function applyCompatIcon() {
  var icon = document.getElementById('compat-icon');
  if (!icon) return;
  var isCompat = document.documentElement.classList.contains('compat');
  icon.textContent = isCompat ? '🔲' : '✨';
  var btn = document.getElementById('compat-toggle-btn');
  if (btn) btn.title = isCompat ? '当前：兼容模式（点击切回特效）' : '当前：特效模式（点击切到兼容）';
}
function toggleCompat() {
  var html = document.documentElement;
  var willEnable = !html.classList.contains('compat');
  if (willEnable) html.classList.add('compat'); else html.classList.remove('compat');
  try { localStorage.setItem('compat', willEnable ? '1' : '0'); } catch (e) {}
  applyCompatIcon();
}
document.addEventListener('DOMContentLoaded', function () {
  applyThemeIcon();
  applyCompatIcon();
});

function toggleAiServers() {
  var body = document.getElementById('ai-servers-body');
  var btn  = document.getElementById('ai-servers-toggle');
  if (!body || !btn) return;
  var hidden = (body.style.display === 'none' || body.style.display === '');
  body.style.display = hidden ? 'block' : 'none';
  btn.textContent = hidden ? '折叠' : '展开';
}

function NewWorld() {
  var url = 'url("https://cdn.luogu.com.cn/upload/image_hosting/uds71m1q.png")';
  [document.documentElement, document.body].forEach(function (el) {
    el.style.backgroundImage      = url;
    el.style.backgroundSize       = 'cover';
    el.style.backgroundPosition   = 'center center';
    el.style.backgroundRepeat     = 'no-repeat';
    el.style.backgroundAttachment = 'fixed';
  });
}
function toggleField(key) {
  var body = document.getElementById('ft_body_' + key);
  var btn  = document.getElementById('ft_but_'  + key);
  var box  = document.getElementById('fb_'      + key);
  if (!body || !btn) return;
  var hidden = !body.classList.contains('show');
  if (hidden) {
    body.classList.add('show'); btn.textContent = '折叠';
    if (box) box.classList.add('expanded');
    if (window.CookPanel && window.CookPanel.enhanceField) window.CookPanel.enhanceField(key);
  } else {
    body.classList.remove('show'); btn.textContent = '展开';
    if (box) box.classList.remove('expanded');
  }
}

(function () {
  var KEYWORDS = new Set(['alignas','alignof','asm','auto','bool','break','case','catch','char','char16_t','char32_t','class','const','constexpr','const_cast','continue','decltype','default','delete','do','double','dynamic_cast','else','enum','explicit','export','extern','false','float','for','friend','goto','if','inline','int','long','mutable','namespace','new','noexcept','nullptr','operator','private','protected','public','register','reinterpret_cast','return','short','signed','sizeof','static','static_assert','static_cast','struct','switch','template','this','throw','true','try','typedef','typeid','typename','union','unsigned','using','virtual','void','volatile','wchar_t','while','and','or','not','xor','bitand','bitor','compl','and_eq','or_eq','xor_eq','not_eq','override','final','constinit','consteval','concept','requires']);
  var TYPES = new Set(['size_t','string','wstring','vector','map','set','unordered_map','unordered_set','pair','tuple','array','deque','list','queue','stack','priority_queue','iostream','fstream','sstream','istream','ostream','cin','cout','cerr','clog','endl','std','printf','scanf','malloc','free','memcpy','memset','INT_MAX','INT_MIN','LLONG_MAX','ULLONG_MAX','NULL']);
  function esc(s) { return s.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }
  function highlight(src) {
    var re = /(\/\/[^\n]*|\/\*[\s\S]*?\*\/)|("(?:\\.|[^"\\\n])*"|'(?:\\.|[^'\\\n])*')|(^[ \t]*#[^\n]*)|(\b(?:0[xX][0-9a-fA-F]+|\d+(?:\.\d+)?(?:[eE][+-]?\d+)?)[uUlLfF]*\b)|(\b[A-Za-z_]\w*\b)/gm;
    var out = '', last = 0, m;
    while ((m = re.exec(src)) !== null) {
      out += esc(src.slice(last, m.index));
      var cm = m[1], str = m[2], pre = m[3], num = m[4], id = m[5];
      if (cm)       out += '<span class="tok-com">'  + esc(cm)  + '</span>';
      else if (str) out += '<span class="tok-str">'  + esc(str) + '</span>';
      else if (pre) out += '<span class="tok-pre">'  + esc(pre) + '</span>';
      else if (num) out += '<span class="tok-num">'  + esc(num) + '</span>';
      else if (id) {
        if (KEYWORDS.has(id))   out += '<span class="tok-kw">'   + id + '</span>';
        else if (TYPES.has(id)) out += '<span class="tok-type">' + id + '</span>';
        else                    out += esc(id);
      } else { out += esc(m[0]); }
      last = m.index + m[0].length;
    }
    out += esc(src.slice(last));
    return out;
  }
  var DEFAULT_TIP = '复制';
  function copyText(text, btn) {
    var timer = null;
    function tip(msg, cls) {
      btn.textContent = msg;
      btn.classList.remove('ok', 'fail');
      if (cls) btn.classList.add(cls);
      if (timer) clearTimeout(timer);
      timer = setTimeout(function () { btn.textContent = DEFAULT_TIP; btn.classList.remove('ok', 'fail'); }, 1500);
    }
    function fallback() {
      var ta = document.createElement('textarea');
      ta.value = text; ta.setAttribute('readonly', '');
      ta.style.position = 'fixed'; ta.style.top = '0'; ta.style.left = '-9999px';
      document.body.appendChild(ta); ta.select(); ta.setSelectionRange(0, ta.value.length);
      var ok = false;
      try { ok = document.execCommand('copy'); } catch (e) { ok = false; }
      document.body.removeChild(ta);
      tip(ok ? '已复制' : '失败', ok ? 'ok' : 'fail');
    }
    if (navigator.clipboard && navigator.clipboard.writeText && window.isSecureContext) {
      navigator.clipboard.writeText(text).then(function () { tip('已复制', 'ok'); }, function () { fallback(); });
    } else { fallback(); }
  }
  function wrapCodeBlock(pre, contributorEl) {
    var wrap = document.createElement('div'); wrap.className = 'code-block';
    var bar = document.createElement('div'); bar.className = 'code-bar';
    var left = document.createElement('span'); left.className = 'code-bar-left';
    var lang = document.createElement('span'); lang.className = 'code-lang'; lang.textContent = 'C++';
    left.appendChild(lang);
    if (contributorEl && contributorEl.innerHTML.trim() !== '') {
      var c = document.createElement('span'); c.className = 'code-contributor'; c.innerHTML = contributorEl.innerHTML;
      left.appendChild(c);
    }
    var btn = document.createElement('button');
    btn.type = 'button'; btn.className = 'copy-btn'; btn.textContent = DEFAULT_TIP;
    btn.addEventListener('click', function () { copyText(pre.textContent, btn); });
    bar.appendChild(left); bar.appendChild(btn);
    pre.parentNode.insertBefore(wrap, pre);
    wrap.appendChild(bar); wrap.appendChild(pre);
  }
  function enhanceBlock(el) {
    if (el.getAttribute('data-highlighted') === '1') return;
    el.setAttribute('data-highlighted', '1');
    var raw = el.textContent;
    el.innerHTML = highlight(raw);
    var pre = el.parentNode;
    if (pre && pre.tagName === 'PRE') {
      var prev = pre.previousElementSibling;
      var contributorEl = (prev && prev.classList.contains('code-contributor-source')) ? prev : null;
      wrapCodeBlock(pre, contributorEl);
      if (contributorEl) contributorEl.remove();
    }
  }
  window.CookPanel = window.CookPanel || {};
  window.CookPanel.enhanceField = function (key) {
    var body = document.getElementById('ft_body_' + key);
    if (!body) return;
    body.querySelectorAll('code.language-cpp').forEach(enhanceBlock);
  };
})();

/* =========== AI 流式问答（含 Markdown 渲染） =========== */
(function () {
  var form = document.getElementById('ai-form');
  if (!form) return;
  if (!window.fetch || !window.ReadableStream || !window.TextDecoder) return;

  var promptEl   = document.getElementById('ai-prompt');
  var submitBtn  = document.getElementById('ai-submit');
  var stopBtn    = document.getElementById('ai-stop');
  var statusEl   = document.getElementById('ai-status');
  var statusText = document.getElementById('ai-status-text');
  var errBox     = document.getElementById('ai-error');
  var outBox     = document.getElementById('ai-output');
  var qBox       = document.getElementById('ai-question');
  var thinkWrap  = document.getElementById('ai-think-wrap');
  var thinkBox   = document.getElementById('ai-think');
  var thinkToggle= document.getElementById('ai-think-toggle');
  var answerWrap = document.getElementById('ai-answer-wrap');
  var answerBox  = document.getElementById('ai-answer');
  var quotaEl    = document.getElementById('ai-quota');

  var controller = null;
  var thinkTW = null;
  var gotReasoning = false, gotAnswer = false;
  var answerRaw = '';          // 累积原始 Markdown
  var renderPending = false;   // 渲染节流标记

  /* ---------- Markdown 渲染 ---------- */
  function escapeHtml(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  }
  function isMarkedReady() {
    return (typeof window.marked !== 'undefined' && window.marked && typeof window.marked.parse === 'function');
  }
  function renderMarkdown(md) {
    if (!isMarkedReady()) {
      // 回退：纯文本 + 换行
      return '<pre style="white-space:pre-wrap;word-break:break-word;">' + escapeHtml(md) + '</pre>';
    }
    try {
      // 如果流式中还有未闭合的 ```，临时给它补上，避免渲染异常
      var fenceCount = (md.match(/```/g) || []).length;
      var text = md;
      if (fenceCount % 2 === 1) text = md + '\n```';
      return window.marked.parse(text, { breaks: true, gfm: true });
    } catch (e) {
      return '<pre style="white-space:pre-wrap;word-break:break-word;">' + escapeHtml(md) + '</pre>';
    }
  }
  /* 节流渲染：requestAnimationFrame 合并同一帧的多次内容更新 */
  function scheduleRender() {
    if (renderPending) return;
    renderPending = true;
    var cb = function () {
      renderPending = false;
      if (!answerBox) return;
      answerBox.innerHTML = renderMarkdown(answerRaw);
      answerBox.scrollTop = answerBox.scrollHeight;
    };
    if (window.requestAnimationFrame) requestAnimationFrame(cb);
    else setTimeout(cb, 16);
  }

  /* ---------- 思考区打字机 ---------- */
  function makeTypewriter(el, speed, step) {
    el.textContent = '';
    var node = document.createTextNode('');
    el.appendChild(node);
    var pending = '';
    var timer = null;
    var finished = false;
    var doneCb = null;
    function tick() {
      if (pending.length > 0) {
        node.appendData(pending.slice(0, step));
        pending = pending.slice(step);
        el.scrollTop = el.scrollHeight;
      } else if (finished) {
        clearInterval(timer); timer = null;
        el.classList.remove('ai-typing');
        if (doneCb) { var f = doneCb; doneCb = null; f(); }
      }
    }
    function ensure() { if (!timer) timer = setInterval(tick, speed); }
    return {
      el: el,
      push: function (t) { if (!t) return; pending += t; el.classList.add('ai-typing'); ensure(); },
      finish: function (cb) { finished = true; doneCb = cb || null; ensure(); },
      flushNow: function () { if (pending) { node.appendData(pending); pending = ''; } if (timer) { clearInterval(timer); timer = null; } el.classList.remove('ai-typing'); }
    };
  }

  function showError(msg) { if (!errBox) return; errBox.textContent = msg || ''; errBox.style.display = msg ? 'block' : 'none'; }
  function setBusy(b) {
    submitBtn.disabled = b;
    promptEl.disabled  = b;
    stopBtn.style.display = b ? 'inline-block' : 'none';
    statusEl.style.display = b ? 'inline-flex' : 'none';
  }
  function setStatus(txt) { if (statusText) statusText.textContent = txt; }
  function updateQuota(info) {
    if (!quotaEl) return;
    if (info.is_admin) { quotaEl.textContent = '管理员 · 不限次数'; return; }
    if (typeof info.rate_limit === 'number') {
      var rem = (typeof info.remaining === 'number') ? info.remaining : 0;
      quotaEl.textContent = '普通用户 · 剩余 ' + rem + ' 次';
    }
  }

  function startStream(prompt) {
    showError('');
    qBox.textContent = prompt;
    outBox.style.display = 'block';
    thinkWrap.style.display  = 'none';
    answerWrap.style.display = 'none';
    thinkBox.textContent  = '';
    answerBox.innerHTML   = '';
    gotReasoning = false; gotAnswer = false;
    answerRaw = '';
    thinkTW = makeTypewriter(thinkBox, 8, 4);
    setStatus('模型正在思考…');
    setBusy(true);
    controller = new AbortController();

    var body = new URLSearchParams();
    body.append('action', 'ai_chat_stream');
    body.append('prompt', prompt);

    fetch(window.location.href, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: body.toString(),
      credentials: 'same-origin',
      signal: controller.signal,
      // 关键：让浏览器也不做输入缓冲
      cache: 'no-store'
    }).then(function (res) {
      if (!res.ok) throw new Error('HTTP ' + res.status);
      if (!res.body) throw new Error('浏览器不支持流式响应');
      var reader  = res.body.getReader();
      var decoder = new TextDecoder('utf-8');
      var buf     = '';
      function pump() {
        return reader.read().then(function (r) {
          if (r.done) { finishStream(false); return; }
          buf += decoder.decode(r.value, { stream: true });
          var idx;
          while ((idx = buf.indexOf('\n\n')) >= 0) {
            var raw = buf.slice(0, idx);
            buf = buf.slice(idx + 2);
            handleEvent(raw);
          }
          return pump();
        });
      }
      return pump();
    }).catch(function (err) {
      if (err && err.name === 'AbortError') { finishStream(true); return; }
      showError('请求失败：' + (err && err.message ? err.message : err));
      finishStream(true);
    });
  }

  function handleEvent(raw) {
    if (!raw) return;
    // 跳过 SSE 注释行（以 : 开头且非 data: 的）
    var lines = raw.split('\n');
    var data = '';
    for (var i = 0; i < lines.length; i++) {
      var l = lines[i];
      if (l.indexOf(':') === 0 && l.indexOf('data:') !== 0) continue;
      if (l.indexOf('data:') === 0) data += l.slice(5).trim();
    }
    if (!data) return;
    var obj;
    try { obj = JSON.parse(data); } catch (e) { return; }

    if (obj.type === 'reasoning') {
      if (!gotReasoning) {
        gotReasoning = true;
        thinkWrap.style.display = 'block';
      }
      setStatus('正在深度思考…');
      thinkTW.push(obj.content || '');
    } else if (obj.type === 'content') {
      if (!gotAnswer) { gotAnswer = true; answerWrap.style.display = 'block'; }
      setStatus('正在输出答案…');
      answerRaw += (obj.content || '');
      // ★ Markdown 增量渲染（rAF 节流），模型吐一个字就跟着渲染
      scheduleRender();
    } else if (obj.type === 'error') {
      showError(obj.message || 'AI 服务错误');
    } else if (obj.type === 'done') {
      updateQuota(obj);
      finishStream(false);
    }
  }

  var streamClosed = false;
  function finishStream(aborted) {
    if (streamClosed) return;
    streamClosed = true;
    setBusy(false);
    controller = null;
    if (thinkTW) thinkTW.finish();
    // 最后再做一次完整 markdown 渲染（确保无未闭合代码块）
    if (answerBox) {
      answerBox.innerHTML = renderMarkdown(answerRaw);
      answerBox.scrollTop = answerBox.scrollHeight;
    }
    if (aborted) {
      if (thinkTW) thinkTW.flushNow();
      setStatus('已停止');
    }
    setTimeout(function () { if (statusEl) statusEl.style.display = 'none'; }, 400);
  }

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    if (submitBtn.disabled) return;
    var prompt = (promptEl.value || '').trim();
    if (!prompt) { showError('请输入你要问的问题'); promptEl.focus(); return; }
    streamClosed = false;
    startStream(prompt);
  });

  stopBtn.addEventListener('click', function () {
    if (controller) { try { controller.abort(); } catch (e) {} }
    if (thinkTW) thinkTW.flushNow();
    finishStream(true);
  });

  thinkToggle.addEventListener('click', function () {
    var hidden = (thinkBox.style.display === 'none');
    thinkBox.style.display = hidden ? '' : 'none';
    thinkToggle.textContent = hidden ? '折叠' : '展开';
  });

  promptEl.addEventListener('keydown', function (e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      e.preventDefault();
      form.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
    }
  });
})();

function ToL() { document.getElementById('l').style.display = "none"; document.getElementById('r').style.display = "block"; }
function ToR() { document.getElementById('l').style.display = "block"; document.getElementById('r').style.display = "none"; }
function ks(i) {
  var j = document.getElementById(i.toString());
  var k = document.getElementById("but" + i.toString());
  if (j.classList.contains('collapsed')) { j.classList.remove('collapsed'); k.innerHTML = '折叠'; }
  else { j.classList.add('collapsed'); k.innerHTML = '显示'; }
}
</script>
</body>
</html>