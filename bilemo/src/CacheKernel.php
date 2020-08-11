<?php


namespace App;


class CacheKernel
{
    protected function getOptions(): array
    {
        return [
            'default_ttl' => 0,
            // ...
        ];
    }
}