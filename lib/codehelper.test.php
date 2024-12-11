<?php

require_once('./codehelper.php');

function test() {
    $url = 'http://10.4.3.155:5040/code/api';
    $token = 'code-api-key-5aa8549cabe0f1793eab6';

    $data = (object)[
        'userQuestion' => 'Hello world!',
        'token' => $token
    ];

    $result = AiHelperUtils::request($url, $data);

    echo '<pre>';
    print_r($result);
    echo '</pre>';
}

test();
