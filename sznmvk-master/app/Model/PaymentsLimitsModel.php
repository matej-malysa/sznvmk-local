<?php
declare(strict_types=1);

namespace App\Model;

use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
use Dibi\Row;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\ArrayHash;

class PaymentsLimitsModel extends BaseModel
{
    const T_PAYMENTS_LIMITS = 'payments_limits';

    const ZALOHA_ID = 1;
    const FULL_PRICE_ID = 2;
    const ZALOHA_EUR_ID = 3;
    const FULL_PRICE_EUR_ID = 4;

    /** @var Schema */
    protected Schema $paymentLimitSchema;


    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->paymentLimitSchema = Expect::structure([
            'amount' => Expect::float()->required(),
        ])->castTo(ArrayHash::class);
    }

    /**
     * @return Fluent
     */
    public function getAll(): Fluent
    {
        return $this->db->select('*')->from('%n', self::T_PAYMENTS_LIMITS);
    }

    /**
     * @param int $id
     * @return Row
     */
    public function getById(int $id): Row
    {
        return $this->db->select('*')->from('%n', self::T_PAYMENTS_LIMITS)->where('id = %i', $id)->fetch();
    }

    /**
     * @param int $id
     * @param ArrayHash $values
     * @throws Exception
     */
    public function editLimit(int $id, ArrayHash $values): void
    {
        $values = $this->validate($this->paymentLimitSchema, $values);
        $this->db->update(self::T_PAYMENTS_LIMITS, ['amount' => $values['amount']])->where('id = %i', $id)->execute();
    }
}
