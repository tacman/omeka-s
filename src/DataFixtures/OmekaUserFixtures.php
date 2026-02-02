<?php

namespace App\DataFixtures;

use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Omeka\Entity\User;
use Omeka\Permissions\Acl;

class OmekaUserFixtures extends Fixture implements DependentFixtureInterface
{
    public const DEFAULT_PASSWORD = 'ChangeMe123!';

    public function load(ObjectManager $manager): void
    {
        $now = new DateTime();

        $users = [
            ['admin@example.com', 'Global Admin', Acl::ROLE_GLOBAL_ADMIN],
            ['siteadmin@example.com', 'Site Admin', Acl::ROLE_SITE_ADMIN],
            ['editor@example.com', 'Editor', Acl::ROLE_EDITOR],
            ['reviewer@example.com', 'Reviewer', Acl::ROLE_REVIEWER],
            ['author@example.com', 'Author', Acl::ROLE_AUTHOR],
            ['researcher@example.com', 'Researcher', Acl::ROLE_RESEARCHER],
        ];

        foreach ($users as [$email, $name, $role]) {
            $user = new User();
            $user->setEmail($email);
            $user->setName($name);
            $user->setRole($role);
            $user->setIsActive(true);
            $user->setCreated($now);
            $user->setModified($now);
            $user->setPassword(self::DEFAULT_PASSWORD);
            $manager->persist($user);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [OmekaBootstrapFixtures::class];
    }
}
