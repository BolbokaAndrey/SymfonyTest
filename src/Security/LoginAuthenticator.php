<?php

namespace App\Security;

use App\Message\SimulateExternalAuthCheck;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class LoginAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    use TargetPathTrait;

    public function __construct(
        private readonly UserRepository $users,
        private readonly MessageBusInterface $bus,
        private readonly RouterInterface $router,
        private readonly KernelInterface $kernel,
    ) {}

    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'app_login' && $request->isMethod('POST');
    }

    public function authenticate(Request $request): Passport
    {
        $email = (string) $request->request->get('email', '');
        $password = (string) $request->request->get('password', '');
        $csrfToken = (string) $request->request->get('_csrf_token', '');

        if ($email === '' || $password === '') {
            throw new CustomUserMessageAuthenticationException('Введите email и пароль.');
        }

        // 1) Emulate external service delay via RabbitMQ message
        $requestId = bin2hex(random_bytes(8));
        $this->bus->dispatch(new SimulateExternalAuthCheck($requestId));

        $dir = $this->kernel->getProjectDir() . '/var/auth_check';
        $deadline = microtime(true) + 2.0; // wait up to 2 seconds
        $doneFile = $dir . '/' . $requestId . '.done';
        while (microtime(true) < $deadline) {
            if (is_file($doneFile)) {
                @unlink($doneFile); // cleanup
                break;
            }
            usleep(50 * 1000); // 50ms step
        }

        // 2) Proceed with normal user/password verification
        //    We let Symfony validate the password via PasswordCredentials
        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [new CsrfTokenBadge('authenticate', $csrfToken)]
        );
    }

    public function onAuthenticationSuccess(Request $request, $token, string $firewallName): ?Response
    {
        $targetPath = $this->getTargetPath($request->getSession(), $firewallName);
        return new RedirectResponse($targetPath ?: $this->router->generate('admin_index'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        // Store the error for the login template
        $request->getSession()->getFlashBag()->add('error', $exception->getMessage());
        return new RedirectResponse($this->router->generate('app_login'));
    }

    // Entry point for unauthenticated access to protected resources
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->router->generate('app_login'));
    }
}
