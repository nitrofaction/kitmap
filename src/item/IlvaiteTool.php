<?php

namespace Kitmap\item;

class IlvaiteTool extends Durable
{
    public function getMaxDurability(): int
    {
        return 3810;
    }
}