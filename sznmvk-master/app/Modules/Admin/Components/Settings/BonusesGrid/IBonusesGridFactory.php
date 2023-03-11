<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\BonusesGrid;

interface IBonusesGridFactory
{
    public function create(): BonusesGrid;
}