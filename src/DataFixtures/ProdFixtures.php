<?php

namespace App\DataFixtures;

use App\Entity\CompanySettings;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ProdFixtures extends Fixture implements FixtureGroupInterface
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public static function getGroups(): array
    {
        return ['prod'];
    }

    public function load(ObjectManager $manager): void
    {
        $this->createSuperAdmin($manager);
        $this->createCompanySettings($manager);

        $manager->flush();
    }

    private function createSuperAdmin(ObjectManager $manager): void
    {
        $existingAdmin = $manager->getRepository(User::class)->findOneBy([
            'email' => 'admin@kamer-center.net',
        ]);

        if ($existingAdmin) {
            return;
        }

        $allUsers = $manager->getRepository(User::class)->findAll();
        foreach ($allUsers as $user) {
            if (in_array(User::ROLE_SUPER_ADMIN, $user->getRoles())) {
                return;
            }
        }

        $admin = new User();
        $admin->setEmail('admin@kamer-center.net');
        $admin->setFirstName('Admin');
        $admin->setLastName('KTC');
        $admin->setPhone('+237 600 000 001');
        $admin->setIsActive(true);
        $admin->setRoles([User::ROLE_SUPER_ADMIN]);
        $admin->setPassword(
            $this->passwordHasher->hashPassword($admin, 'Super@2026!')
        );

        $manager->persist($admin);
    }

    private function createCompanySettings(ObjectManager $manager): void
    {
        $existing = $manager->getRepository(CompanySettings::class)->findOneBy([]);

        if ($existing) {
            return;
        }

        $settings = new CompanySettings();
        $settings->setCompanyName('KTC-Center Sarl');
        $settings->setRccm('RC/YAO/2016/A/3141');
        $settings->setTaxId('M016200025487C');
        $settings->setAddress('Yaoundé, Cameroun');
        $settings->setCountry('Cameroun');
        $settings->setPhone('+237 XXX XXX XXX');
        $settings->setEmail('contact@ktc-center.com');
        $settings->setWebsite('http://www.ktc-center.com');

        $settings->setCurrency('FCFA');
        $settings->setDefaultTaxRate('19.25');
        $settings->setProformaPrefix('PROV');
        $settings->setInvoicePrefix('FAC');
        $settings->setProformaStartNumber(1);
        $settings->setInvoiceStartNumber(1);
        $settings->setProformaCurrentNumber(0);
        $settings->setInvoiceCurrentNumber(0);
        $settings->setDefaultValidityDays(30);
        $settings->setDefaultPaymentDays(30);

        $settings->setSenderEmail('sale@kamer-center.net');
        $settings->setSenderName('KTC-CENTER');
        $settings->setReplyToEmail('sale@kamer-center.net');

        $settings->setTimezone('Africa/Douala');
        $settings->setLocale('fr');
        $settings->setDateFormat('d/m/Y');

        $manager->persist($settings);
    }
}