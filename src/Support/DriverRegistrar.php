<?php

namespace Ifds\Interchange\Support;

use Ifds\Interchange\Attributes\Driver;
use Ifds\Interchange\Client\AbstractApiClient;
use Ifds\Interchange\Exceptions\InvalidDriverConfigException;
use Illuminate\Contracts\Container\Container;
use ReflectionClass;

final class DriverRegistrar
{
    /**
     * Build the client class configured under `interchange.drivers.{$name}`.
     *
     * The config is read at resolve time rather than snapshotted at boot, so a
     * driver added or reconfigured later in the request (tests, feature flags,
     * runtime tenancy) is picked up.
     */
    public function resolve(Container $container, string $name): AbstractApiClient
    {
        /** @var array<string, mixed> $config */
        $config = $container->make('config')->get("interchange.drivers.{$name}", []);

        if ($config === []) {
            throw InvalidDriverConfigException::unknownDriver($name);
        }

        $class = $config['client'] ?? null;

        if (! is_string($class) || $class === '') {
            throw InvalidDriverConfigException::missingClient($name);
        }

        $this->assertAttributeMatches($class, $name);

        /** @var AbstractApiClient $client */
        $client = $container->make($class);

        return $client->configure(DriverConfig::fromArray($name, $config));
    }

    /**
     * @param  class-string  $class
     */
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
