<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Settings\SessionsGrid;

interface ISessionsGridFactory
{
    /**
     * @return SessionsGrid
     */
    public function create(): SessionsGrid;
}