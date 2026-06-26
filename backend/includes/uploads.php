<?php

/*
====================================================
  IMAGE UPLOAD SYSTEM (IONOS + SUPABASE COMPATIBLE)
====================================================
*/

function createDirectories() {

    $dirs = [
        UPLOAD_DIR . 'products/',
        UPLOAD_DIR . 'products/original/',
        UPLOAD_DIR . 'products/large/',
        UPLOAD_DIR . 'products/thumb/',
        UPLOAD_DIR . 'categories/',
        UPLOAD_DIR . 'banners/'
    ];

    foreach ($dirs as $dir) {
        if (!file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

/*
====================================================
  RESIZE IMAGE (GD LIB REQUIRED)
====================================================
*/
function resizeImage($source, $destination, $width, $height, $quality = 80) {

    $info = getimagesize($source);
    if (!$info) return false;

    list($origWidth, $origHeight) = $info;
    $mime = $info['mime'];

    $ratio = min($width / $origWidth, $height / $origHeight);
    $newWidth = (int)($origWidth * $ratio);
    $newHeight = (int)($origHeight * $ratio);

    switch ($mime) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($source);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($source);
            break;
        case 'image/gif':
            $sourceImage = imagecreatefromgif($source);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($source);
            break;
        default:
            return false;
    }

    if (!$sourceImage) return false;

    $newImage = imagecreatetruecolor($newWidth, $newHeight);

    // preserve transparency for PNG
    if ($mime === 'image/png') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }

    imagecopyresampled(
        $newImage,
        $sourceImage,
        0, 0, 0, 0,
        $newWidth,
        $newHeight,
        $origWidth,
        $origHeight
    );

    imagewebp($newImage, $destination, $quality);

    imagedestroy($sourceImage);
    imagedestroy($newImage);

    return true;
}

/*
====================================================
  PRODUCT IMAGE UPLOAD
====================================================
*/
function uploadProductImage($file, $productId = null) {

    createDirectories();

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Eroare la upload'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Fișier prea mare'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Tip fișier nepermis'];
    }

    $filename = uniqid() . '_' . bin2hex(random_bytes(6)) . '.webp';

    $originalPath = UPLOAD_DIR . 'products/original/' . $filename;
    $largePath    = UPLOAD_DIR . 'products/large/' . $filename;
    $thumbPath    = UPLOAD_DIR . 'products/thumb/' . $filename;

    $tempFile = $file['tmp_name'];

    if (!resizeImage($tempFile, $originalPath, 1920, 1920, 85)) {
        return ['success' => false, 'message' => 'Eroare procesare imagine'];
    }

    resizeImage($tempFile, $largePath, 800, 800, 80);
    resizeImage($tempFile, $thumbPath, 300, 300, 75);

    return [
        'success' => true,
        'path' => 'uploads/products/original/' . $filename,
        'filename' => $filename
    ];
}

/*
====================================================
  CATEGORY IMAGE UPLOAD
====================================================
*/
function uploadCategoryImage($file) {

    createDirectories();

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Eroare upload'];
    }

    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Fișier prea mare'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Tip fișier nepermis'];
    }

    $filename = uniqid() . '_' . bin2hex(random_bytes(6)) . '.webp';

    $path = UPLOAD_DIR . 'categories/' . $filename;

    if (!resizeImage($file['tmp_name'], $path, 800, 800, 85)) {
        return ['success' => false, 'message' => 'Eroare procesare imagine'];
    }

    return [
        'success' => true,
        'path' => 'uploads/categories/' . $filename
    ];
}

/*
====================================================
  DELETE IMAGE (SAFE)
====================================================
*/
function deleteImage($path) {

    $fullPath = __DIR__ . '/../' . $path;

    if (file_exists($fullPath)) {
        unlink($fullPath);
    }

    $dirs = ['original', 'large', 'thumb'];

    foreach ($dirs as $dir) {

        $derivedPath = str_replace('/original/', '/' . $dir . '/', $path);
        $fullDerived = __DIR__ . '/../' . $derivedPath;

        if (file_exists($fullDerived)) {
            unlink($fullDerived);
        }
    }
}
?>