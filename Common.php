<?php

function getIniFiles()
{
    $iniFiles = array(strval(php_ini_loaded_file()));

    if ($scanned = php_ini_scanned_files()) {
        $iniFiles = array_merge($iniFiles, array_map('trim', explode(',', $scanned)));
    }

    if (empty($iniFiles[0])) {
        array_shift($iniFiles);
    }

    return $iniFiles;
}

function getIniContent(array $iniFiles, $merged)
{
    $content = '';
    $regex = '/^\s*(zend_extension\s*=.*xdebug.*)$/mi';

    foreach ($iniFiles as $file) {
        $data = preg_replace($regex, ';$1', file_get_contents($file));
        $content .= $data.PHP_EOL;
    }

    if ($merged) {
        $loaded = ini_get_all(null, false);
        $config = parse_ini_string($content);
        $content .= mergeLoadedConfig($loaded, $config);
    } else {
        $content .= 'allow_url_fopen='.ini_get('allow_url_fopen').PHP_EOL;
        $content .= 'disable_functions="'.ini_get('disable_functions').'"'.PHP_EOL;
        $content .= 'memory_limit='.ini_get('memory_limit').PHP_EOL;
    }

    if (defined('PHP_WINDOWS_VERSION_BUILD')) {
        // Work-around for PHP windows bug, see Composer issue #6052
        $content .= 'opcache.enable_cli=0'.PHP_EOL;
    }

    return $content;
}

function mergeLoadedConfig(array $loadedConfig, array $iniConfig)
{
    $content = '';

    foreach ($loadedConfig as $name => $value) {
        // Values will either be null, string or array (HHVM only)
        if (!is_string($value) || strpos($name, 'xdebug') === 0) {
            continue;
        }

        if (!isset($iniConfig[$name]) || $iniConfig[$name] !== $value) {
            // Based on main -d option handling in php-src/sapi/cli/php_cli.c
            if ($value && !ctype_alnum($value)) {
                $value = '"'.str_replace('"', '\\"', $value).'"';
            }
            $content .= $name.'='.$value.PHP_EOL;
        }
    }

    return $content;
}
