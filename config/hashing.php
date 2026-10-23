<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Hash Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default hash driver that will be used to hash
    | passwords for your application. By default, Argon2id is used.
    |
    */

    'driver' => env('HASH_DRIVER', 'argon2id'),

    /*
    |--------------------------------------------------------------------------
    | Argon Options
    |--------------------------------------------------------------------------
    |
    | Here you may specify the configuration options that will be used when
    | hashing passwords using the Argon algorithm.
    |
    */

    'argon' => [
        'memory' => 65536,
        'threads' => 1,
        'time' => 4,
        'verify' => true,
    ],

    'bcrypt' => [
        'rounds' => env('BCRYPT_ROUNDS', 12),
        'verify' => true,
    ],

];
