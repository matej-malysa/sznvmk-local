<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Payments\AssignPaymentForm;

interface IAssignPaymentFormFactory
{
    /**
     * @param int $id
     * @return AssignPaymentForm
     */
    public function create(int $id): AssignPaymentForm;
}