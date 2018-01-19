#!/bin/bash

echo
echo Simple Test
echo -----------
echo

COUNTER=0
while [  $COUNTER -lt 3 ]; do
    php SimpleTests.php
    let COUNTER=COUNTER+1
    echo
done

echo
echo Simple Test Merge
echo -----------------
echo

COUNTER=0
while [  $COUNTER -lt 3 ]; do
    php SimpleTests.php --merge-inis
    let COUNTER=COUNTER+1
    echo
done

export COMPOSER_ALLOW_SUPERUSER=1
echo
echo Composer Test
echo -------------
echo

COUNTER=0
while [  $COUNTER -lt 3 ]; do
    php composer-xdebug.phar --version
    let COUNTER=COUNTER+1
    echo
done

echo
echo Composer Test Merge
echo -------------------
echo

COUNTER=0
while [  $COUNTER -lt 3 ]; do
    php composer-xdebug.phar --version --merge-inis
    let COUNTER=COUNTER+1
    echo
done
