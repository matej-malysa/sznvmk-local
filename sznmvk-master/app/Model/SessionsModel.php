<?php
declare(strict_types=1);

namespace App\Model;

use App\Classes\Exceptions\FullSessionEditCapacityException;
use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

class SessionsModel extends BaseModel
{
    const T_SESSIONS = 'sessions';

    /** @var ParticipantsModel */
    protected ParticipantsModel $participantsModel;

    /** @var Schema */
    protected Schema $sessionSchema;

    public function __construct(Connection $connection, ParticipantsModel $participantsModel)
    {
        parent::__construct($connection);
        $this->participantsModel = $participantsModel;
        $this->sessionSchema = Expect::structure([
            'title' => Expect::string(),
            'start' => Expect::type(DateTime::class)->before(function ($v) {
                return DateTime::from($v);
            }),
            'end' => Expect::type(DateTime::class)->before(function ($v) {
                return DateTime::from($v);
            }),
            'full_capacity' => Expect::int(),
            'first_lodging_capacity' => Expect::int(),
            'second_lodging_capacity' => Expect::int(),
            'third_lodging_capacity' => Expect::int(),
            'instructors_capacity' => Expect::int(),
            'guest_capacity' => Expect::int(),
        ])->castTo(ArrayHash::class);
    }

    /**
     * Returns sessions dates in the corresponding czech format.
     * If start and end dates are in the same month: "5. - 9. září"
     * If start and end dates are not in the same month: "30. srpna - 4. září"
     *
     * @return array
     */
    public function getAllToRadioSelect(): array
    {
        $sessionsRaw = $this->db->select("id, start, end, active")->from('%n', self::T_SESSIONS)->fetchAssoc('id');
        $sessionsCz = [];
        $czStart = '';
        $czEnd = '';

        foreach ($sessionsRaw as $sessionRaw) {
            if($sessionRaw->active == 1) {
                $monthStart = (int) $sessionRaw->start->format('m');
                $monthEnd = (int) $sessionRaw->end->format('m');
                if ($monthStart == $monthEnd) {
                    $czStart = $sessionRaw->start->format('j.');
                    switch ($monthEnd) {
                        case 8:
                            $czEnd = $sessionRaw->end->format('j') . '. srpna';
                            break;
                        case 9:
                            $czEnd = $sessionRaw->end->format('j') . '. září';
                            break;
                        case 10:
                            $czEnd = $sessionRaw->end->format('j') . '. října';
                            break;
                        default:
                            break;
                    }
                } else {
                    switch ($monthStart) {
                        case 8:
                            $czStart = $sessionRaw->start->format('j') . '. srpna';
                            break;
                        case 9:
                            $czStart = $sessionRaw->start->format('j') . '. září';
                            break;
                        case 10:
                            $czStart = $sessionRaw->start->format('j') . '. října';
                            break;
                        default:
                            break;
                    }
                    switch ($monthEnd) {
                        case 8:
                            $czEnd = $sessionRaw->end->format('j') . '. srpna';
                            break;
                        case 9:
                            $czEnd = $sessionRaw->end->format('j') . '. září';
                            break;
                        case 10:
                            $czEnd = $sessionRaw->end->format('j') . '. října';
                            break;
                        default:
                            break;
                    }
                }

                $sessionsCz[$sessionRaw->id] = ['date' => $czStart . ' - ' . $czEnd];
            }

        }

        return $sessionsCz;
    }

    /**
     * @param int $id
     * @return string
     */
    public function getNameById(int $id): string
    {
        return $this->db->select('title')->from('%n', self::T_SESSIONS)->where('id = %i', $id)->fetchSingle();
    }

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->db->select('id')->from('%n', self::T_SESSIONS)->fetchAll();
    }

    /**
     * @return array
     */
    public function getAllToSelect(): array
    {
        return $this->db->select('id, title')->from('%n', self::T_SESSIONS)->fetchPairs();
    }

    /**
     * @param ArrayHash $values
     * @throws Exception
     */
    public function addSession(ArrayHash $values)
    {
        $values = $this->validate($this->sessionSchema, $values);
        $this->db->insert(self::T_SESSIONS, $values)->execute();
    }

    /**
     * @param int $sessionId
     * @param iterable $values
     * @throws Exception
     */
    public function editSession(int $sessionId, iterable $values)
    {
        $values = $this->validate($this->sessionSchema, $values);
        $oldFullCapacity = $this->db->select('full_capacity')->from('%n', self::T_SESSIONS)->where('id = %i', $sessionId)->fetchSingle();
        $freeSpots = $this->db->select('sess.full_capacity - sess.instructors_capacity - cp.confirmed_participants')
            ->from('%n AS sess', self::T_SESSIONS)
            ->leftJoin('%n AS cp ON cp.session = sess.id', ParticipantsModel::V_CONFIRMED_PARTICIPANTS)
            ->where('sess.id = %i', $sessionId)
            ->fetchSingle();
        if ($values['full_capacity'] < $oldFullCapacity && $freeSpots == 0) {
            throw new FullSessionEditCapacityException('Na turnusu je více potvrzených účastníků než nově požadovaná kapacita');
        } elseif ($values['full_capacity'] > $oldFullCapacity && $freeSpots == 0) {
            $this->participantsModel->recountQueue($sessionId);
        }
        $this->db->update(self::T_SESSIONS, $values)->where('id = %i', $sessionId)->execute();
    }

    /**
     * @param int $sessionId
     * @throws Exception
     */
    public function deleteSession(int $sessionId)
    {
        $this->db->delete(self::T_SESSIONS)->where('id = %i', $sessionId)->execute();
    }

    /* GRID ********************************************************************************************************* */

    /**
     * @return Fluent
     */
    public function getGridSelection()
    {
        $selection = $this->db->select('*')
            ->from('%n', self::T_SESSIONS);

        return $selection;
    }


    public function getGrid()
    {
        $selection = $this->getGridSelection();

        return $selection;
    }

    /**
     * @param int $id
     * @param int $value
     * @throws Exception
     */
    public function changeEnabled(int $id, int $value)
    {
        $this->db->update(self::T_SESSIONS, ['active' => $value])->where('id = %i', $id)->execute();
    }
}