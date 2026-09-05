<?php

namespace Ifds\HttpAdapter\Support;

use Ifds\HttpAdapter\AdapterManager;
use Ifds\HttpAdapter\Attributes\Driver;
use Ifds\HttpAdapter\Exceptions\InvalidDriverConfigException;
use Illuminate\Contracts\Container\Container;
use ReflectionClass;

final class DriverRegistrar
{
    /**
     * @param  array<string, array<string, mixed>>  $driversConfig
     */
    public function registerFromConfig(AdapterManager $manager, Container $app, array $driversConfig): void
    {
        foreach ($driversConfig as $name => $config) {
            $manager->extend($name, function ($app) use ($name, $config) {
                $class = $config['client'] ?? null;

                if (! $class) {
                    throw InvalidDriverConfigException::missingClient($name);
                }

                $this->assertAttributeMatches($class, $name);

                return $app->make($class)->configure(DriverConfig::fromArray($name, $config));
            });
        }
    }

    private function assertAttributeMatches(string $class, string $name): void
    {
        $reflection = new ReflectionClass($class);
        $attributes = $reflection->getAttributes(Driver::class);

        if ($attributes === []) {
            throw InvalidDriverConfigException::missingAttribute($name, $class);
        }

        /** @var Driver $driverAttribute */
        $driverAttribute = $attributes[0]->newInstance();

        if ($driverAttribute->name !== $name) {
            throw InvalidDriverConfigException::attributeMismatch($name, $class, $driverAttribute->name);
        }
    }
}
