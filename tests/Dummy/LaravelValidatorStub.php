<?php

declare(strict_types=1);

namespace Tabuna\Map\Tests\Dummy;

use Illuminate\Contracts\Support\MessageBag as MessageBagContract;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Illuminate\Support\MessageBag;
use RuntimeException;

class LaravelValidatorStub implements ValidatorContract
{
    /**
     * @param array<string, mixed> $validated
     */
    public function __construct(
        private array $validated,
        private bool $throwOnValidated = false
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function validate()
    {
        return $this->validated();
    }

    /**
     * @return array<string, mixed>
     */
    public function validated()
    {
        if ($this->throwOnValidated) {
            throw new RuntimeException('Validated payload is unavailable.');
        }

        return $this->validated;
    }

    public function fails()
    {
        return false;
    }

    /**
     * @return array<string, mixed>
     */
    public function failed()
    {
        return [];
    }

    public function sometimes($attribute, $rules, callable $callback)
    {
        return $this;
    }

    public function after($callback)
    {
        return $this;
    }

    public function errors()
    {
        return $this->getMessageBag();
    }

    public function getMessageBag(): MessageBagContract
    {
        return new MessageBag();
    }
}
