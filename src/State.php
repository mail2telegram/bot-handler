<?php

namespace M2T;

use M2T\Model\DraftEmail;

class State
{
    public ?string $step;
    public ?string $strategy;

    public function __construct(
        ?string $step = null,
        ?string $strategy = null
    ) {
        $this->step = $step;
        $this->strategy = $strategy;
    }
}
