<?php

require __DIR__.'/Common.php';

$iniFiles = getIniFiles();
printf('%-20s: %d%s', 'Ini files used', count($iniFiles), PHP_EOL);

reportTmpIni($iniFiles, false);
reportTmpIni($iniFiles, true);

function reportTmpIni(array $iniFiles, $merged){

    $content = getIniContent($iniFiles, $merged);
    $size = strlen($content);
    $entries = parse_ini_string($content);

    $format = '%-20s: %d bytes (entries: %d)';
    $title = 'Ini concat' . ($merged ? ' (merged)' : '');

    $text = sprintf($format, $title, $size, count($entries));
    print($text.PHP_EOL);
}
