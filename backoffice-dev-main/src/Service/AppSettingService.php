<?php

namespace App\Service;

use App\Entity\AppSetting;
use App\Repository\AppSettingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class AppSettingService
{
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $entityManager,
        private AppSettingRepository $appSettingRepository,
    ) {}

    public function get(string $name, ?string $default = null): ?string
    {
        $setting = $this->appSettingRepository->findOneBy([
            'name' => $name,
        ]);
        if ($setting === null) {
            return $default;
        }
        return $setting->getValue() ?? $default;
    }

    /**
     * @param string[] $names
     * @return array<string, string>
     */
    public function getMultiple(array $names = []): array
    {
        return $this->convertToKv($this->getMultipleRaw($names));
    }

    /**
     * @param string[] $names
     * @return AppSetting[]
     */
    public function getMultipleRaw(array $names = []): array
    {
        // Passing empty array means you want all (i.e. there is no criteria)
        if ($names === []) {
            $names = AppSetting::SUPPORTED_SETTINGS;
        }
        return $this->appSettingRepository->findBy([
            'name' => array_intersect($names, AppSetting::SUPPORTED_SETTINGS),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function getAll(): array
    {
        return $this->getMultiple([]);
    }

    /**
     * @param array<string, string> $settings
     */
    public function setMultiple(array $newSettings): array
    {
        if ($newSettings === []) {
            return [];
        }
        /** @var AppSetting[] $settings */
        $settings = $this->appSettingRepository->findBy([
            'name' => array_keys($newSettings),
        ]);
        foreach ($settings as $setting) {
            $setting->setValue($newSettings[$setting->getName()]);
        }
        $this->entityManager->flush();
        return $this->convertToKv($settings);
    }

    /**
     * @return string[]
     */
    public function setup(): array
    {
        // Returns an array of AppSettings created
        // Empty array if nothing needed to be setup
        $missingSettings = $this->findMissing();
        if ($this->findMissing() === []) {
            return [];
        }
        $newSettings = [];
        foreach ($missingSettings as $settingName) {
            $newSetting = new AppSetting($settingName);
            $newSettings[] = $newSetting;
            // Tell doctrine to track this new entity
            $this->entityManager->persist($newSetting);
        }
        $this->entityManager->flush();
        return $this->extractNames($newSettings);
    }

    /**
     * @param AppSetting[] $settings
     * @return array<string, string>
     */
    public function convertToKv(array $settings): array
    {
        $settingsAsKv = [];
        foreach ($settings as $setting) {
            $settingsAsKv[$setting->getName()] = $setting->getValue();
        }
        return $settingsAsKv;
    }

    /**
     * @return string[]
     */
    private function findMissing(): array
    {
        $existingSettingNames = $this->extractNames($this->appSettingRepository->findBy([
            'name' => AppSetting::SUPPORTED_SETTINGS,
        ]));
        return array_diff(AppSetting::SUPPORTED_SETTINGS, $existingSettingNames);
    }

    /**
     * @param AppSetting[] $settings
     * @return string[]
     */
    private function extractNames(array $settings): array
    {
        $names = [];
        foreach ($settings as $setting) {
            $names[] = $setting->getName();
        }
        return $names;
    }
}
