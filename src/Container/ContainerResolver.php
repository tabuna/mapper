<?php

namespace Tabuna\Map\Container;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Psr\Container\ContainerInterface;

class ContainerResolver
{
    /**
     * Global configured container used by default for all mapper instances.
     */
    protected static ?ContainerContract $globalContainer = null;

    /**
     * Auto-detected runtime container cached for subsequent mapper instances.
     */
    protected static ?ContainerContract $autoDetectedContainer = null;

    /**
     * Runtime detector for framework-specific container discovery.
     */
    protected static ?FrameworkContainerDetector $detector = null;

    /**
     * Resolve container for mapper runtime.
     */
    public static function resolve(?ContainerContract $container = null): ContainerContract
    {
        return $container
            ?? self::$globalContainer
            ?? self::resolveAutoDetectedContainer()
            ?? Container::getInstance();
    }

    /**
     * Configure global Illuminate container for all future map() calls.
     */
    public static function useContainer(ContainerContract $container): void
    {
        self::$globalContainer = $container;
    }

    /**
     * Configure global PSR-11 container for all future map() calls.
     */
    public static function usePsrContainer(ContainerInterface $container): void
    {
        self::$globalContainer = new PsrContainerAdapter($container);
    }

    /**
     * Reset global and auto-detected container configuration.
     */
    public static function reset(): void
    {
        self::$globalContainer = null;
        self::$autoDetectedContainer = null;
        self::$detector = null;
    }

    /**
     * Resolve and cache a runtime container from supported framework environments.
     */
    protected static function resolveAutoDetectedContainer(): ?ContainerContract
    {
        if (self::$autoDetectedContainer instanceof ContainerContract) {
            return self::$autoDetectedContainer;
        }

        $detector = self::$detector ??= new FrameworkContainerDetector();
        $candidates = $detector->detectCandidates();

        foreach ($candidates as $candidate) {
            $resolved = self::normalizeCandidate($candidate);

            if ($resolved instanceof ContainerContract) {
                self::$autoDetectedContainer = $resolved;

                return self::$autoDetectedContainer;
            }
        }

        return null;
    }

    /**
     * Normalize mixed container candidate into Illuminate container.
     */
    protected static function normalizeCandidate(mixed $candidate): ?ContainerContract
    {
        if ($candidate instanceof ContainerContract) {
            return $candidate;
        }

        if ($candidate instanceof ContainerInterface) {
            return new PsrContainerAdapter($candidate);
        }

        if (SymfonyContainerAdapter::supports($candidate)) {
            return new PsrContainerAdapter(new SymfonyContainerAdapter($candidate));
        }

        return null;
    }
}
