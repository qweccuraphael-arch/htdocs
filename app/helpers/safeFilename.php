<?php
/**
 * Generate safe filename for storage.
 */
function safeFilename(string $filename): string {
    $safe = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    return preg_replace('/_+/', '_', trim($safe, '_'));
}
?>

