<?php

namespace Tabuna\Map\Container;

use Tabuna\Map\Container\Contracts\KernelContainerProvider;
use Throwable;

class FrameworkContainerDetector
{
    protected const SYMFONY_KERNEL_CLASS = 'Symfony\\Component\\HttpKernel\\Kernel';

    /**
     * Collect possible runtime container candidates from known environments.
     *
     * @return array<int, mixed>
     */
    public function detectCandidates(): array
    {
        return [
            $this->detectGlobalContainerVariable(),
            $this->detectSymfonyKernelContainer(),
            $this->detectLaravelContainer(),
        ];
    }

    /**
     * Detect Laravel container from helper runtime.
     */
    protected function detectLaravelContainer(): mixed
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
    protected function detectSymfonyKernelContainer(): mixed
    {
        $kernel = $GLOBALS['kernel'] ?? null;

        if ($kernel instanceof KernelContainerProvider) {
            return $this->extractKernelContainer($kernel);
        }

        $kernelClass = self::SYMFONY_KERNEL_CLASS;

        if (! class_exists($kernelClass) || ! $kernel instanceof $kernelClass) {
            return null;
        }

        return $this->extractKernelContainer($kernel);
    }

    /**
     * Detect generic global container variable (for simple bootstrap setups).
     */
    protected function detectGlobalContainerVariable(): mixed
    {
        return $GLOBALS['container'] ?? null;
    }

    /**
     * @param object $kernel
     *
     * @return mixed
     */
    protected function extractKernelContainer(object $kernel)
    {
        try {
            return call_user_func([$kernel, 'getContainer']);
        } catch (Throwable) {
            return null;
        }
    }
}
