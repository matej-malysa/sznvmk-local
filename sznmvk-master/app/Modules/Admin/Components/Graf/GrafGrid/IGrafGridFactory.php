<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Graf\GrafGrid;

interface IGrafGridFactory
{
    public function create(): GrafGrid;
}