<?php

$a = array(
    'a' => array(
        'b' => array(
            'c' => 'd'
        ),
        'e' => array(
            'f' => 'g'
        )
    ),
    'h' => 'hello'
);

$test = array(
    'b' => array(
        'c' => 'd'
    ),
    'e' => array(
        'f' => 'g'
    )
);

if(in_array($test,$a)) {
    echo 'ok';
} else {
    echo 'none';
}
