<?php defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Extends some missing functions in the FileHelper
 */
class FileExtendedHelper
{
    /**
     * Removes a file or a directory (even if not empty)
     * @param string $path
     * @return boolean
     */
    public function deleteFromFileSystem($path)
    {
        if (is_dir($path)) {
            $allChildrenDeleted = true;
            foreach (scandir($path) as $child) {
                switch ($child) {
                    case '.':
                    case '..':
                        break;
                    default:
                        if ($this->deleteFromFileSystem($path.'/'.$child) === false) {
                            $allChildrenDeleted = false;
                        }
                        break;
                }
            }
            if ($allChildrenDeleted) {
                $result = @rmdir($path) ? true : false;
            } else {
                $result = false;
            }
        } elseif (is_file($path)) {
            $result = @unlink($path) ? true : false;
        } else {
            $result = false;
        }

        return $result;
    }
}
