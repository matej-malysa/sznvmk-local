<?php
declare(strict_types=1);

namespace App\Model;

use Dibi\Connection;
use Dibi\Fluent;
use Dibi\Row;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

class ImportantDatesModel extends BaseModel
{
    const T_IMPORTANT_DATES = 'important_dates';

    const ZALOHA_1 = 1;
    const ZALOHA_2 = 2;
    const DOPLATEK = 3;
    const REFUNDACE = 4;
    const KONEC_EDITACI = 5;

    /** @var Schema */
    protected Schema $deadlineSchema;

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->deadlineSchema = Expect::structure([
            'deadline' => Expect::type(DateTime::class)->before(function ($v) {
                return DateTime::from($v);
            }),
        ])->castTo(ArrayHash::class);
    }

    /**
     * @return Fluent
     */
    public function getAll(): Fluent
    {
        return $this->db->select('*')->from('%n', self::T_IMPORTANT_DATES)->orderBy('deadline ASC');
    }

    /**
     * @param array $dates
     * @return array
     */
    public function getCzechTextDates(array $dates): array
    {
        $czDates = [];
        foreach ($dates as $id => $date) {
            $month = (int) $date['deadline']->format('m');
            $czDate = '';
            switch ($month) {
                case 6:
                    $czDate = $date['deadline']->format('j') . '. června';
                    break;
                case 7:
                    $czDate = $date['deadline']->format('j') . '. července';
                    break;
                case 8:
                    $czDate = $date['deadline']->format('j') . '. srpna';
                    break;
                case 9:
                    $czDate = $date['deadline']->format('j') . '. září';
                    break;
                case 10:
                    $czDate = $date['deadline']->format('j') . '. října';
                    break;
            }
            $czDates[$id] = $czDate;
        }

        return $czDates;
    }

    /**
     * @param int $id
     * @return Row
     */
    public function getById(int $id): Row
    {
        return $this->db->select('*')->from('%n', self::T_IMPORTANT_DATES)->where('id = %i', $id)->fetch();
    }

    /**
     * @return array
     */
    public function getRefundDates(): array
    {
        return $this->db->select('*')->from('%n', self::T_IMPORTANT_DATES)->fetchAssoc('id');
    }

    /**
     * @param int $id
     * @param \Nette\Utils\ArrayHash $values
     * @throws \Dibi\Exception
     * @throws \Exception
     */
    public function editDeadline(int $id, ArrayHash $values): void
    {
        $values['deadline'] = new DateTime($values['deadline']);
        $values = $this->validate($this->deadlineSchema, $values);
        $this->db->update(self::T_IMPORTANT_DATES, ['deadline' => $values['deadline']])->where('id = %i', $id)->execute();
    }
}