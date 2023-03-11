<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Payments\AutoAssignPaymentForm;

interface IAutoAssignPaymentFormFactory
{
    /**
     * @return AutoAssignPaymentForm
     */
    public function create(): AutoAssignPaymentForm;
}