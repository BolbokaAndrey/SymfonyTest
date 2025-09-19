<?php

namespace App\Command;

use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:roles:seed', description: 'Seed default roles with Russian names')]
class SeedRolesCommand extends Command
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $defaults = [
            'ROLE_USER' => 'Пользователь',
            'ROLE_CONTENT_MANAGER' => 'Контент-менеджер',
            'ROLE_ADMIN' => 'Администратор',
            'ROLE_SUPER_ADMIN' => 'Супер-администратор',
        ];

        $repo = $this->em->getRepository(Role::class);
        $count = 0;

        foreach ($defaults as $code => $nameRu) {
            $role = $repo->findOneBy(['code' => $code]);
            if (!$role) {
                $role = (new Role())->setCode($code)->setNameRu($nameRu);
                $this->em->persist($role);
                $count++;
            } else {
                // keep name up to date
                if ($role->getNameRu() !== $nameRu) {
                    $role->setNameRu($nameRu);
                }
            }
        }

        $this->em->flush();
        $output->writeln("Seeded roles. Inserted: $count");
        return Command::SUCCESS;
    }
}
