<?php

$port = getenv('LOCAL_PORT');
$missing = array_filter(
    ['BW_ACCOUNT_ID', 'BW_CLIENT_ID', 'BW_CLIENT_SECRET', 'BW_MESSAGING_APPLICATION_ID', 'BW_NUMBER', 'LOCAL_PORT'],
    fn ($name) => getenv($name) === false
);

if ($missing || !ctype_digit($port)) {
    fwrite(STDERR, "Please set the environmental variables defined in the README\n");
    exit(1);
}

$server = proc_open(
    [PHP_BINARY, '-S', "127.0.0.1:{$port}", '-t', __DIR__ . '/public', __DIR__ . '/public/index.php'],
    [STDIN, STDOUT, STDERR],
    $pipes
);
exit(proc_close($server));
