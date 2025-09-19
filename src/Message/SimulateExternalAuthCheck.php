<?php

namespace App\Message;

final class SimulateExternalAuthCheck
{
    public function __construct(public readonly string $requestId)
    {
    }
}
