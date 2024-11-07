<?php

namespace AaronFrancis\Solo\Console;

class CustomHotKey
{
    function __construct(
        public string|array $key,
        public string $name,
        public $callback,
    ) {}
}