<?php

require_once('LogParser.php');
$pathToLogFile = $argv[1];

if (empty($pathToLogFile)) {
    die("Must specify path to log file");
}

$emailConfigs = array(
    'registration_failed' => array(
        'to' => 'you@acme.com',
    )
);

$logReader = new Acme\LogParser\SimpleLogReader($pathToLogFile);
$parseState = new Acme\LogParser\ParseStateJson();
$logParser = new Acme\LogParser\LogParser($logReader, $parseState);

$logParser->registerParseStrategy(
    new Acme\LogParser\RegistrationFailedStrategy(
        $parseState,
        $emailConfigs['registration_failed']
    )
);
$logParser->parse();
$logParser->sendEmail();
$logParser->done();
