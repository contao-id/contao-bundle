<?php

declare(strict_types=1);

namespace ContaoId\ContaoBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoIdContaoBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
