<?php defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Extends some missing functions in the FileHelper.
 */
class FileExtendedHelper
{
    /**
     * @param bool $createSubfolder
     *
     * @throws Exception
     *
     * @return string
     */
    public function getTempSandboxDirectory($createSubfolder)
    {
        $fh = Loader::helper('file');
        /* @var $fh FileHelper */
        $dir = $fh->getTemporaryDirectory();
        if ((!is_string($dir)) || ($dir === '')) {
            throw new Exception(t('Unable to retrieve the temporary directory.'));
        }
        $dir = str_replace(DIRECTORY_SEPARATOR, '/', $dir).'/integrated_localization';
        if (!is_dir($dir)) {
            @mkdir($dir);
            if (!is_dir($dir)) {
                throw new Exception(t('Unable to create a temporary directory.'));
            }
        }
        $file = $dir.'/index.html';
        if (!file_exists($file)) {
            if (@file_put_contents($file, '') === false) {
                throw new Exception(t('Error initializing a temporary directory.'));
            }
        }
        $file = $dir.'/.htaccess';
        if (!file_exists($file)) {
            if (@file_put_contents(
                $file,
                <<<EOT
Order deny,allow
Deny from all
php_flag engine off
EOT
                ) === false) {
                throw new Exception(t('Error initializing a temporary directory.'));
            }
        }
        if ($createSubfolder) {
            $dir2 = '';
            while (($dir2 === '') || file_exists($dir2)) {
                $dir2 = $dir.'/'.date('YmdHis').'-'.md5(microtime().mt_rand());
            }
            @mkdir($dir2, DIRECTORY_PERMISSIONS_MODE, true);
            if (!is_dir($dir2)) {
                throw new Exception(t('Unable to create the directory %s', $dir2));
            }
            $dir = $dir2;
        }

        return $dir;
    }
    /**
     * Removes a file or a directory (even if not empty).
     *
     * @param string $path
     *
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
    /**
     * @param string $fieldName
     * @param bool $allowEmpty
     *
     * @throws Exception
     *
     * @return array|null
     */
    public function getUploadedFile($fieldName, $allowEmpty = false)
    {
        if (!(isset($_FILES) && isset($_FILES[$fieldName]) && is_array($_FILES[$fieldName]))) {
            return;
        }
        $file = $_FILES[$fieldName];
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
                throw new Exception(t('The uploaded file exceeds the upload_max_filesize directive in php.ini.'));
            case UPLOAD_ERR_FORM_SIZE:
                throw new Exception(t('The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.'));
            case UPLOAD_ERR_PARTIAL:
                throw new Exception(t('The uploaded file was only partially uploaded.'));
            case UPLOAD_ERR_NO_FILE:
                throw new Exception(t('No file was uploaded.'));
            case UPLOAD_ERR_NO_TMP_DIR:
                throw new Exception(t('Missing a temporary folder.'));
            case UPLOAD_ERR_CANT_WRITE:
                throw new Exception(t('Failed to write file to disk.'));
            case UPLOAD_ERR_EXTENSION:
                throw new Exception(t('A PHP extension stopped the file upload.'));
            default:
                throw new Exception(t('Unknown error occurred during file upload (%s).', $file['error']));
        }
        unset($file['error']);
        if ((!$allowEmpty) && ($file['size'] <= 0)) {
            throw new Exception(t('The uploaded file is empty.'));
        }

        return $file;
    }
}
