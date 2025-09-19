<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\RoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:roles:migrate-user-roles-to-ids', description: 'Convert user.roles JSON from role codes to role IDs')]
class MigrateUserRolesToIdsCommand extends Command
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

        // Build maps code<->id once
        $roles = $this->roleRepository->findAll();
        $codeToId = [];
        foreach ($roles as $r) {
            $codeToId[$r->getCode()] = $r->getId();
        }

        $updated = 0;
        foreach ($users as $user) {
            $raw = $user->getRoleIds(); // currently whatever is stored in JSON
            $needUpdate = false;

            if (empty($raw)) {
                // assign default ROLE_USER id if available
                if (isset($codeToId['ROLE_USER'])) {
                    $user->setRoleIds([$codeToId['ROLE_USER']]);
                    $needUpdate = true;
                }
            } else {
                $ids = [];
                foreach ($raw as $val) {
                    if (is_int($val)) {
                        $ids[] = $val;
                    } elseif (is_string($val)) {
                        // treat as role code
                        if (isset($codeToId[$val])) {
                            $ids[] = $codeToId[$val];
                        } else {
                            // try uppercase and prefix
                            $code = strtoupper($val);
                            if (!str_starts_with($code, 'ROLE_')) {
                                $code = 'ROLE_' . $code;
                            }
                            if (isset($codeToId[$code])) {
                                $ids[] = $codeToId[$code];
                            }
                        }
                    }
                }
                $ids = array_values(array_unique(array_map('intval', $ids)));
                if ($ids !== $raw && !empty($ids)) {
                    $user->setRoleIds($ids);
                    $needUpdate = true;
                }
            }

            if ($needUpdate) {
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
