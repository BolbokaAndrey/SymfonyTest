<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:roles:migrate-user-ids-to-codes', description: 'Convert user.roles JSON from role IDs to role CODES')]
class MigrateUserRoleIdsToCodesCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly RoleRepository $roleRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repo = $this->em->getRepository(User::class);
        /** @var User[] $users */
        $users = $repo->findAll();

        // Build id->code map
        $roles = $this->roleRepository->findAll();
        $idToCode = [];
        foreach ($roles as $r) {
            $idToCode[$r->getId()] = $r->getCode();
        }

        $updated = 0;
        foreach ($users as $user) {
            $raw = $user->getRoles(); // currently codes, but may contain ints from previous state

            $codes = [];
            foreach ((array)$raw as $val) {
                if (is_int($val)) {
                    if (isset($idToCode[$val])) {
                        $codes[] = $idToCode[$val];
                    }
                } elseif (is_string($val)) {
                    $code = strtoupper($val);
                    if (!str_starts_with($code, 'ROLE_')) {
                        $code = 'ROLE_' . $code;
                    }
                    $codes[] = $code;
                }
            }
            if (empty($codes)) {
                $codes[] = 'ROLE_USER';
            }
            $codes = array_values(array_unique($codes));

            if ($codes !== $raw) {
                $user->setRoles($codes);
                $updated++;
            }
        }

        if ($updated > 0) {
            $this->em->flush();
        }

        $output->writeln("Users updated: $updated");
        return Command::SUCCESS;
    }
}
