<?php

namespace App\MessageHandler;

use App\Message\SimulateExternalAuthCheck;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsMessageHandler]
final class SimulateExternalAuthCheckHandler
{
    public function __construct(private readonly KernelInterface $kernel) {}

    public function __invoke(SimulateExternalAuthCheck $message): void
    {
        // Emulate slow external check
        usleep(1500 * 1000); // 1.5s

        // Mark as done by touching a file in var/auth_check
        $dir = $this->kernel->getProjectDir() . '/var/auth_check';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        @touch($dir . '/' . $message->requestId . '.done');
    }
}
