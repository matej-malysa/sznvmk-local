<?php
declare(strict_types=1);

namespace App\Model;

use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\ArrayHash;

class TransportModel extends BaseModel
{
    const T_TRANSPORTS = 'transport_types';

    const VLASTNI = 2;

    /** @var Structure|Schema */
    protected Schema|Structure $editTransportSchema;

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->editTransportSchema = Expect::structure([
            'name' => Expect::string()->required(),
        ])->castTo(ArrayHash::class);
    }

    /**
     * @param int $id
     * @return string
     */
    public function getNameById(int $id): string
    {
        return $this->db->select('name')->from('%n', self::T_TRANSPORTS)->where('id = %i', $id)->fetchSingle();
    }

    /**
     * @return array
     */
    public function getAllToRadioSelect(): array
    {
        return $this->db->select('id, name')->from('%n', self::T_TRANSPORTS)->where('enabled = %i', 1)->fetchAssoc('id');
    }

    /**
     * @return array
     */
    public function getAllToSelect(): array
    {
        return $this->db->select('id, name')->from('%n', self::T_TRANSPORTS)->where('enabled = %i', 1)->fetchPairs();
    }

    /**
     * @return Fluent
     */
    public function getGrid(): Fluent
    {
        return $this->db->select('*')->from('%n', self::T_TRANSPORTS);
    }

    /**
     * @param int $id
     * @param int $value
     * @throws Exception
     */
    public function changeEnabled(int $id, int $value): void
    {
        $this->db->update(self::T_TRANSPORTS, ['enabled' => $value])->where('id = %i', $id)->execute();
    }

    /**
     * @param int $id
     * @param ArrayHash $values
     * @throws Exception
     */
    public function editTransport(int $id, ArrayHash $values): void
    {
        $values = $this->validate($this->editTransportSchema, $values);
        $this->db->update(self::T_TRANSPORTS, $values)->where('id = %i', $id)->execute();
    }
}