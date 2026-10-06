<?php
/**
 * ============================================================
 *  简易 Web FTP / 文件下载站（PHP 单文件，开源可自由修改）
 * ============================================================
 *  功能：
 *    - 浏览并下载「当前工作目录」下的全部文件与文件夹
 *    - 文件夹可一键打包成 zip 下载（需 Zip 扩展）
 *    - 支持断点续传（HTTP Range）
 *    - 无需密码，公开访问
 *    - 以 . 开头的隐藏文件 / 文件夹：不显示、不可下载
 *    - 防目录穿越，无法访问共享根目录之外的任何内容
 *    - 页脚显示 GitHub 仓库链接
 *    - 顶部提供「AI大模型专属文件夹」快捷入口
 *
 *  用法：把本文件放到你要分享的目录里，浏览器访问即可。
 *  警告：本程序无任何鉴权，任何人都能下载全部内容，
 *        请勿直接放在公网服务器上，除非你确实想公开这些文件。
 * ============================================================
 */
declare(strict_types=1);

/* ======================= 配置区 ======================= */
$ROOT        = __DIR__;      // 共享根目录，默认 = 本文件所在目录，可改成绝对路径
$SHOW_HIDDEN = false;        // true = 显示以 . 开头的隐藏文件/文件夹
$ALLOW_ZIP   = true;         // true = 允许把文件夹打包成 zip 下载
$TITLE       = '文件下载';    // 站点标题
$CHUNK       = 262144;       // 输出分块大小（256KB）

$GITHUB_URL  = 'https://github.com/Cube12389/CppOs/'; // GitHub 仓库链接
$AI_DIR      = 'llama-b11384-bin-win-cpu-x64/AI';      // AI 大模型专属文件夹（相对共享根目录）
/* ====================================================== */


/* ----------------------- 工具函数 ----------------------- */

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** 是否是以 . 开头的隐藏项 */
function is_hidden(string $name): bool
{
    return $name !== '' && $name[0] === '.';
}

/** 相对路径中是否含有隐藏层级 */
function path_has_hidden(string $rel, bool $showHidden): bool
{
    if ($showHidden) return false;
    foreach (explode('/', $rel) as $seg) {
        if ($seg !== '' && $seg[0] === '.') return true;
    }
    return false;
}

/** 把相对路径按段做 URL 编码 */
function url_path(string $rel): string
{
    if ($rel === '') return '';
    return implode('/', array_map('rawurlencode', explode('/', $rel)));
}

/** 人类可读的文件大小 */
function fmt_size(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
    $i = 0;
    $v = (float)$bytes;
    while ($v >= 1024 && $i < count($units) - 1) {
        $v /= 1024;
        $i++;
    }
    return ($i === 0 ? (string)$bytes : number_format($v, 2)) . ' ' . $units[$i];
}

/**
 * 把用户传入的相对路径解析为真实路径，并做越界防护。
 * 返回 ['real' => 绝对路径, 'rel' => 规范化后的相对路径]，非法则返回 null。
 */
function resolve_rel(string $root, string $rel, bool $showHidden): ?array
{
    // 去掉空字节 / 统一分隔符（同时兼容 Windows 的反斜杠）
    $rel = str_replace(["\0", '\\'], ['', '/'], $rel);

    $parts = [];
    foreach (explode('/', $rel) as $seg) {
        if ($seg === '' || $seg === '.') continue;
        if ($seg === '..') { array_pop($parts); continue; }   // 不允许跳出根目录
        if (!$showHidden && is_hidden($seg)) return null;      // 隐藏路径直接拒绝
        $parts[] = $seg;
    }

    $rootReal = realpath($root);
    if ($rootReal === false) return null;

    $relPath = implode('/', $parts);
    $full = $relPath === ''
        ? $rootReal
        : $rootReal . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relPath);

    $real = realpath($full);
    if ($real === false) return null;

    // 必须位于根目录之内（含根目录自身）
    if ($real !== $rootReal) {
        $prefix = $rootReal . DIRECTORY_SEPARATOR;
        if (strncmp($real, $prefix, strlen($prefix)) !== 0) return null;
    }

    return ['real' => $real, 'rel' => $relPath];
}

/** 发送单个文件（支持断点续传） */
function send_file(string $file, int $chunk): void
{
    set_time_limit(0);

    if (!is_readable($file)) {
        http_response_code(403);
        header('Content-Type: text/plain; charset=utf-8');
        exit('403 文件不可读');
    }

    $size = (int)filesize($file);
    $name = basename($file);
    $disposition = 'attachment; filename="' . rawurlencode($name) . '"; '
                 . "filename*=UTF-8''" . rawurlencode($name);

    $start = 0;
    $end   = $size - 1;

    // ---- Range 处理 ----
    $range = isset($_SERVER['HTTP_RANGE']) ? trim((string)$_SERVER['HTTP_RANGE']) : '';
    if ($range !== '' && preg_match('/^bytes=(\d*)-(\d*)$/i', $range, $m)) {
        if ($m[1] !== '') {
            $start = (int)$m[1];
            if ($m[2] !== '') $end = (int)$m[2];
        } elseif ($m[2] !== '') {          // bytes=-N 取末尾 N 字节
            $start = max(0, $size - (int)$m[2]);
        }

        if ($start > $end || ($size > 0 && $start >= $size)) {
            http_response_code(416);
            header("Content-Range: bytes */{$size}");
            exit;
        }
        $end = min($end, $size - 1);
        http_response_code(206);
        header("Content-Range: bytes {$start}-{$end}/{$size}");
    }

    $length = $size > 0 ? ($end - $start + 1) : 0;

    header('Content-Type: application/octet-stream');
    header('Content-Disposition: ' . $disposition);
    header('Content-Length: ' . $length);
    header('Accept-Ranges: bytes');
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=0');

    while (ob_get_level() > 0) ob_end_clean();

    if ($length === 0) exit;

    $fp = fopen($file, 'rb');
    if ($fp === false) exit;

    fseek($fp, $start);
    $remaining = $length;
    while ($remaining > 0 && !feof($fp)) {
        $read = $remaining > $chunk ? $chunk : $remaining;
        $data = fread($fp, $read);
        if ($data === false || $data === '') break;
        echo $data;
        $remaining -= strlen($data);
        flush();
    }
    fclose($fp);
    exit;
}

/** 把整个目录打包成 zip 并发送 */
function send_zip(string $dir, bool $showHidden, int $chunk): void
{
    if (!class_exists('ZipArchive')) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        exit('服务器未安装 Zip 扩展，无法打包下载，请进入文件夹逐个下载。');
    }

    set_time_limit(0);

    $tmp = tempnam(sys_get_temp_dir(), 'webftp_');
    if ($tmp === false) {
        http_response_code(500);
        exit('临时文件创建失败');
    }

    $zip = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        @unlink($tmp);
        http_response_code(500);
        exit('无法创建压缩包');
    }

    $base = basename($dir) !== '' ? basename($dir) : 'download';

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($it as $file) {
        /** @var SplFileInfo $file */
        $sub = str_replace('\\', '/', $it->getSubPathname());
        if ($sub === '' || path_has_hidden($sub, $showHidden)) continue;

        $entry = $base . '/' . $sub;

        if ($file->isDir()) {
            $zip->addEmptyDir($entry);
        } elseif ($file->isFile() && $file->isReadable()) {
            $zip->addFile($file->getPathname(), $entry);
        }
    }
    $zip->close();

    $zipName = $base . '.zip';
    $size    = (int)filesize($tmp);

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . rawurlencode($zipName) . '"; '
         . "filename*=UTF-8''" . rawurlencode($zipName));
    header('Content-Length: ' . $size);
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: no-store');

    while (ob_get_level() > 0) ob_end_clean();

    readfile($tmp);
    @unlink($tmp);
    exit;
}

/* ----------------------- 初始化 ----------------------- */

$ROOT_REAL = realpath($ROOT);
if ($ROOT_REAL === false || !is_dir($ROOT_REAL)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('配置错误：共享根目录不存在');
}
$ROOT = $ROOT_REAL;

$action = isset($_GET['action']) ? (string)$_GET['action'] : 'list';
$pathIn = isset($_GET['path'])   ? (string)$_GET['path']   : '';

$target = resolve_rel($ROOT, $pathIn, $SHOW_HIDDEN);
if ($target === null) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    exit('<!doctype html><meta charset="utf-8"><title>404</title>'
       . '<h1>404</h1><p>路径不存在或不可访问。</p><p><a href="?">返回根目录</a></p>');
}

/* ----------------------- 下载分支 ----------------------- */

if ($action === 'download') {
    $real = $target['real'];

    if (is_file($real)) {
        send_file($real, $CHUNK);
    }

    if (is_dir($real)) {
        if (!$ALLOW_ZIP) {
            http_response_code(403);
            header('Content-Type: text/plain; charset=utf-8');
            exit('已禁用文件夹打包下载');
        }
        send_zip($real, $SHOW_HIDDEN, $CHUNK);
    }

    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    exit('404');
}

// 浏览时若路径指向文件，直接跳到下载
if (is_file($target['real'])) {
    header('Location: ?action=download&path=' . url_path($target['rel']));
    exit;
}

/* ----------------------- 目录列表 ----------------------- */

$dir   = $target['real'];
$items = [];
$totalSize = 0;

$dh = @opendir($dir);
if ($dh !== false) {
    while (($name = readdir($dh)) !== false) {
        if ($name === '.' || $name === '..') continue;
        if (!$SHOW_HIDDEN && is_hidden($name)) continue;   // 隐藏项不显示

        $full  = $dir . DIRECTORY_SEPARATOR . $name;
        $isDir = is_dir($full);
        $size  = $isDir ? -1 : (int)@filesize($full);

        if (!$isDir) $totalSize += max(0, $size);

        $items[] = [
            'name'  => $name,
            'dir'   => $isDir,
            'size'  => $size,
            'mtime' => (int)@filemtime($full),
            'rel'   => ($target['rel'] === '' ? '' : $target['rel'] . '/') . $name,
        ];
    }
    closedir($dh);
}

// 文件夹优先，其次按名称自然排序
usort($items, static function (array $a, array $b): int {
    if ($a['dir'] !== $b['dir']) return $a['dir'] ? -1 : 1;
    return strnatcasecmp($a['name'], $b['name']);
});

// 面包屑
$crumbs = [['name' => '根目录', 'path' => '']];
$acc = '';
foreach (explode('/', $target['rel']) as $seg) {
    if ($seg === '') continue;
    $acc = $acc === '' ? $seg : $acc . '/' . $seg;
    $crumbs[] = ['name' => $seg, 'path' => $acc];
}

$canZip  = $ALLOW_ZIP && class_exists('ZipArchive');
$selfUrl = basename(__FILE__);

// AI 专属文件夹是否存在
$aiExists = resolve_rel($ROOT, $AI_DIR, $SHOW_HIDDEN) !== null;

header('Content-Type: text/html; charset=utf-8');
header('X-Content-Type-Options: nosniff');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title><?= h($TITLE) ?> - /<?= h($target['rel']) ?></title>
<style>
  *{box-sizing:border-box}
  body{margin:0;background:#f5f6f8;color:#222;
       font:14px/1.6 -apple-system,BlinkMacSystemFont,"Segoe UI","PingFang SC","Microsoft YaHei",sans-serif}
  .wrap{max-width:980px;margin:0 auto;padding:24px 16px 64px}
  h1{font-size:20px;margin:0 0 4px}
  .sub{color:#777;font-size:13px;margin:0 0 16px}
  .quick{background:#eef4ff;border:1px solid #cfe0ff;border-radius:10px;
         padding:12px 16px;margin-bottom:14px;display:flex;flex-wrap:wrap;
         align-items:center;gap:10px}
  .quick .label{color:#3b5bdb;font-weight:600;font-size:13px}
  .quick a{display:inline-block;padding:4px 12px;background:#2563eb;color:#fff;
           border-radius:6px;text-decoration:none;font-size:13px}
  .quick a:hover{background:#1d4ed8}
  .quick .miss{color:#b45309;font-size:12px}
  .crumbs{background:#fff;border:1px solid #e5e7eb;border-radius:10px;
          padding:10px 14px;margin-bottom:14px;word-break:break-all}
  .crumbs a{color:#2563eb;text-decoration:none}
  .crumbs a:hover{text-decoration:underline}
  .crumbs .sep{color:#bbb;margin:0 6px}
  table{width:100%;border-collapse:collapse;background:#fff;
        border:1px solid #e5e7eb;border-radius:10px;overflow:hidden}
  th,td{padding:10px 14px;text-align:left;border-bottom:1px solid #f0f1f3;font-size:14px}
  th{background:#fafbfc;color:#666;font-weight:600;font-size:12px;
     letter-spacing:.05em;text-transform:uppercase}
  tr:last-child td{border-bottom:none}
  tr:hover td{background:#fafbff}
  td.name a{color:#1f2937;text-decoration:none;display:inline-flex;align-items:center;gap:8px}
  td.name a:hover{color:#2563eb}
  .icon{width:18px;text-align:center;flex:none}
  td.size,td.time{color:#888;font-size:13px;white-space:nowrap;width:1%}
  td.act{text-align:right;white-space:nowrap;width:1%}
  .btn{display:inline-block;padding:3px 10px;border:1px solid #d7dae0;border-radius:6px;
       color:#333;text-decoration:none;font-size:12px;background:#fff;margin-left:6px}
  .btn:hover{border-color:#2563eb;color:#2563eb}
  .empty{padding:40px;text-align:center;color:#999;background:#fff;
         border:1px solid #e5e7eb;border-radius:10px}
  footer{margin-top:20px;color:#aaa;font-size:12px;text-align:center}
  footer a{color:#2563eb;text-decoration:none}
  footer a:hover{text-decoration:underline}
  @media(max-width:600px){td.time,th.time{display:none}}
</style>
</head>
<body>
<div class="wrap">

  <h1>📁 <?= h($TITLE) ?></h1>
  <p class="sub">
    <?= count($items) ?> 个项目 · 共 <?= h(fmt_size($totalSize)) ?>
    <?php if ($canZip): ?>
      · <a href="?action=download&amp;path=<?= h(url_path($target['rel'])) ?>">打包下载当前目录 (zip)</a>
    <?php endif; ?>
  </p>

  <!-- AI 大模型专属文件夹入口 -->
  <div class="quick">
    <span class="label">🤖 AI 大模型专属文件夹 - 建议同步下载所有模型通用的 <a href='?action=download&amp;path=llama-b11384-bin-win-cpu-x64/AI服务启动脚本 - 所有模型.cmd'>多模型启动脚本</a></span>
    <?php if ($aiExists): ?>
      <a href="?path=<?= h(url_path($AI_DIR)) ?>">进入 llama-b11384-bin-win-cpu-x64 / AI</a>
    <?php else: ?>
      <span class="miss">未找到目录：<?= h($AI_DIR) ?></span>
    <?php endif; ?>
  </div>

  <div class="crumbs">
    <?php foreach ($crumbs as $i => $c): ?>
      <?php if ($i > 0): ?><span class="sep">/</span><?php endif; ?>
      <?php if ($i === count($crumbs) - 1): ?>
        <strong><?= h($c['name']) ?></strong>
      <?php else: ?>
        <a href="?path=<?= h(url_path($c['path'])) ?>"><?= h($c['name']) ?></a>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>

  <?php if (!$items): ?>
    <div class="empty">这个目录是空的</div>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>名称</th>
        <th class="size">大小</th>
        <th class="time">修改时间</th>
        <th class="act">操作</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
      <tr>
        <td class="name">
          <?php if ($it['dir']): ?>
            <a href="?path=<?= h(url_path($it['rel'])) ?>">
              <span class="icon">📁</span><?= h($it['name']) ?>
            </a>
          <?php else: ?>
            <a href="?action=download&amp;path=<?= h(url_path($it['rel'])) ?>">
              <span class="icon">📄</span><?= h($it['name']) ?>
            </a>
          <?php endif; ?>
        </td>
        <td class="size"><?= $it['dir'] ? '—' : h(fmt_size($it['size'])) ?></td>
        <td class="time"><?= $it['mtime'] ? h(date('Y-m-d H:i', $it['mtime'])) : '—' ?></td>
        <td class="act">
          <?php if ($it['dir']): ?>
            <?php if ($canZip): ?>
              <a class="btn" href="?action=download&amp;path=<?= h(url_path($it['rel'])) ?>">zip</a>
            <?php endif; ?>
            <a class="btn" href="?path=<?= h(url_path($it['rel'])) ?>">打开</a>
          <?php else: ?>
            <a class="btn" href="?action=download&amp;path=<?= h(url_path($it['rel'])) ?>">下载</a>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <footer>
    Powered by PHP Web FTP · <?= h(date('Y-m-d H:i')) ?> ·
    <a href="<?= h($GITHUB_URL) ?>" target="_blank" rel="noopener noreferrer">GitHub 仓库</a>
  </footer>
</div>
</body>
</html>