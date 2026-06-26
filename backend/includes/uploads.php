<?php
require_once __DIR__ . '/config.php';

/*
====================================================
  IMAGE UPLOAD SYSTEM
  - Folosește GD (webp) dacă e disponibil,
    altfel salvează fișierul original.
====================================================
*/

function ensureUploadDirs() {
    $dirs = [
        UPLOAD_DIR,
        UPLOAD_DIR . 'products/',
        UPLOAD_DIR . 'categories/',
        UPLOAD_DIR . 'banners/',
    ];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }
}

function gdAvailable() {
    return function_exists('imagecreatetruecolor') && function_exists('imagewebp');
}

/**
 * Redimensionează (păstrând proporțiile) și salvează ca webp.
 */
function resizeToWebp($source, $destination, $maxW, $maxH, $quality = 82) {
    if (!gdAvailable()) return false;

    $info = @getimagesize($source);
    if (!$info) return false;

    [$origW, $origH] = $info;
    $mime = $info['mime'];

    switch ($mime) {
        case 'image/jpeg': $img = @imagecreatefromjpeg($source); break;
        case 'image/png':  $img = @imagecreatefrompng($source);  break;
        case 'image/gif':  $img = @imagecreatefromgif($source);  break;
        case 'image/webp': $img = @imagecreatefromwebp($source); break;
        default: return false;
    }
    if (!$img) return false;

    $ratio = min($maxW / $origW, $maxH / $origH, 1);
    $newW = max(1, (int)($origW * $ratio));
    $newH = max(1, (int)($origH * $ratio));

    $new = imagecreatetruecolor($newW, $newH);
    imagealphablending($new, false);
    imagesavealpha($new, true);
    $transparent = imagecolorallocatealpha($new, 255, 255, 255, 127);
    imagefilledrectangle($new, 0, 0, $newW, $newH, $transparent);

    imagecopyresampled($new, $img, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

    $ok = imagewebp($new, $destination, $quality);

    imagedestroy($img);
    imagedestroy($new);

    return $ok;
}

function validateUpload($file) {
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return 'Eroare la upload';
    }
    if ($file['size'] > MAX_FILE_SIZE) {
        return 'Fișier prea mare';
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        return 'Tip fișier nepermis';
    }
    $info = @getimagesize($file['tmp_name']);
    if (!$info) {
        return 'Fișierul nu este o imagine validă';
    }
    return null; // ok
}

/**
 * Salvează o imagine într-un subfolder (products|categories|banners).
 * Returnează ['success'=>bool, 'path'=>'uploads/.../file.ext', 'message'=>...]
 */
function saveImage($file, $subfolder, $maxW = 1000, $maxH = 1000) {
    ensureUploadDirs();

    $err = validateUpload($file);
    if ($err) {
        return ['success' => false, 'message' => $err];
    }

    $base = uniqid('', true) . '_' . bin2hex(random_bytes(4));
    $targetDir = UPLOAD_DIR . $subfolder . '/';

    if (gdAvailable()) {
        $filename = $base . '.webp';
        $dest = $targetDir . $filename;
        if (resizeToWebp($file['tmp_name'], $dest, $maxW, $maxH)) {
            return ['success' => true, 'path' => 'uploads/' . $subfolder . '/' . $filename];
        }
        // fallthrough la salvare original dacă eșuează
    }

    // Fallback: salvăm fișierul original
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = $base . '.' . $ext;
    $dest = $targetDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest) || @copy($file['tmp_name'], $dest)) {
        return ['success' => true, 'path' => 'uploads/' . $subfolder . '/' . $filename];
    }

    return ['success' => false, 'message' => 'Nu s-a putut salva imaginea'];
}

function uploadProductImage($file) {
    return saveImage($file, 'products', 1200, 1200);
}

function uploadCategoryImage($file) {
    return saveImage($file, 'categories', 800, 800);
}

function deleteImageFile($relativePath) {
    if (!$relativePath) return;
    $full = __DIR__ . '/../' . ltrim($relativePath, '/');
    // siguranță: rămânem în interiorul folderului uploads
    if (strpos(realpath(dirname($full)) ?: '', realpath(UPLOAD_DIR) ?: 'XX') === 0) {
        if (is_file($full)) @unlink($full);
    }
}
