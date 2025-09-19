<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Repository\RoleRepository;

#[AsCommand(name: 'app:user:create', description: 'Create a user with roles and a hashed password')]
class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly RoleRepository $roleRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Email of the user')
            ->addArgument('password', InputArgument::REQUIRED, 'Plain password')
            ->addOption('name', null, InputOption::VALUE_REQUIRED, 'First name', 'Admin')
            ->addOption('last-name', null, InputOption::VALUE_OPTIONAL, 'Last name')
            ->addOption('roles', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Roles to assign (repeat option)', ['ROLE_USER'])
            ->addOption('update-if-exists', null, InputOption::VALUE_NONE, 'Update password/roles if user exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = (string) $input->getArgument('email');
        $plainPassword = (string) $input->getArgument('password');
        /** @var string[] $rolesOpt */
        $rolesOpt = (array) $input->getOption('roles');
        $name = (string) $input->getOption('name');
        $lastName = $input->getOption('last-name');
        $updateIfExists = (bool) $input->getOption('update-if-exists');

        $repo = $this->em->getRepository(User::class);
        $existing = $repo->findOneBy(['email' => $email]);

        if ($existing && !$updateIfExists) {
            $output->writeln("<error>User with email '$email' already exists. Use --update-if-exists to update.</error>");
            return Command::FAILURE;
        }

        $user = $existing ?: new User();
        $user->setEmail($email);
        $user->setName($name);
        $user->setLastName($lastName ?: null);
        // Map role codes to IDs
        $codes = array_map(function ($r) {
            $r = strtoupper((string)$r);
            return str_starts_with($r, 'ROLE_') ? $r : 'ROLE_' . $r;
        }, $rolesOpt);
        $roles = $this->roleRepository->createQueryBuilder('r')
            ->where('r.code IN (:codes)')->setParameter('codes', $codes)
            ->getQuery()->getResult();
        $ids = array_map(fn($r) => $r->getId(), $roles);
        if (empty($ids)) {
            // fallback to ROLE_USER if provided roles not found
            $roleUser = $this->roleRepository->findOneBy(['code' => 'ROLE_USER']);
            if ($roleUser) { $ids = [$roleUser->getId()]; }
        }
        $user->setRoleIds($ids);

        $hash = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hash);

        $this->em->persist($user);
        $this->em->flush();

        $action = $existing ? 'updated' : 'created';
        $output->writeln("<info>User $action:</info> $email");
        $output->writeln('<info>Role IDs:</info> ' . implode(', ', $user->getRoleIds()));
        return Command::SUCCESS;
    }
}
