<?php

$device_tokens = array("64d2b6076e98fbb25df2fd7cd2dc3ba9d3f90927ec76d5e356dff4464ede33da");

$passphrase = "123asd";

$message = "FooBarPushDemo test!";

$cert = 'ck.pem';

///////////////////////////////////////////////////////////////////////////////
$ctx = stream_context_create();

stream_context_set_option($ctx, 'ssl', 'local_cert', $cert);
stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);


for($i = 0; $i < count($device_tokens); $i++) {
    $device_token = mb_convert_encoding($device_tokens[$i], 'utf-8');


    echo "Device token : $device_token, length <" . strlen($device_token) . ">" . PHP_EOL;

    // Open a connection to the APNS server
    $fp = stream_socket_client(
        'ssl://gateway.sandbox.push.apple.com:2195', $err,
        $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

    stream_set_blocking($fp, 0);
    stream_set_write_buffer($fp, 0);

    if (!$fp)
        exit("Failed to connect: $err $errstr" . PHP_EOL);


    echo 'Connected to APNS' . PHP_EOL;

    // Create the payload body
    $body['aps'] = array(
        'alert' => $message
    );

    // Encode the payload as JSON
    $payload = mb_convert_encoding(stripslashes(json_encode($body)), 'utf-8');

    // Build the binary notification

    if(strlen($device_token) != 64) {
        die("Device token has invalid length");
    }

    if(strlen($payload) < 10) {
        die('Invalid payload size');
    }

    $msg = chr(0)
        .pack('n', 32) //token length
        .pack('H*', $device_token) //token
        .pack('n', strlen($payload)) //length of payload
        .$payload;


    // Send it to the server
    $result = fwrite($fp, $msg /*, strlen($msg) */);

    if (!$result)
        echo 'Message not delivered' . PHP_EOL;
    else {
        echo 'Message successfully delivered, result:' . $result . PHP_EOL;

        echo "Sent {" . strlen($msg) . "} to server, received {" . $result . "}" . PHP_EOL;

        $response = fread($fp, 6);
        var_dump($response);

        $messageResult = unpack('Ccommand/CstatusCode/Nidentifier', $response);
        var_dump($messageResult);

    }

    //connect to the APNS feedback servers
    //make sure you're using the right dev/production server & cert combo!

    $apns = stream_socket_client('ssl://feedback.sandbox.push.apple.com:2196', $errcode, $errstr, 60, STREAM_CLIENT_CONNECT, $ctx);
    if(!$apns) {
        echo "ERROR $errcode: $errstr\n";
        return;
    }

    echo "Feedback returned: " . PHP_EOL;

    $feedback_tokens = array();
    //and read the data on the connection:
    while(!feof($apns)) {
        $data = fread($apns, 38);
        var_dump($data);
        if(strlen($data)) {
            $feedback_tokens[] = unpack("N1timestamp/n1length/H*devtoken", $data);
        }
    }

    var_dump($feedback_tokens);

    fclose($apns);
    fclose($fp);
}
/*
// Put your device token here (without spaces):
//$deviceToken = '11e0e87773f34b496384625824fcb2b9a977603a1d27a840e7cf0fb0643dccc7';
$deviceToken = '64d2b6076e98fbb25df2fd7cd2dc3ba9d3f90927ec76d5e356dff4464ede33da';

// Put your private key's passphrase here:
$passphrase = '123asd';

// Put your alert message here:
$message = 'My first push notification!';

////////////////////////////////////////////////////////////////////////////////
//ftp://mayahiyacom@mayahiya.net/subbir/ck.pem
$ctx = stream_context_create();
stream_context_set_option($ctx, 'ssl', 'local_cert', 'ck.pem');
stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);
stream_context_set_option($ctx, 'ssl', 'cafile', 'entrust_2048_ca.cer');

// Open a connection to the APNS server
$fp = stream_socket_client(
    'ssl://gateway.push.apple.com:2195', $err,
    $errstr, 2, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);

if (!$fp)
    exit("Failed to connect: $err $errstr" . PHP_EOL);

echo 'Connected to APNS' . PHP_EOL;

// Create the payload body
$body['aps'] = array(
    'alert' => $message,
    'sound' => 'default'
);

// Encode the payload as JSON
$payload = json_encode($body);

// Build the binary notification
$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;

// Send it to the server
$result = fwrite($fp, $msg, strlen($msg));

if (!$result)
    echo 'Message not delivered' . PHP_EOL;
else
    echo 'Message successfully delivered' . PHP_EOL;

// Close the connection to the server
fclose($fp);
*/
?>