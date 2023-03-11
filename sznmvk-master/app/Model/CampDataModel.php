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

class CampDataModel extends BaseModel
{
    const T_CAMP_DATA = 'camp_data';
    const T_COVID = 'covid';

    /** @var Structure|Schema */
    protected Schema|Structure $editCampDataSchema;


    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->editCampDataSchema = Expect::structure([
            'personalId' => Expect::string(),
            'cardId' => Expect::string(),
            'permanentAddress' => Expect::string(),
            'covid' => Expect::int(),
        ])->castTo(ArrayHash::class);
    }

    /**
     * @param int $id
     * @return array
     */
    public function getById(int $id): array
    {
        return $this->db->select('*')->from('%n', self::T_CAMP_DATA)->where('id = %i', $id)->fetchAll();
    }

    /**
     * @return Fluent
     */
    public function getCampDataGrid(): Fluent
    {
        return $this->db->select('app.id, app.firstname, app.lastname, app.session, camp_data.personal_id AS personalId, 
        camp_data.card_id AS cardId, camp_data.permanent_address AS permanentAddress, sessions.title AS sessionName, 
        participants.covid AS covid, covid.name AS covidName')
            ->from('%n AS app', ApplicationsModel::T_APPLICATIONS)
            ->leftJoin('%n ON app.id = camp_data.application_id', self::T_CAMP_DATA)
            ->leftJoin('%n ON sessions.id = app.session', SessionsModel::T_SESSIONS)
            ->leftJoin('%n ON participants.application_id = app.id', ParticipantsModel::T_PARTICIPANTS)
            ->leftJoin('%n ON covid.id = participants.covid', self::T_COVID)
            ->where('participants.status = %i', ParticipantsModel::STATUS_CONFIRMED)
            ->orderBy('app.session');
    }

    public function getCovidSelect(): array
    {
        return $this->db->select('id, name')->from('%n', self::T_COVID)->fetchPairs();
    }

    /**
     * @param int $applicationId
     * @param ArrayHash $values
     * @throws Exception
     */
    public function edit(int $applicationId, ArrayHash $values): void
    {
        $values = $this->validate($this->editCampDataSchema, $values);
        $this->db->query('INSERT INTO %n (application_id, personal_id, card_id, permanent_address) VALUES (%i, %s, %s, %s) 
            ON DUPLICATE KEY UPDATE personal_id = %s, card_id = %s, permanent_address = %s', self::T_CAMP_DATA, $applicationId,
            $values->personalId, $values->cardId, $values->permanentAddress, $values->personalId, $values->cardId, $values->permanentAddress);
        $this->db->update(ParticipantsModel::T_PARTICIPANTS, ['covid' => $values->covid])->where('application_id = %i', $applicationId)->execute();
    }
}