<?php

namespace Acme\LogParser;

interface LogReaderInterface
{
    public function setStart($bytes);
    public function freezeEnd();
    public function getLine();
}

interface ParseStrategyInterface
{
    public function checkLine($line);
    public function sendEmail();
}

class SimpleLogReader implements LogReaderInterface
{
    private $fileHandle;
    private $startInBytes = 0;
    private $endInBytes = 0;

    public function __construct($pathToFile)
    {
        //try to open the file
        $this->fileHandle = fopen($pathToFile, 'r');

        //if unsuccessful we should throw an exception
        if (!$this->fileHandle) {
            throw new \Exception("${pathToFile} could not be opened");
        }
    }

    public function setStart($bytes)
    {
        $this->startInBytes = $bytes;
        fseek($this->fileHandle, $this->startInBytes);
    }

    public function freezeEnd()
    {
        $currentPosition = ftell($this->fileHandle);
        fseek($this->fileHandle, -1, SEEK_END);
        $this->endInBytes = ftell($this->fileHandle);
        fseek($this->fileHandle, $currentPosition);
        return $this->endInBytes;
    }

    public function getLine()
    {
        if (ftell($this->fileHandle) > $this->endInBytes) {
            return false;
        } else {
            return fgets($this->fileHandle);
        }
    }
}

//expects to be provided with a log reader and a log parser state
class LogParser
{
    private $logReader = null;
    private $parserState = null;
    private $parseStrategies = array();
    private $lastByteRead = null;

    public function __construct(LogReaderInterface $logReader, ParseStateInterface $parseState)
    {
        $this->logReader = $logReader;
        $this->parseState = $parseState;

        //grab previous progress
        $startByte = 0;

        $lastByteRead = $this->parseState->get('lastByteRead');

        if ($lastByteRead) {
            $startByte = $lastByteRead;
        }

        $this->logReader->setStart($startByte);
        $this->lastByteRead = $this->logReader->freezeEnd();
    }

    public function registerParseStrategy(ParseStrategyInterface $parseStrategy)
    {
        $this->parseStrategies[] = $parseStrategy;
    }

    public function parse()
    {
        if (count($this->parseStrategies) === 0) {
            throw new \Exception("No parse strategies specified");
        }

        while ($line = $this->logReader->getLine()) {
            //check line with all parse strategies
            foreach ($this->parseStrategies as $parseStrategy) {
                $parseStrategy->checkLine($line);
            }
        }
    }

    public function sendEmail()
    {
        //each parse strategy can send email
        foreach ($this->parseStrategies as $parseStrategy) {
            $parseStrategy->sendEmail();
        }
    }

    public function done()
    {
        //update lastParseTimestamp in state
        $this->parseState->set('lastParseTimestamp', time());
        $this->parseState->set('lastByteRead', $this->lastByteRead);
        $this->parseState->save();
    }
}

class RegistrationFailedStrategy implements ParseStrategyInterface
{
    private $regex = "/Registration from.+ failed for '(.*)'/";
    private $ipAddresses = array();
    private $emailConfig = array(
        "to" => 'me@acme.com',
        "subject" => "Registration Failed",
        "message" => "IP address %s is trying to register on host PBX-2.",
        "headers" => array(
            'From: noreply@acme.com'
        )
    );
    private $parseState = null;

    public function __construct(ParseStateInterface $parseState, $emailConfig = array())
    {
        $this->parseState = $parseState;
        $this->emailConfig = array_merge($this->emailConfig, $emailConfig);
    }

    public function checkLine($line)
    {
        $matches = null;
        $hasMatches = preg_match($this->regex, $line, $matches);
        if ($hasMatches) {
            $ipAddress = $matches[1];

            $ipAddresesInState = $this->parseState->get('ipAddresses');

            if (!isset($ipAddresesInState)) {
                $ipAddresesInState = array();
            }

            if (!isset($this->ipAddresses[$ipAddress])) {
                $this->ipAddresses[$ipAddress] = array(
                    "count" => 0,
                    "previouslySent" => isset($ipAddresesInState[$ipAddress])
                );
            }

            $this->ipAddresses[$ipAddress]['count']++;
            $ipAddresesInState[$ipAddress] = 1;
            $this->parseState->set('ipAddresses', $ipAddresesInState);
        }
    }

    public function sendEmail()
    {
        if (empty($this->ipAddresses)) {
            echo "No Registration Failure emails to send.\n";
            return;
        }

        $to = $this->emailConfig['to'];
        $subject = $this->emailConfig['subject'];
        $headers = implode($this->emailConfig['headers'], '\r\n');

        foreach ($this->ipAddresses as $ipAddress => $info) {
            if ($info['previouslySent']) {
                continue;
            }

            $message .= sprintf($this->emailConfig['message'], $ipAddress) . "\n";
        }

        $result = mail($to, $subject, $message, $headers);

        if ($result) {
            echo "Success: Registration Failures email accepted for delivery.\n";
        } else {
            echo "Error: Registration Failures email not accepted for delivery.\n";
        }
    }
}

interface ParseStateInterface
{
    public function get($property);
    public function set($property, $value);
    public function save();
}

class ParseStateJson implements ParseStateInterface
{
    private $state = array();
    private $fileName;

    public function __construct($fileName = '.parse_state_json')
    {
        $this->fileName = $fileName;

        //ignore error here, just means file doesn't exist
        $fileAsString = @file_get_contents($fileName);

        if ($fileAsString) {
            $this->state = json_decode($fileAsString, true);
        }
    }

    public function get($property)
    {
        return $this->state[$property];
    }

    public function set($property, $value)
    {
        $this->state[$property] = $value;
    }

    public function save()
    {
        file_put_contents($this->fileName, json_encode($this->state));
    }
}
