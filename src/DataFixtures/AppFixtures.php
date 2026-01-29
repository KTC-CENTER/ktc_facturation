<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Client;
use App\Entity\Product;
use App\Entity\ProformaTemplate;
use App\Entity\TemplateItem;
use App\Entity\CompanySettings;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Paramètres entreprise
        $settings = $this->createCompanySettings();
        $manager->persist($settings);

        // Utilisateurs
        $users = $this->createUsers($manager);

        // Clients
        $clients = $this->createClients($manager);

        // Produits
        $products = $this->createProducts($manager);

        // Modèles préconfigurés
        $this->createTemplates($manager, $products);

        $manager->flush();
    }

    private function createCompanySettings(): CompanySettings
    {
        $settings = new CompanySettings();
        $settings->setCompanyName('KTC-Center Sarl');
        $settings->setAddress('Yaoundé, Cameroun');
        $settings->setPhone('+237 XXX XXX XXX');
        $settings->setEmail('contact@ktc-center.com');
        $settings->setWebsite('www.ktc-center.com');
        $settings->setRccm('RC/YAO/2016/A/3141');
        $settings->setTaxId('M016200025487C');
        $settings->setCurrency('FCFA');
        $settings->setDefaultTaxRate('19.25');
        $settings->setProformaPrefix('PROV');
        $settings->setInvoicePrefix('FAC');
        $settings->setDefaultValidityDays(30);
        $settings->setDefaultPaymentDays(30);

        return $settings;
    }

    private function createUsers(ObjectManager $manager): array
    {
        $users = [];

        // Super Admin
        $superAdmin = new User();
        $superAdmin->setEmail('admin@ktc-center.com');
        $superAdmin->setFirstName('Admin');
        $superAdmin->setLastName('KTC');
        $superAdmin->setPhone('+237 600 000 001');
        $superAdmin->setRoles([User::ROLE_SUPER_ADMIN]);
        $superAdmin->setIsActive(true);
        $superAdmin->setPassword($this->passwordHasher->hashPassword($superAdmin, 'admin123'));
        $manager->persist($superAdmin);
        $users['super_admin'] = $superAdmin;

        // Admin
        $admin = new User();
        $admin->setEmail('gestionnaire@ktc-center.com');
        $admin->setFirstName('Jean');
        $admin->setLastName('Dupont');
        $admin->setPhone('+237 600 000 002');
        $admin->setRoles([User::ROLE_ADMIN]);
        $admin->setIsActive(true);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);
        $users['admin'] = $admin;

        // Commerciaux
        $commercial1 = new User();
        $commercial1->setEmail('commercial@ktc-center.com');
        $commercial1->setFirstName('Marie');
        $commercial1->setLastName('Mbarga');
        $commercial1->setPhone('+237 600 000 003');
        $commercial1->setRoles([User::ROLE_COMMERCIAL]);
        $commercial1->setIsActive(true);
        $commercial1->setPassword($this->passwordHasher->hashPassword($commercial1, 'commercial123'));
        $manager->persist($commercial1);
        $users['commercial1'] = $commercial1;

        $commercial2 = new User();
        $commercial2->setEmail('commercial2@ktc-center.com');
        $commercial2->setFirstName('Pierre');
        $commercial2->setLastName('Nkono');
        $commercial2->setPhone('+237 600 000 004');
        $commercial2->setRoles([User::ROLE_COMMERCIAL]);
        $commercial2->setIsActive(true);
        $commercial2->setPassword($this->passwordHasher->hashPassword($commercial2, 'commercial123'));
        $manager->persist($commercial2);
        $users['commercial2'] = $commercial2;

        // Viewer
        $viewer = new User();
        $viewer->setEmail('viewer@ktc-center.com');
        $viewer->setFirstName('Paul');
        $viewer->setLastName('Essomba');
        $viewer->setPhone('+237 600 000 005');
        $viewer->setRoles([User::ROLE_VIEWER]);
        $viewer->setIsActive(true);
        $viewer->setPassword($this->passwordHasher->hashPassword($viewer, 'viewer123'));
        $manager->persist($viewer);
        $users['viewer'] = $viewer;

        return $users;
    }

    private function createClients(ObjectManager $manager): array
    {
        $clients = [];

        $clientsData = [
            [
                'name' => 'Paroisse Saint-Pierre',
                'email' => 'contact@paroisse-st-pierre.cm',
                'phone' => '+237 677 123 456',
                'address' => 'Quartier Bastos',
                'city' => 'Yaoundé',
                'contactPerson' => 'Abbé Jean-Marie',
                'contactPhone' => '+237 677 123 457',
            ],
            [
                'name' => 'Église Évangélique du Cameroun',
                'email' => 'secretariat@eec.cm',
                'phone' => '+237 699 234 567',
                'address' => 'Avenue Kennedy',
                'city' => 'Douala',
                'contactPerson' => 'Pasteur Thomas',
                'contactPhone' => '+237 699 234 568',
            ],
            [
                'name' => 'SABC - Brasseries du Cameroun',
                'email' => 'achats@sabc.cm',
                'phone' => '+237 233 421 000',
                'address' => 'Zone Industrielle Bassa',
                'city' => 'Douala',
                'taxId' => 'M012300045678A',
                'rccm' => 'RC/DLA/1950/B/1234',
                'contactPerson' => 'M. Kamga',
                'contactPhone' => '+237 699 345 678',
            ],
            [
                'name' => 'Université de Yaoundé I',
                'email' => 'rectorat@uy1.cm',
                'phone' => '+237 222 221 320',
                'address' => 'Campus Ngoa-Ekelle',
                'city' => 'Yaoundé',
                'contactPerson' => 'Pr. Ndjodo',
                'contactPhone' => '+237 677 456 789',
            ],
            [
                'name' => 'Clinique de l\'Espoir',
                'email' => 'direction@clinique-espoir.cm',
                'phone' => '+237 233 456 789',
                'address' => 'Rue de l\'Hôpital',
                'city' => 'Bafoussam',
                'taxId' => 'M098700012345B',
                'contactPerson' => 'Dr. Fotso',
                'contactPhone' => '+237 699 567 890',
            ],
        ];

        foreach ($clientsData as $data) {
            $client = new Client();
            $client->setName($data['name']);
            $client->setEmail($data['email'] ?? null);
            $client->setPhone($data['phone'] ?? null);
            $client->setAddress($data['address'] ?? null);
            $client->setCity($data['city'] ?? null);
            $client->setCountry('Cameroun');
            $client->setTaxId($data['taxId'] ?? null);
            $client->setRccm($data['rccm'] ?? null);
            $client->setContactPerson($data['contactPerson'] ?? null);
            $client->setContactPhone($data['contactPhone'] ?? null);

            $manager->persist($client);
            $clients[] = $client;
        }

        return $clients;
    }

    private function createProducts(ObjectManager $manager): array
    {
        $products = [];

        // LOGICIELS
        $logiciels = [
            [
                'name' => 'CHURCH 3.0',
                'code' => 'LOG-CHURCH',
                'description' => 'Logiciel de gestion paroissiale complet',
                'unitPrice' => '500000',
                'version' => '3.0',
                'licenseType' => '0-500 fidèles',
                'licenseDuration' => 12,
                'maxUsers' => 5,
            ],
            [
                'name' => 'CHURCH 3.0 Premium',
                'code' => 'LOG-CHURCH-PREM',
                'description' => 'Logiciel de gestion paroissiale - Version premium',
                'unitPrice' => '800000',
                'version' => '3.0',
                'licenseType' => '500-2000 fidèles',
                'licenseDuration' => 12,
                'maxUsers' => 15,
            ],
            [
                'name' => 'ComptaPlus',
                'code' => 'LOG-COMPTA',
                'description' => 'Logiciel de comptabilité générale',
                'unitPrice' => '350000',
                'version' => '2.5',
                'licenseType' => 'Mono-poste',
                'licenseDuration' => 12,
                'maxUsers' => 1,
            ],
        ];

        foreach ($logiciels as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setCode($data['code']);
            $product->setType(Product::TYPE_LOGICIEL);
            $product->setDescription($data['description']);
            $product->setUnitPrice($data['unitPrice']);
            $product->setVersion($data['version'] ?? null);
            $product->setLicenseType($data['licenseType'] ?? null);
            $product->setLicenseDuration($data['licenseDuration'] ?? null);
            $product->setMaxUsers($data['maxUsers'] ?? null);
            $product->setUnit('licence');
            $manager->persist($product);
            $products[$data['code']] = $product;
        }

        // MATÉRIELS
        $materiels = [
            [
                'name' => 'Ordinateur Desktop',
                'code' => 'MAT-DESKTOP',
                'description' => 'Ordinateur de bureau complet',
                'characteristics' => 'Core I3 /4Go /250Go',
                'unitPrice' => '250000',
                'brand' => 'HP',
                'model' => 'ProDesk 400',
                'warrantyMonths' => 12,
            ],
            [
                'name' => 'Imprimante Ticket',
                'code' => 'MAT-IMP-TICKET',
                'description' => 'Imprimante thermique pour tickets de caisse',
                'characteristics' => 'Imprimante à Ticket de caisse',
                'unitPrice' => '75000',
                'brand' => 'Epson',
                'model' => 'TM-T20III',
                'warrantyMonths' => 12,
            ],
            [
                'name' => 'Switch Réseau',
                'code' => 'MAT-SWITCH',
                'description' => 'Switch 4 ports + Clé Wifi',
                'characteristics' => 'Switch 4 ports / Clé Wifi',
                'unitPrice' => '35000',
                'brand' => 'TP-Link',
                'model' => 'TL-SG1005D',
                'warrantyMonths' => 24,
            ],
            [
                'name' => 'Onduleur',
                'code' => 'MAT-ONDULEUR',
                'description' => 'Onduleur de protection',
                'characteristics' => '650VA / Protection surtension',
                'unitPrice' => '45000',
                'brand' => 'APC',
                'model' => 'Back-UPS 650',
                'warrantyMonths' => 24,
            ],
        ];

        foreach ($materiels as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setCode($data['code']);
            $product->setType(Product::TYPE_MATERIEL);
            $product->setDescription($data['description']);
            $product->setCharacteristics($data['characteristics'] ?? null);
            $product->setUnitPrice($data['unitPrice']);
            $product->setBrand($data['brand'] ?? null);
            $product->setModel($data['model'] ?? null);
            $product->setWarrantyMonths($data['warrantyMonths'] ?? null);
            $product->setUnit('unité');
            $manager->persist($product);
            $products[$data['code']] = $product;
        }

        // SERVICES
        $services = [
            [
                'name' => 'Formation Utilisateurs',
                'code' => 'SRV-FORMATION',
                'description' => 'Formation des utilisateurs au logiciel',
                'unitPrice' => '50000',
                'durationHours' => 8,
            ],
            [
                'name' => 'Maintenance Annuelle',
                'code' => 'SRV-MAINT',
                'description' => 'Contrat de maintenance et support technique',
                'unitPrice' => '100000',
                'durationHours' => null,
            ],
            [
                'name' => 'Installation Site Web',
                'code' => 'SRV-WEBSITE',
                'description' => 'Création et hébergement site internet',
                'unitPrice' => '150000',
                'durationHours' => 40,
            ],
            [
                'name' => 'Pack SMS',
                'code' => 'SRV-SMS',
                'description' => 'Pack de 100 SMS',
                'unitPrice' => '10000',
                'durationHours' => null,
            ],
            [
                'name' => 'Support Formation + Maintenance',
                'code' => 'SRV-SUPPORT',
                'description' => 'Formation + Maintenance 1 an',
                'unitPrice' => '150000',
                'durationHours' => 8,
            ],
        ];

        foreach ($services as $data) {
            $product = new Product();
            $product->setName($data['name']);
            $product->setCode($data['code']);
            $product->setType(Product::TYPE_SERVICE);
            $product->setDescription($data['description']);
            $product->setUnitPrice($data['unitPrice']);
            $product->setDurationHours($data['durationHours'] ?? null);
            $product->setUnit('forfait');
            $manager->persist($product);
            $products[$data['code']] = $product;
        }

        return $products;
    }

    private function createTemplates(ObjectManager $manager, array $products): void
    {
        // Template CHURCH 3.0 EN LIGNE
        $template = new ProformaTemplate();
        $template->setName('CHURCH 3.0 EN LIGNE');
        $template->setDescription('Pack complet logiciel CHURCH 3.0 avec matériel et services');
        $template->setCategory('Paroisses');
        $template->setDefaultNotes('Ce pack comprend tout le nécessaire pour démarrer avec CHURCH 3.0');
        $template->setDefaultConditions("Validité de l'offre : 30 jours\nPaiement : 100% à la commande");
        $template->setIsActive(true);

        // Ajouter les éléments du template
        $items = [
            ['code' => 'LOG-CHURCH', 'quantity' => 1, 'optional' => false, 'sortOrder' => 1],
            ['code' => 'MAT-DESKTOP', 'quantity' => 2, 'optional' => true, 'price' => 0, 'sortOrder' => 2],
            ['code' => 'MAT-IMP-TICKET', 'quantity' => 2, 'optional' => true, 'price' => 0, 'sortOrder' => 3],
            ['code' => 'MAT-SWITCH', 'quantity' => 1, 'optional' => true, 'price' => 0, 'sortOrder' => 4],
            ['code' => 'SRV-WEBSITE', 'quantity' => 1, 'optional' => true, 'price' => 0, 'sortOrder' => 5],
            ['code' => 'SRV-SUPPORT', 'quantity' => 1, 'optional' => true, 'price' => 0, 'sortOrder' => 6],
            ['code' => 'SRV-SMS', 'quantity' => 1, 'optional' => true, 'price' => 0, 'sortOrder' => 7],
        ];

        foreach ($items as $itemData) {
            if (isset($products[$itemData['code']])) {
                $item = new TemplateItem();
                $item->setTemplate($template);
                $item->setProduct($products[$itemData['code']]);
                $item->setQuantity((string) $itemData['quantity']);
                $item->setIsOptional($itemData['optional']);
                $item->setSortOrder($itemData['sortOrder']);
                if (isset($itemData['price'])) {
                    $item->setUnitPrice((string) $itemData['price']);
                }
                $manager->persist($item);
            }
        }

        $manager->persist($template);

        // Template Pack Comptabilité
        $template2 = new ProformaTemplate();
        $template2->setName('Pack Comptabilité PME');
        $template2->setDescription('Solution comptable complète pour PME');
        $template2->setCategory('Entreprises');
        $template2->setIsActive(true);

        $items2 = [
            ['code' => 'LOG-COMPTA', 'quantity' => 1, 'optional' => false, 'sortOrder' => 1],
            ['code' => 'MAT-DESKTOP', 'quantity' => 1, 'optional' => true, 'sortOrder' => 2],
            ['code' => 'MAT-ONDULEUR', 'quantity' => 1, 'optional' => true, 'sortOrder' => 3],
            ['code' => 'SRV-FORMATION', 'quantity' => 1, 'optional' => false, 'sortOrder' => 4],
        ];

        foreach ($items2 as $itemData) {
            if (isset($products[$itemData['code']])) {
                $item = new TemplateItem();
                $item->setTemplate($template2);
                $item->setProduct($products[$itemData['code']]);
                $item->setQuantity((string) $itemData['quantity']);
                $item->setIsOptional($itemData['optional']);
                $item->setSortOrder($itemData['sortOrder']);
                $manager->persist($item);
            }
        }

        $manager->persist($template2);
    }
}
