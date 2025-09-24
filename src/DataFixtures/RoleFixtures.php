<?php

namespace App\DataFixtures;

use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $roles = [
            [
                'CODE' => 'ROLE_SUPER_ADMIN',
                'NAME_RU' => 'Супер администратор',
            ],
            [
                'CODE' => 'ROLE_ADMIN',
                'NAME_RU' => 'Администратор',
            ],
            [
                'CODE' => 'ROLE_CONTENT_MANAGER',
                'NAME_RU' => 'Контент менеджер',
            ],
            [
                'CODE' => 'ROLE_USER',
                'NAME_RU' => 'Пользователь',
            ],
        ];

        foreach ($roles as $role) {
            $roleEntity = new Role();
            $roleEntity->setCode($role['CODE']);
            $roleEntity->setNameRu($role['NAME_RU']);
            $manager->persist($roleEntity);
        }

        $manager->flush();
    }
}
