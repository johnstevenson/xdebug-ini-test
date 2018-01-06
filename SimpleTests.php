<?php

$start = getenv('INI_TEST_START');

if ($start) {
    // Restarted, so print out elapsed time
    $end =  microtime(true);
    $elapsed = round(($end - $start) * 1000);
    printf('%-20s: %4d ms%s', 'Process restarted', $elapsed, PHP_EOL);
} else {
    // Perform a restart
    putenv('INI_TEST_START='.strval(microtime(true)));
    putenv('PHP_INI_SCAN_DIR=');

    $iniFiles = getIniFiles();
    $tmpIni = writeTmpIni($iniFiles);

    $command = getCommand($_SERVER['argv'], $tmpIni);
    passthru($command, $exitCode);
    exit($exitCode);
}

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

function writeTmpIni(array $iniFiles)
{
    $start = microtime(true);
    $merged = false;
    $content = '';
    $regex = '/^\s*(zend_extension\s*=.*xdebug.*)$/mi';

    if (in_array('--merge-inis', $_SERVER['argv'])) {
        $config = array();
        $merged = true;

        foreach ($iniFiles as $file) {
            $data = preg_replace($regex, ';$1', file_get_contents($file));
            $config = array_merge($config, parse_ini_string($data));
            $content .= $data.PHP_EOL;
        }

        $loaded = ini_get_all(null, false);
        $content .= mergeLoadedConfig($loaded, $config);

    } else {

        foreach ($iniFiles as $file) {
            $data = preg_replace($regex, ';$1', file_get_contents($file));
            $content .= $data.PHP_EOL;
        }

        $content .= 'allow_url_fopen='.ini_get('allow_url_fopen').PHP_EOL;
        $content .= 'disable_functions="'.ini_get('disable_functions').'"'.PHP_EOL;
        $content .= 'memory_limit='.ini_get('memory_limit').PHP_EOL;
    }

    if (defined('PHP_WINDOWS_VERSION_BUILD')) {
        // Work-around for PHP windows bug, see issue #6052
        $content .= 'opcache.enable_cli=0'.PHP_EOL;
    }

    $time = round((microtime(true) - $start) * 1000);
    $format = '%-20s: %4d ms (files: %d, entries: %d)';
    $title = 'Inis read' . ($merged ? '/merged' : '');
    $entries = parse_ini_string($content);

    $text = sprintf($format, $title, $time, count($iniFiles), count($entries));
    print($text.PHP_EOL);

    $filename = $merged ? 'tmp-merged.ini' : 'tmp.ini';
    $tmpIni = __DIR__.DIRECTORY_SEPARATOR.$filename;
    file_put_contents($tmpIni, $content);

    return $tmpIni;
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

function getCommand($args, $tmpIni)
{
    $args = array_merge(array(PHP_BINARY, '-c', $tmpIni), $args);

    $cmd = escape(array_shift($args), true, true);
    foreach ($args as $arg) {
        $cmd .= ' '.escape($arg);
    }

    return $cmd;
}

function escape($arg, $meta = true, $module = false)
{
    if (!defined('PHP_WINDOWS_VERSION_BUILD')) {
        return escapeshellarg($arg);
    }

    $quote = strpbrk($arg, " \t") !== false || $arg === '';
    $arg = preg_replace('/(\\\\*)"/', '$1$1\\"', $arg, -1, $dquotes);

    if ($meta) {
        $meta = $dquotes || preg_match('/%[^%]+%/', $arg);

        if (!$meta) {
            $quote = $quote || strpbrk($arg, '^&|<>()') !== false;
        } elseif ($module && !$dquotes && $quote) {
            $meta = false;
        }
    }

    if ($quote) {
        $arg = preg_replace('/(\\\\*)$/', '$1$1', $arg);
        $arg = '"'.$arg.'"';
    }

    if ($meta) {
        $arg = preg_replace('/(["^&|<>()%])/', '^$1', $arg);
    }

    return $arg;
}
