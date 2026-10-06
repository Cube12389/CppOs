<?php
// core/Storage.php

class Storage {
    private static $settings = null;

    public static function init() {
        if (self::$settings === null) {
            $conn = connect_db();
            $result = $conn->query("SELECT * FROM system_settings");
            $settings = [];
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            }
            self::$settings = $settings;
            $conn->close();
        }
    }

    public static function getSetting($key) {
        if (self::$settings === null) self::init();
        return self::$settings[$key] ?? '';
    }

    public static function upload($file, $category = 'message') {
        if (self::$settings === null) self::init();
        
        $driver = self::$settings['storage_driver'] ?? 'local';
        
        // 1. Check Size Limits
        $is_video = strpos($file['type'], 'video/') === 0;
        $is_audio = strpos($file['type'], 'audio/') === 0;
        
        $limit_mb = $is_video ? (self::$settings['upload_video_limit'] ?? 50) : (self::$settings['upload_image_limit'] ?? 5);
        // Audio uses same limit as video or separate? Let's use video limit for now as audio can be large
        if($is_audio) $limit_mb = 20; 

        $limit_bytes = $limit_mb * 1024 * 1024;
        
        if ($file['size'] > $limit_bytes) {
            throw new Exception("文件大小超过限制 ({$limit_mb}MB)");
        }

        switch ($driver) {
            case 's3':
                return self::uploadS3($file);
            case 'local':
            default:
                if ($is_video) {
                     return self::uploadLocalVideo($file);
                }
                if ($is_audio) {
                     return self::uploadLocalAudio($file);
                }
                return self::uploadLocal($file, $category);
        }
    }

    // --- DRIVERS ---

    private static function uploadLocal($file, $category = 'message') {
        $uploadDir = ($category === 'avatar') ? AVATAR_DIR : MESSAGE_IMAGE_DIR;
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        // Robust MIME Type Check
        $mime_type = self::getMimeType($file['tmp_name']);
        
        if (strpos($mime_type, 'image/') !== 0) {
            // Fallback: Check extension if mime detection failed to identify image
            // Only strictly enforce checks if we are sure about the MIME type
            // But for safety, let's rely on extension mapping if MIME is 'application/octet-stream'
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            if (!in_array(strtolower($ext), ['jpg','jpeg','png','gif','webp'])) {
                 throw new Exception("Invalid file type: $mime_type");
            }
        }

        $ext_map = [
            'image/jpeg'=>'jpg', 'image/png'=>'png', 'image/gif'=>'gif', 'image/webp'=>'webp',
            'application/octet-stream' => pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg'
        ];
        $ext = $ext_map[$mime_type] ?? pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (self::compressImage($file['tmp_name'], $targetPath, 80)) {
             return $filename;
        }
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $filename; 
        }
        throw new Exception("Local upload failed");
    }

    private static function uploadLocalVideo($file) {
        $uploadDir = MESSAGE_VIDEO_DIR;
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $mime_type = self::getMimeType($file['tmp_name']);
        
        $allowed = ['video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/quicktime' => 'mov'];
        
        // If detection fails, trust extension for now (fallback)
        if ($mime_type === 'application/octet-stream') {
             $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
             if (in_array($ext, ['mp4','webm','mov'])) {
                 $mime_type = array_search($ext, $allowed);
             }
        }

        if (!array_key_exists($mime_type, $allowed)) {
            throw new Exception("Unsupported video type: $mime_type");
        }
        
        $ext = $allowed[$mime_type];
        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $filename;
        }
        throw new Exception("Video upload failed");
    }

    private static function uploadLocalAudio($file) {
        $uploadDir = MESSAGE_AUDIO_DIR;
        if (!file_exists($uploadDir)) mkdir($uploadDir, 0777, true);

        $mime_type = self::getMimeType($file['tmp_name']);
        
        // Relaxed Allowed Types for Voice Messages
        $allowed = [
            'audio/mpeg' => 'mp3', 
            'audio/mp3' => 'mp3',
            'audio/wav' => 'wav', 
            'audio/x-wav' => 'wav',
            'audio/webm' => 'webm', 
            'audio/ogg' => 'ogg',
            'audio/mp4' => 'm4a',
            'video/mp4' => 'm4a' // Some browsers record audio as mp4 container
        ];

        // Fallback for octet-stream
        if ($mime_type === 'application/octet-stream') {
             $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
             if (in_array($ext, array_values($allowed))) {
                 // rough check
                 $mime_type = 'audio/' . $ext;
             }
        }

        // Just check extension if check strict fails, mainly for webm/mp3
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, array_values($allowed))) {
             // Let's be lenient correctly
             if ($ext != 'webm' && $ext != 'mp3' && $ext != 'wav') {
                 throw new Exception("不支持的音频格式");
             }
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $ext;
        $targetPath = $uploadDir . $filename;
        
        if (move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $filename; 
        }
        throw new Exception("Audio upload failed");
    }

    private static function uploadS3($file) {
        $endpoint = self::getSetting('s3_endpoint');
        $region = self::getSetting('s3_region') ?: 'auto';
        $accessKey = self::getSetting('s3_access_key');
        $secretKey = self::getSetting('s3_secret_key');
        $bucket = self::getSetting('s3_bucket');

        if (!$endpoint || !$accessKey || !$secretKey || !$bucket) {
            throw new Exception("S3 configuration missing");
        }

        // 1. Prepare File Info
        $mime_type = self::getMimeType($file['tmp_name']);
        
        // Extension
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        if(!$ext) {
             $map = [
                 'image/jpeg'=>'jpg', 'image/png'=>'png', 'image/gif'=>'gif', 
                 'video/mp4'=>'mp4', 'video/webm'=>'webm', 'video/quicktime'=>'mov'
             ];
             $ext = $map[$mime_type] ?? 'bin';
        }

        // Object Key (dates/YYYYMMDD/hash.ext)
        $objectKey = 'dates/' . date('Ymd') . '/' . bin2hex(random_bytes(16)) . '.' . $ext;
        $content = file_get_contents($file['tmp_name']);
        $content_sha256 = hash('sha256', $content);
        
        // 2. AWS Signature V4
        $service = 's3';
        $timestamp = time();
        $date_long = gmdate('Ymd\THis\Z', $timestamp);
        $date_short = gmdate('Ymd', $timestamp);
        
        // Host Parsing (Handle https:// prefix)
        $parsedUrl = parse_url($endpoint);
        $host = $parsedUrl['host'] ?? $endpoint;
        $scheme = $parsedUrl['scheme'] ?? 'https';
        
        // Construct Host: bucket.endpoint or endpoint/bucket (Virtual Host addressing preferred usually)
        // For R2/COS: Use virtual host style: bucket.endpoint
        $requestHost = "{$bucket}.{$host}";
        $url = "{$scheme}://{$requestHost}/{$objectKey}";
        
        // Prepare Headers
        $headers = [
            "host" => $requestHost,
            "x-amz-content-sha256" => $content_sha256,
            "x-amz-date" => $date_long,
            "content-type" => $mime_type,
        ];
        
        // Canonical Request
        $canonical_uri = '/' . $objectKey;
        $canonical_querystring = '';
        $canonical_headers = "content-type:{$mime_type}\nhost:{$requestHost}\nx-amz-content-sha256:{$content_sha256}\nx-amz-date:{$date_long}\n";
        $signed_headers = "content-type;host;x-amz-content-sha256;x-amz-date";
        
        $canonical_request = "PUT\n" . 
                             $canonical_uri . "\n" . 
                             $canonical_querystring . "\n" . 
                             $canonical_headers . "\n" . 
                             $signed_headers . "\n" . 
                             $content_sha256;

        // String to Sign
        $credential_scope = "{$date_short}/{$region}/{$service}/aws4_request";
        $string_to_sign = "AWS4-HMAC-SHA256\n" . 
                          $date_long . "\n" . 
                          $credential_scope . "\n" . 
                          hash('sha256', $canonical_request);
        
        // Signing Key
        $kSecret = "AWS4" . $secretKey;
        $kDate = hash_hmac('sha256', $date_short, $kSecret, true);
        $kRegion = hash_hmac('sha256', $region, $kDate, true);
        $kService = hash_hmac('sha256', $service, $kRegion, true);
        $kSigning = hash_hmac('sha256', 'aws4_request', $kService, true);
        
        // Signature
        $signature = hash_hmac('sha256', $string_to_sign, $kSigning);
        
        $authorization = "AWS4-HMAC-SHA256 Credential={$accessKey}/{$credential_scope}, SignedHeaders={$signed_headers}, Signature={$signature}";
        
        // 3. Execution (Curl)
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $curl_headers = [
             "Authorization: {$authorization}",
             "Content-Type: {$mime_type}",
             "Host: {$requestHost}",
             "x-amz-date: {$date_long}",
             "x-amz-content-sha256: {$content_sha256}"
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curl_headers);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $url;
        }
        throw new Exception("S3 Upload Failed: HTTP $httpCode");
    }

    // --- HELPER: MIME Detection (Robust) ---
    private static function getMimeType($file) {
        // 1. Try fileinfo (Best)
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file);
            if ($mime) return $mime;
        }

        // 2. Try mime_content_type (Function)
        if (function_exists('mime_content_type')) {
            $mime = mime_content_type($file);
            if ($mime) return $mime;
        }

        // 3. Try image_type_to_mime_type (Images only)
        if (function_exists('exif_imagetype')) {
            $type = exif_imagetype($file);
            if ($type && function_exists('image_type_to_mime_type')) {
                return image_type_to_mime_type($type);
            }
        }
        
        // 4. Fallback (e.g. getimagesize - slow but works for images)
        if (function_exists('getimagesize')) {
            $info = @getimagesize($file);
            if ($info && isset($info['mime'])) return $info['mime'];
        }

        return 'application/octet-stream';
    }

    // --- HELPER: Image Compression ---
    private static function compressImage($source, $destination, $quality) {
        // 1. Priority: Imagick (ImageMagick)
        if (class_exists('Imagick')) {
            try {
                $imagick = new Imagick($source);
                
                // Resize if too large (max width 1920)
                $maxWidth = 1920;
                if ($imagick->getImageWidth() > $maxWidth) {
                    $imagick->resizeImage($maxWidth, 0, Imagick::FILTER_LANCZOS, 1);
                }

                // Compress & Optimize
                $imagick->setImageCompressionQuality($quality);
                $imagick->stripImage(); // Remove metadata (EXIF)
                
                $imagick->writeImage($destination);
                $imagick->clear();
                $imagick->destroy();
                return true;
            } catch (Exception $e) {
                // If Imagick fails, fall through to GD
            }
        }

        // 2. Fallback: GD Library
        // Prevent fatal error if GD is missing
        if (!extension_loaded('gd')) {
            return false; 
        }

        $info = getimagesize($source);
        $mime = $info['mime'];

        switch ($mime) {
            case 'image/jpeg': 
                $image = imagecreatefromjpeg($source); 
                break;
            case 'image/gif': 
                $image = imagecreatefromgif($source); 
                break;
            case 'image/png': 
                $image = imagecreatefrompng($source); 
                break;
            default: 
                return false; 
        }

        // Resize if too large (max width 1920)
        $maxWidth = 1920;
        $width = imagesx($image);
        $height = imagesy($image);
        
        if ($width > $maxWidth) {
            $newWidth = $maxWidth;
            $newHeight = floor($height * ($maxWidth / $width));
            $tmp = imagecreatetruecolor($newWidth, $newHeight);
            
            // Handle transparency
            if ($mime == 'image/png' || $mime == 'image/gif') {
                imagecolortransparent($tmp, imagecolorallocatealpha($tmp, 0, 0, 0, 127));
                imagealphablending($tmp, false);
                imagesavealpha($tmp, true);
            }
            
            imagecopyresampled($tmp, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $tmp;
        }

        // Save
        if ($mime == 'image/jpeg') {
            imagejpeg($image, $destination, $quality);
        } elseif ($mime == 'image/png') {
            // PNG quality is 0-9 (0 is no compression). 
            // We want compression, so ~6/7. 
            // Mapping 0-100 quality to 0-9: (100 - 80) / 10 * 9 ?? No. 
            // just use 7.
            imagepng($image, $destination, 7);
        } elseif ($mime == 'image/gif') {
            imagegif($image, $destination);
        }

        imagedestroy($image);
        return true;
    }
}
?>
