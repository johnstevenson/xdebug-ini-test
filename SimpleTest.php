<?php

require __DIR__.'/Common.php';

if ($start = getenv('INI_TEST_START')) {
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

function writeTmpIni(array $iniFiles)
{
    $merged = in_array('--merge-inis', $_SERVER['argv']);
    $start = microtime(true);
    $content = getIniContent($iniFiles, $merged);
    $time = round((microtime(true) - $start) * 1000);

    $format = '%-20s: %4d ms (files: %d)';
    $title = 'Inis read' . ($merged ? '/merged' : '');
    $text = sprintf($format, $title, $time, count($iniFiles));
    print($text.PHP_EOL);

    $filename = $merged ? 'tmp-merged.ini' : 'tmp.ini';
    $tmpIni = __DIR__.DIRECTORY_SEPARATOR.$filename;
    file_put_contents($tmpIni, $content);

    return $tmpIni;
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
