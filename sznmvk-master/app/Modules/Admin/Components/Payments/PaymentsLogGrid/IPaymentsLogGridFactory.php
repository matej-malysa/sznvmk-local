<?php
declare(strict_types=1);

namespace App\Modules\Admin\Components\Payments\PaymentsLogGrid;

interface IPaymentsLogGridFactory
{
    /**
     * @param int $paymentId
     * @return PaymentsLogGrid
     */
    public function create(int $paymentId): PaymentsLogGrid;
}