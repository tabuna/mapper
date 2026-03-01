<?php

namespace Tabuna\Map\Source\Contracts;

interface WordPressRequestPayload
{
    /**
     * @return array<string, mixed>
     */
    public function get_params(): array;
}
