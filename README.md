# xdebug-ini-test
A temporary repo to test the creation of a single php.ini file for the [composer/xdebug-handler](https://github.com/composer/xdebug-handler) restart.

See the test results from a Travis CI run: https://travis-ci.org/johnstevenson/xdebug-ini-test

## About
By default xdebug-handler concatenates all ini files into a temporary php.ini, with references to the xdebug extension commented out. This works well, but not if configuration has been set on the command-line (using the `-d` option) because these values are not passed to the restarted process.

Because there is currently no way to obtain the PHP command-line arguments and as a result of reported Composer issues, the following existing ini values are now appended to the temporary ini:

- memory_limit
- allow_url_fopen
- disable_functions

But this is not a complete solution. A more robust method is to compare the ini settings of the current process with the content of the loaded ini files, and merge missing or changed values into the temporary ini by appending them to the end of the file.

However this will slow the restart process because:

- More work is needed to prepare the file.
- The ini will contain many default values and will take longer to parse.

This repo provides methods for measuring and comparing the extra time that this takes.

## Tests
There are two main types of test; The _Simple_ tests run a PHP script that always restarts the process, while the _Composer_ tests use a modified composer.phar to run `composer --version` with xdebug-handler output.

Both these tests types accept a `--merge-inis` argument, so that they can be run using both of the ini creation methods outlined above.

### Test Output
The `tests.sh` script first reports the number of ini files being used, then concatenates them using the two ini creation methods, reporting the file size and number of entries in the new ini.

It next runs each of the following tests 3 times:

- Simple Test
- Simple Test Merge
- Composer Test
- Composer Test Merge

 Timing values are shown for the overall restart, as well as the time taken to create the ini content (which is included in the overall time). The number of ini files being used is also shown.

The ini files created by the _Simple_ tests are saved as `tmp-ini` and `tmp-merged.ini` in the working directory.

## Usage
Clone this repo somewhere:

```bash
git clone https://github.com/johnstevenson/xdebug-ini-test.git .

./tests.sh
```

### Docker Image
The Travis CI tests run on a modified ubuntu:xenial image with PHP-7.0.27 installed and many extensions loaded, including xdebug.

https://hub.docker.com/r/johnstevenson/php-cli-exts/

To run this locally, from the repo directory:
```
docker build -t xdebug-ini-test .

# run the tests and exit
docker run -it --rm xdebug-ini-test /bin/bash tests.sh

# run bash
docker run -it --rm xdebug-ini-test

# run bash with live directory
docker run -it --rm -v "$PWD":/app xdebug-ini-test
```


