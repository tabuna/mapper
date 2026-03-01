<?php

namespace Tabuna\Map\Support;

use Illuminate\Container\Container;
use Illuminate\Contracts\Container\Container as ContainerContract;
use Psr\Container\ContainerInterface;
use Throwable;

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
    }

    /**
     * Resolve and cache a runtime container from supported framework environments.
     */
    protected static function resolveAutoDetectedContainer(): ?ContainerContract
    {
        if (self::$autoDetectedContainer instanceof ContainerContract) {
            return self::$autoDetectedContainer;
        }

        $candidates = [
            self::detectGlobalContainerVariable(),
            self::detectSymfonyKernelContainer(),
            self::detectLaravelContainer(),
        ];

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

        if (is_object($candidate) && method_exists($candidate, 'get') && method_exists($candidate, 'has')) {
            return new PsrContainerAdapter(new SymfonyContainerAdapter($candidate));
        }

        return null;
    }

    /**
     * Detect Laravel container from helper runtime.
     */
    protected static function detectLaravelContainer(): mixed
    {
        if (! function_exists('app')) {
            return null;
        }

        try {
            return app();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Detect Symfony container exposed by global kernel instance.
     */
    protected static function detectSymfonyKernelContainer(): mixed
    {
        $kernel = $GLOBALS['kernel'] ?? null;

        if (! is_object($kernel) || ! method_exists($kernel, 'getContainer')) {
            return null;
        }

        try {
            return $kernel->getContainer();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Detect generic global container variable (for simple bootstrap setups).
     */
    protected static function detectGlobalContainerVariable(): mixed
    {
        return $GLOBALS['container'] ?? null;
    }
}
