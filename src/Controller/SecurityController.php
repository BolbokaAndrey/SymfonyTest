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
use App\Repository\UserRepository;
use App\Repository\RoleRepository;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;

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

    #[Route('/admin', name: 'admin_index')]
    public function admin(UserRepository $userRepository): Response
    {
        return $this->render('admin/index.html.twig');
    }

    #[Route('/admin/users', name: 'admin_users')]
    public function adminUsers(UserRepository $userRepository, RoleRepository $roleRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CONTENT_MANAGER');
        $users = $userRepository->findBy([], ['id' => 'ASC']);
        $roles = $roleRepository->findAll();
        $roleNames = [];
        foreach ($roles as $r) {
            $roleNames[$r->getCode()] = $r->getNameRu();
        }
        return $this->render('admin/users.html.twig', [
            'users' => $users,
            'roleNames' => $roleNames,
        ]);
    }

    #[Route('/admin/users/new', name: 'admin_users_new')]
    public function adminUsersNew(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        RoleRepository $roleRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $error = null;
        $allRoles = $roleRepository->findAll();

        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name'));
            $lastName = trim((string) $request->request->get('lastName'));
            $email = strtolower(trim((string) $request->request->get('email')));
            $plainPassword = (string) $request->request->get('password');
            $roleCode = (string) $request->request->get('role_code');
            $selectedRole = $roleCode !== '' ? $roleRepository->findOneBy(['code' => $roleCode]) : null;

            // Admin cannot assign SUPER_ADMIN
            if ($selectedRole && $selectedRole->getCode() === 'ROLE_SUPER_ADMIN' && !$this->isGranted('ROLE_SUPER_ADMIN')) {
                $error = 'Недостаточно прав для назначения роли Супер-администратор.';
            }

            if ($name === '' || $email === '' || $plainPassword === '') {
                $error = 'Пожалуйста, заполните обязательные поля.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Некорректный email.';
            } elseif (strlen($plainPassword) < 6) {
                $error = 'Пароль должен быть не короче 6 символов.';
            } elseif (!$selectedRole) {
                $error = 'Выберите роль.';
            } else {
                $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existing) {
                    $error = 'Пользователь с таким email уже существует.';
                } else {
                    $user = new User();
                    $user->setName($name);
                    $user->setLastName($lastName ?: null);
                    $user->setEmail($email);
                    // по умолчанию ROLE_USER + выбранная роль
                    $codes = ['ROLE_USER'];
                    $codes[] = $selectedRole->getCode();
                    $user->setRoles(array_values(array_unique($codes)));
                    $hash = $passwordHasher->hashPassword($user, $plainPassword);
                    $user->setPassword($hash);
                    $em->persist($user);
                    $em->flush();
                    return $this->redirectToRoute('admin_users');
                }
            }
        }

        return $this->render('admin/user_form.html.twig', [
            'mode' => 'create',
            'error' => $error,
            'userData' => [
                'name' => $request->request->get('name', ''),
                'lastName' => $request->request->get('lastName', ''),
                'email' => $request->request->get('email', ''),
                'role_code' => $request->request->get('role_code'),
            ],
            'roles' => $allRoles,
        ]);
    }

    #[Route('/admin/users/{id}/edit', name: 'admin_users_edit')]
    public function adminUsersEdit(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        RoleRepository $roleRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Пользователь не найден');
        }

        // Only SUPER_ADMIN can edit a SUPER_ADMIN
        $targetIsSuper = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
        if ($targetIsSuper && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException('Недостаточно прав для редактирования супер-админа.');
        }

        $error = null;
        $allRoles = $roleRepository->findAll();

        if ($request->isMethod('POST')) {
            $name = trim((string) $request->request->get('name'));
            $lastName = trim((string) $request->request->get('lastName'));
            $email = strtolower(trim((string) $request->request->get('email')));
            $plainPassword = (string) $request->request->get('password');
            $roleCode = (string) $request->request->get('role_code');
            $selectedRole = $roleCode !== '' ? $roleRepository->findOneBy(['code' => $roleCode]) : null;
            if ($selectedRole && $selectedRole->getCode() === 'ROLE_SUPER_ADMIN' && !$this->isGranted('ROLE_SUPER_ADMIN')) {
                $error = 'Недостаточно прав для назначения роли Супер-администратор.';
            }

            if ($name === '' || $email === '') {
                $error = 'Имя и Email обязательны.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = 'Некорректный email.';
            } elseif (!$selectedRole) {
                $error = 'Выберите роль.';
            } else {
                $another = $userRepository->findOneBy(['email' => $email]);
                if ($another && $another->getId() !== $user->getId()) {
                    $error = 'Пользователь с таким email уже существует.';
                } else {
                    $user->setName($name);
                    $user->setLastName($lastName ?: null);
                    $user->setEmail($email);
                    // ROLE_USER + выбранная роль
                    $codes = ['ROLE_USER'];
                    $codes[] = $selectedRole->getCode();
                    $user->setRoles(array_values(array_unique($codes)));
                    if ($plainPassword !== '') {
                        if (strlen($plainPassword) < 6) {
                            $error = 'Пароль должен быть не короче 6 символов.';
                        } else {
                            $hash = $passwordHasher->hashPassword($user, $plainPassword);
                            $user->setPassword($hash);
                        }
                    }

                    if ($error === null) {
                        $em->flush();
                        return $this->redirectToRoute('admin_users');
                    }
                }
            }
        }

        return $this->render('admin/user_form.html.twig', [
            'mode' => 'edit',
            'error' => $error,
            'userData' => [
                'name' => $request->isMethod('POST') ? (string) $request->request->get('name') : (string) $user->getName(),
                'lastName' => $request->isMethod('POST') ? (string) $request->request->get('lastName') : (string) ($user->getLastName() ?? ''),
                'email' => $request->isMethod('POST') ? (string) $request->request->get('email') : (string) $user->getEmail(),
                'role_code' => $request->isMethod('POST') ? $request->request->get('role_code') : ($user->getRoles()[0] ?? 'ROLE_USER'),
            ],
            'userId' => $user->getId(),
            'targetIsSuper' => $targetIsSuper,
            'roles' => $allRoles,
        ]);
    }

    #[Route('/admin/users/{id}/delete', name: 'admin_users_delete', methods: ['POST'])]
    public function adminUsersDelete(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = $userRepository->find($id);
        if (!$user) {
            throw $this->createNotFoundException('Пользователь не найден');
        }

        // Admin cannot delete SUPER_ADMIN; allow only SUPER_ADMIN to delete SUPER_ADMIN
        $targetIsSuper = in_array('ROLE_SUPER_ADMIN', $user->getRoles(), true);
        if ($targetIsSuper && !$this->isGranted('ROLE_SUPER_ADMIN')) {
            throw $this->createAccessDeniedException('Недостаточно прав для удаления супер-админа.');
        }

        // Prevent deleting yourself to avoid accidental lockout
        if ($this->getUser() instanceof User && $this->getUser()->getId() === $user->getId()) {
            $this->addFlash('error', 'Нельзя удалить самого себя.');
            return $this->redirectToRoute('admin_users');
        }

        // CSRF check
        $token = new CsrfToken('delete_user_' . $user->getId(), (string) $request->request->get('_token'));
        if (!$csrfTokenManager->isTokenValid($token)) {
            throw $this->createAccessDeniedException('Неверный CSRF токен.');
        }

        $em->remove($user);
        $em->flush();
        $this->addFlash('success', 'Пользователь удален.');
        return $this->redirectToRoute('admin_users');
    }

    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        RoleRepository $roleRepository
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
