<?php defined('C5_EXECUTE') or die('Access Denied.');

/**
 * Extracts useful info from packages
 */
class PackageInspectorHelper
{
    /**
     * Look for a package controller
     * @param string $path The full path to the controller, the directory containing the controller.php file, or a folder that contains a single sub-folder for the package
     * @return string Returns an empty string if the controlles hasn't been found, the full path to the directory containing the controller otherwise
     */
    public function getControllerDirectory($path)
    {
        $result = '';
        if (is_string($path) && ($path !== '')) {
            $realPath = @realpath($path);
            if ($realPath !== false) {
                if (is_file($realPath)) {
                    $result = str_replace(DIRECTORY_SEPARATOR, '/', dirname($realPath));
                } elseif (is_dir($realPath)) {
                    $realPath = str_replace(DIRECTORY_SEPARATOR, '/', $realPath);
                    if (@is_file($realPath.'/controller.php')) {
                        $result = $realPath;
                    } else {
                        $contents = @scandir($path);
                        if (is_array($contents)) {
                            $contents = array_values(array_filter($contents, function ($item) {
                                return $item[0] !== '.';
                            }));
                            if ((count($contents) === 1) && is_file($realPath.'/'.$contents[0].'/controller.php')) {
                                $result = $realPath.'/'.$contents[0];
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Inspect a package to retrieve its controller path, its handle and its version
     * @param string $path The path to the package controller.php file
     * @throws Exception Throws an Exception if we were not able to retrieve all the data.
     * @return array Array keys are: handle, version
     */
    public function getPackageInfo($path)
    {
        if (!is_file($path)) {
            throw new Exception(t('Unable to find the file %s', $path));
        }
        if (!is_readable($path)) {
            throw new Exception(t("The file '%s' is not readable", $path));
        }
        $controllerContents = @file_get_contents($path);
        if ($controllerContents == false) {
            throw new Exception(t('Error reading the contents of the file %s', $path));
        }
        $readTokens = @token_get_all($controllerContents);
        if (!is_array($readTokens)) {
            throw new Exception(t('Error analyzing the PHP contents of the file %s', $path));
        }
        // Normalize tokens representation, remove comments and multiple spaces
        $tokens = array();
        foreach ($readTokens as $token) {
            if (is_array($token)) {
                $token = array(
                    'id' => $token[0],
                    '_' => token_name($token[0]),
                    'text' => $token[1],
                    'line' => $token[2],
                );
            } else {
                $token = array(
                   'id' => null,
                   'text' => $token,
                   'line' => null,
                );
            }
            switch ($token['id']) {
                case T_COMMENT:
                case T_DOC_COMMENT:
                    break;
                case T_WHITESPACE:
                    $n = count($tokens);
                    if (($n === 0) || ($tokens[$n - 1]['id'] !== T_WHITESPACE)) {
                        $tokens[] = $token;
                    }
                    break;
                default:
                    $tokens[] = $token;
            }
        }
        $pkgHandle = $this->lookVar('$pkgHandle', $tokens);
        $pkgVersion = $this->lookVar('$pkgVersion', $tokens);
        if (!(isset($pkgHandle)  && isset($pkgVersion))) {
            throw new Exception(t('Unable to detect the package handle and/or version'));
        }
        if (is_string($pkgHandle)) {
            $pkgHandle = trim($pkgHandle);
        }
        if ((!is_string($pkgHandle)) || ($pkgHandle === '')) {
            throw new Exception(t('The package handle is not valid'));
        }
        if (is_int($pkgVersion) || is_float($pkgVersion)) {
            $pkgVersion = (string) $pkgVersion;
        } else {
            if (is_string($pkgVersion)) {
                $pkgVersion = trim($pkgVersion);
            }
            if ((!is_string($pkgVersion)) || ($pkgVersion === '')) {
                throw new Exception(t('The package version is not valid'));
            }
        }

        return array(
            'handle' => $pkgHandle,
            'version' => $pkgVersion,
        );
    }
    private function lookVar($name, $tokens)
    {
        $n = count($tokens);
        foreach ($tokens as $varIndex => $varToken) {
            if (($varToken['id'] === T_VARIABLE) && ($varToken['text'] === $name)) {
                if (($varIndex > 1) && ($tokens[$varIndex - 1]['id'] === T_WHITESPACE)) {
                    switch ($tokens[$varIndex - 2]['id']) {
                        case T_PROTECTED:
                        case T_PUBLIC:
                        case T_VAR:
                            $iNext = $varIndex + 1;
                            if (($iNext < $n - 1) && ($tokens[$iNext]['id'] === T_WHITESPACE)) {
                                $iNext++;
                            }
                            if (($iNext < $n - 1) && ($tokens[$iNext]['text'] === '=')) {
                                $iNext++;
                                if (($iNext < $n - 1) && ($tokens[$iNext]['id'] === T_WHITESPACE)) {
                                    $iNext++;
                                }
                                if ($iNext < $n) {
                                    switch ($tokens[$iNext]['id']) {
                                        case T_CONSTANT_ENCAPSED_STRING:
                                            if (preg_match('/^["\'](.*)["\']$/', $tokens[$iNext]['text'], $matches)) {
                                                return stripslashes((string) $matches[1]);
                                            }
                                            break;
                                        case T_DNUMBER:
                                            return (float) $tokens[$iNext]['text'];
                                        case T_LNUMBER:
                                            return (int) $tokens[$iNext]['text'];
                                    }
                                }
                            }
                            break;
                    }
                }
            }
        }

        return;
    }
}
