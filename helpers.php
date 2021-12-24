<?php declare(strict_types=1);

function output(string $message = ''): void {
    if ($message !== '') {
        print $message;
    }
    print PHP_EOL;
}
