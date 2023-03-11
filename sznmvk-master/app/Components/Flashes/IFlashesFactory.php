<?php
declare(strict_types=1);

namespace App\Components\Flashes;

interface IFlashesFactory
{
    /**
     * @return Flashes
     */
    public function create();
}