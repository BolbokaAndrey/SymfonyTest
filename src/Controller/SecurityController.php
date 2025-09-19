<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;

final class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): void
    {
        // Controller can be blank: it will be intercepted by the logout key on your firewall.
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('admin_index');
        }

        $error = null;

        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name'));
            $lastName = trim((string) $request->request->get('lastName'));
            $email = strtolower(trim((string) $request->request->get('email')));
            $plainPassword = (string) $request->request->get('password');

            if ($name === '' || $email === '' || $plainPassword === '') {
                $error = 'Пожалуйста, заполните обязательные поля.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Некорректный email.';
            } elseif (strlen($plainPassword) < 6) {
                $error = 'Пароль должен быть не короче 6 символов.';
            } else {
                $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existing) {
                    $error = 'Пользователь с таким email уже существует.';
                } else {
                    $user = new User();
                    $user->setName($name);
                    $user->setLastName($lastName ?: null);
                    $user->setEmail($email);
                    $hash = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hash);
                    // роли по умолчанию выставляются методом getRoles()
                    $em->persist($user);
                    $em->flush();

                    return $this->redirectToRoute('app_login');
                }
            }
        }

        return $this->render('security/register.html.twig', [
            'error' => $error,
        ]);
    }
}
