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

class BonusesModel extends BaseModel
{
    const T_BONUSES = 'bonuses';

    /** @var Structure|Schema */
    protected Schema|Structure $editBonusSchema;

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->editBonusSchema = Expect::structure([
            'name' => Expect::string()->required(),
        ])->castTo(ArrayHash::class);
    }

    /**
     * @param int $id
     * @return string
     */
    public function getNameById(int $id): string
    {
        return $this->db->select('name')->from('%n', self::T_BONUSES)->where('id = %i', $id)->fetchSingle();
    }

    /** @return Fluent */
    public function getGrid(): Fluent
    {
        return $this->db->select('*')->from('%n', self::T_BONUSES);
    }

    /**
     * @return array
     */
    public function getActiveToRadioSelect(): array
    {
        return $this->db->select('id, name')->from('%n', self::T_BONUSES)->where('enabled = %i', 1)->fetchAssoc('id');
    }

    /**
     * @param int $id
     * @param int $value
     * @throws Exception
     */
    public function changeEnabled(int $id, int $value)
    {
        $this->db->update(self::T_BONUSES, ['enabled' => $value])->where('id = %i', $id)->execute();
    }

    /**
     * @param int $id
     * @param ArrayHash $values
     * @throws Exception
     */
    public function editBonus(int $id, ArrayHash $values): void
    {
        $values = $this->validate($this->editBonusSchema, $values);
        $this->db->update(self::T_BONUSES, $values)->where('id = %i', $id)->execute();
    }
}