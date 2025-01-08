<?php

namespace App\Config;

use App\Entity\InstanceConfiguration;
use App\Repository\InstanceConfigurationRepository;
use Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

readonly class ConfigValue
{
    public function __construct(
        private InstanceConfigurationRepository $instanceConfigurationRepository,
        private ParameterBagInterface $parameterBag,
    ) {
    }

    public function resolve(string $name, bool $createIfMissing = false): ?string
    {
        $existsInDb = $this->instanceConfigurationRepository->findOneBy(['name' => $name]);
        if ($existsInDb instanceof InstanceConfiguration && trim((string) $existsInDb->getValue()) !== '') {
            return $existsInDb->getValue();
        }

        if ($createIfMissing) {
            $config = new InstanceConfiguration();
            $config->setName($name);
            $config->setValue($this->resolve($name));
            $this->instanceConfigurationRepository->save($config, true);
        }

        try {
            $result = $this->parameterBag->get($name);
            if ($result === null || is_array($result)) {
                return null;
            }

            return (string) $result;
        } catch (ParameterNotFoundException) {
            return null;
        }
    }

    public function update(string $name, string $value): void
    {
        $config = $this->instanceConfigurationRepository->findOneBy(['name' => $name]);
        if (!$config instanceof InstanceConfiguration) {
            $config = new InstanceConfiguration();
            $config->setName($name);
        }
        $config->setValue($value);
        $this->instanceConfigurationRepository->save($config, true);
    }
}
