<?php

namespace App\Twig;

use App\Repository\CompanySettingsRepository;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class AppExtension extends AbstractExtension implements GlobalsInterface
{
    private ?array $cachedSettings = null;

    public function __construct(
        private CompanySettingsRepository $settingsRepository
    ) {}

    public function getGlobals(): array
    {
        $settings = $this->getSettings();

        return [
            'company_logo' => $settings ? $settings->getLogoPath() : null,
            'company_favicon' => $settings ? $settings->getFaviconPath() : null,
            'company_name' => $settings ? $settings->getCompanyName() : null,
        ];
    }

    private function getSettings(): ?object
    {
        if ($this->cachedSettings === null) {
            try {
                $this->cachedSettings = ['data' => $this->settingsRepository->findOneBy([], ['id' => 'ASC'])];
            } catch (\Exception $e) {
                $this->cachedSettings = ['data' => null];
            }
        }
        return $this->cachedSettings['data'];
    }
}
