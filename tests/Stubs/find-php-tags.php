<?php

declare(strict_types=1);

// Prints each file given as an argument that PHP's tokenizer, under this
// process's ini settings, reads as containing PHP code. ShortOpenTagTest runs
// it with `-d short_open_tag=On`, which can't be switched on at runtime.

foreach (array_slice($argv, 1) as $file) {
    foreach (token_get_all((string) file_get_contents($file)) as $token) {
        if (is_array($token) && $token[0] !== T_INLINE_HTML) {
            echo $file, PHP_EOL;
            break;
        }
    }
}
