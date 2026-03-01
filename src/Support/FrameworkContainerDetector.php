<?php

namespace Tabuna\Map\Support;

use Throwable;

class FrameworkContainerDetector
{
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
    protected function detectGlobalContainerVariable(): mixed
    {
        return $GLOBALS['container'] ?? null;
    }
}
