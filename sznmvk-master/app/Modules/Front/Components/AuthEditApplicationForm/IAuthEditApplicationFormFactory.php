<?php
declare(strict_types=1);

namespace App\Modules\Front\Components\AuthEditApplicationForm;

interface IAuthEditApplicationFormFactory
{
    /**
     * @param int $applicationId
     * @return AuthEditApplicationForm
     */
    public function create(int $applicationId): AuthEditApplicationForm;
}