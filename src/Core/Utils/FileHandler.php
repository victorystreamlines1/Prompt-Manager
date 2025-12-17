<?php
/**
 * ============================================
 * File Handler Utility
 * ============================================
 * 
 * PURPOSE:
 * Handles file operations including upload,
 * validation, and management.
 * 
 * INPUTS:
 * - File arrays from $_FILES
 * - File paths
 * 
 * OUTPUTS:
 * - Upload results
 * - File information
 * 
 * SIDE EFFECTS:
 * - Creates/deletes files
 * - Creates directories
 * 
 * ============================================
 */

namespace App\Core\Utils;

class FileHandler
{
    private string $uploadPath;
    private array $allowedExtensions;
    private int $maxSize;

    /**
     * Initialize file handler.
     * 
     * @param string $uploadPath Base upload directory
     * @param array $allowedExtensions Allowed file extensions
     * @param int $maxSize Maximum file size in bytes
     */
    public function __construct(
        string $uploadPath = 'uploads',
        array $allowedExtensions = [],
        int $maxSize = 52428800 // 50MB
    ) {
        $this->uploadPath = rtrim($uploadPath, '/\\');
        $this->allowedExtensions = $allowedExtensions ?: [
            'stl', 'obj', 'fbx', 'glb', 'gltf',
            'html', 'htm', 'php', 'txt', 'json', 'csv',
            'png', 'jpg', 'jpeg', 'gif', 'svg'
        ];
        $this->maxSize = $maxSize;
        
        $this->ensureDirectory($this->uploadPath);
    }

    /**
     * Upload a file from $_FILES array.
     * 
     * @param array $file File array from $_FILES
     * @param string|null $customName Custom filename (without extension)
     * @param string|null $subDir Subdirectory within upload path
     * @return array Result with 'success', 'message', 'filepath', etc.
     */
    public function upload(array $file, ?string $customName = null, ?string $subDir = null): array
    {
        // Validate file array
        if (!isset($file['tmp_name']) || !isset($file['name'])) {
            return $this->error('Invalid file data');
        }

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $this->error($this->getUploadErrorMessage($file['error']));
        }

        // Get file info
        $originalName = $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $size = $file['size'];

        // Validate extension
        if (!in_array($extension, $this->allowedExtensions)) {
            return $this->error("File type '{$extension}' is not allowed");
        }

        // Validate size
        if ($size > $this->maxSize) {
            $maxMB = round($this->maxSize / 1048576, 1);
            return $this->error("File exceeds maximum size of {$maxMB}MB");
        }

        // Generate filename
        $filename = $customName 
            ? $this->sanitizeFilename($customName) . '.' . $extension
            : $this->generateUniqueFilename($originalName);

        // Build target path
        $targetDir = $subDir 
            ? $this->uploadPath . '/' . trim($subDir, '/\\')
            : $this->uploadPath;
        
        $this->ensureDirectory($targetDir);
        $targetPath = $targetDir . '/' . $filename;

        // Move uploaded file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            return $this->error('Failed to move uploaded file');
        }

        return [
            'success' => true,
            'message' => 'File uploaded successfully',
            'filename' => $filename,
            'filepath' => $targetPath,
            'original_name' => $originalName,
            'extension' => $extension,
            'size' => $size,
            'mime_type' => mime_content_type($targetPath) ?: $file['type'],
        ];
    }

    /**
     * Delete a file.
     * 
     * @param string $filepath Path to file
     * @return bool True if deleted
     */
    public function delete(string $filepath): bool
    {
        if (file_exists($filepath) && is_file($filepath)) {
            return unlink($filepath);
        }
        return false;
    }

    /**
     * Check if file exists.
     * 
     * @param string $filepath
     * @return bool
     */
    public function exists(string $filepath): bool
    {
        return file_exists($filepath) && is_file($filepath);
    }

    /**
     * Get file info.
     * 
     * @param string $filepath
     * @return array|null
     */
    public function getInfo(string $filepath): ?array
    {
        if (!$this->exists($filepath)) {
            return null;
        }

        return [
            'filename' => basename($filepath),
            'extension' => pathinfo($filepath, PATHINFO_EXTENSION),
            'size' => filesize($filepath),
            'modified' => filemtime($filepath),
            'mime_type' => mime_content_type($filepath),
        ];
    }

    /**
     * List files in directory.
     * 
     * @param string|null $subDir Subdirectory
     * @param array $extensions Filter by extensions
     * @return array File list
     */
    public function listFiles(?string $subDir = null, array $extensions = []): array
    {
        $dir = $subDir 
            ? $this->uploadPath . '/' . trim($subDir, '/\\')
            : $this->uploadPath;

        if (!is_dir($dir)) {
            return [];
        }

        $files = [];
        foreach (scandir($dir) as $file) {
            if ($file === '.' || $file === '..') continue;
            
            $filepath = $dir . '/' . $file;
            if (!is_file($filepath)) continue;

            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (!empty($extensions) && !in_array($ext, $extensions)) continue;

            $files[] = [
                'filename' => $file,
                'filepath' => $filepath,
                'extension' => $ext,
                'size' => filesize($filepath),
                'modified' => filemtime($filepath),
            ];
        }

        return $files;
    }

    /**
     * Read file contents.
     * 
     * @param string $filepath
     * @return string|null
     */
    public function read(string $filepath): ?string
    {
        if (!$this->exists($filepath)) {
            return null;
        }
        return file_get_contents($filepath);
    }

    /**
     * Write content to file.
     * 
     * @param string $filepath
     * @param string $content
     * @return bool
     */
    public function write(string $filepath, string $content): bool
    {
        $dir = dirname($filepath);
        $this->ensureDirectory($dir);
        return file_put_contents($filepath, $content) !== false;
    }

    /**
     * Ensure directory exists.
     * 
     * @param string $path
     * @return bool
     */
    public function ensureDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            return mkdir($path, 0755, true);
        }
        return true;
    }

    /**
     * Generate unique filename.
     * 
     * @param string $originalName
     * @return string
     */
    private function generateUniqueFilename(string $originalName): string
    {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $safeName = $this->sanitizeFilename($baseName);
        $timestamp = time();
        
        return "{$timestamp}_{$safeName}.{$extension}";
    }

    /**
     * Sanitize filename.
     * 
     * @param string $filename
     * @return string
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove path components
        $filename = basename($filename);
        
        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);
        
        // Remove non-alphanumeric characters except underscore and dash
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '', $filename);
        
        // Limit length
        return substr($filename, 0, 100) ?: 'file';
    }

    /**
     * Get upload error message.
     * 
     * @param int $errorCode
     * @return string
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        return match($errorCode) {
            UPLOAD_ERR_INI_SIZE   => 'File exceeds server maximum size',
            UPLOAD_ERR_FORM_SIZE  => 'File exceeds form maximum size',
            UPLOAD_ERR_PARTIAL    => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE    => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION  => 'Upload stopped by extension',
            default               => 'Unknown upload error',
        };
    }

    /**
     * Create error response array.
     * 
     * @param string $message
     * @return array
     */
    private function error(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
        ];
    }

    /**
     * Get allowed extensions.
     * 
     * @return array
     */
    public function getAllowedExtensions(): array
    {
        return $this->allowedExtensions;
    }

    /**
     * Set allowed extensions.
     * 
     * @param array $extensions
     */
    public function setAllowedExtensions(array $extensions): void
    {
        $this->allowedExtensions = $extensions;
    }
}

