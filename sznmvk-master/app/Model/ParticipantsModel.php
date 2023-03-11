<?php
declare(strict_types=1);

namespace App\Model;

use App\Classes\MailerSend\MailerSendService;
use App\Components\AppComponent;
use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
use Dibi\Row;
use JsonException;
use MailerSend\Exceptions\MailerSendAssertException;
use Nette\Utils\ArrayHash;
use Psr\Http\Client\ClientExceptionInterface;

class ParticipantsModel extends BaseModel
{
    const T_PARTICIPANTS = 'participants';
    const T_PARTICIPANT_STATUS = 'participant_status';
    const V_CONFIRMED_PARTICIPANTS = 'confirmed_participants';

    const STATUS_ZAJEMCE = 1;
    const STATUS_GUEST = 2;
    const STATUS_CONFIRMED = 3;
    const STATUS_WAITING_FOR_ACTION = 4;

    /** @var LodgingModel */
    protected LodgingModel $lodgingModel;

    /** @var MailerSendService */
    protected MailerSendService $mailerSendService;

    public function __construct(Connection $connection,LodgingModel $lodgingModel, MailerSendService $mailerSendService)
    {
        parent::__construct($connection);
        $this->lodgingModel = $lodgingModel;
        $this->mailerSendService = $mailerSendService;
    }

    /**
     * @param int $applicationId
     * @return Row|null
     */
    public function getParticipantByApplicationId(int $applicationId): Row|null
    {
        return $this->db->select('participants.*, ps.name AS statusName, ps.status_text AS statusText')->from('%n', self::T_PARTICIPANTS)
            ->leftJoin('%n AS ps ON participants.status = ps.id', self::T_PARTICIPANT_STATUS)
            ->where('application_id = %i', $applicationId)->fetch();
    }


    /**
     * @param int $applicationId
     * @throws Exception
     */
    public function createParticipantFromApplication(int $applicationId): void
    {
        $participantValues['application_id'] = $applicationId;
        $participantValues['status'] = self::STATUS_ZAJEMCE;

        $this->db->insert(ParticipantsModel::T_PARTICIPANTS, $participantValues)->execute();
    }


    /**
     * @param int|null $sessionId
     * @return mixed
     */
    public function getFreeSpots(?int $sessionId)
    {
        if (!empty($sessionId)) {
            $spots = $this->db->select('sess.full_capacity - sess.instructors_capacity - cp.confirmed_participants')
                ->from('%n AS sess', SessionsModel::T_SESSIONS)
                ->leftJoin('%n AS cp ON cp.session = sess.id', self::V_CONFIRMED_PARTICIPANTS)
                ->where('sess.id = %i', $sessionId)
                ->fetchSingle();
            if (is_null($spots)) {
                return 1000;
            } else {
                return $spots;
            }
        } else {
            return 0;
        }
    }

    /**
     * @return array
     */
    public function getAvailableSessions(): array
    {
        $allSessions = $this->db->select('id')->from('%n', SessionsModel::T_SESSIONS)->fetchAll();
        $freeSpots = [];
        foreach ($allSessions as $key => $sessionId) {
            $cap = $this->getFreeSpots($sessionId->id);
            if ($cap > 0) {
                $freeSpots[$sessionId->id] = $cap;
            }
        }

        return $freeSpots;
    }

    /**
     * @param int $applicationId
     * @param int $sessionId
     * @throws Exception
     */
    public function enqueue(int $applicationId, int $sessionId)
    {
        $queueOrd = $this->db->select('IF(MAX(participants.queue) IS NULL, 1, MAX(participants.queue) + 1)')
            ->from('%n', self::T_PARTICIPANTS)
            ->leftJoin('%n ON applications.id = participants.application_id', ApplicationsModel::T_APPLICATIONS)
            ->where('applications.session = %i', $sessionId)
            ->fetch();
        $this->db->update(self::T_PARTICIPANTS, ['queue' => $queueOrd])->where('application_id = %i', $applicationId)->execute();
        $this->logParticipantEnqueued($applicationId, $sessionId);
    }

    /**
     * @param int $sessionId
     * @param int $minQueueNumber
     * @throws Exception
     */
    public function dequeue(int $sessionId, int $minQueueNumber)
    {
        $applicationToDequeue = $this->db->select('id')->from('%n', self::T_PARTICIPANTS)
            ->leftJoin('%n AS app ON app.id = participants.application_id', ApplicationsModel::T_APPLICATIONS)
            ->where('app.session = %i AND queue = %i', $sessionId, $minQueueNumber)
            ->fetchSingle();

        // Dequeue this application
        $this->db->update(self::T_PARTICIPANTS, ['queue' => null])->where('application_id = %i', $applicationToDequeue)->execute();

        // Zmen stav ucastnika na confirmed a zaloguj
        $this->db->update(self::T_PARTICIPANTS, ['status' => self::STATUS_CONFIRMED])->where('application_id = %i', $applicationToDequeue)->execute();
        $this->logParticipantDequeued($applicationToDequeue);
    }

    /**
     * Check queue of participants session, if there is someone, dequeue participant with the lowest queue number
     *
     * @param int $sessionId
     * @throws Exception
     */
    public function recountQueue(int $sessionId)
    {
        // Get first in queue for this session
        $minQueueNumber = $this->db->select('IF(MIN(participants.queue) IS NULL, NULL, MIN(participants.queue))')
            ->from('%n', self::T_PARTICIPANTS)
            ->leftJoin('%n AS app ON app.id = participants.application_id', ApplicationsModel::T_APPLICATIONS)
            ->where('app.session = %i', $sessionId)
            ->fetchSingle();
        if (!empty($minQueueNumber)) {
            $this->dequeue($sessionId, $minQueueNumber);
        }
    }

    /**
     * @param int $applicationId
     * @param int|null $sessionId
     * @return int|null
     */
    public function getQueueOrder(int $applicationId, ?int $sessionId): ?int
    {
        $queueNumber = $this->db->select('queue')
            ->from('%n', self::T_PARTICIPANTS)
            ->where('application_id = %i', $applicationId)
            ->fetchSingle();

        if (empty($sessionId)) {
            return null;
        } else {
            return $this->db->select('COUNT(id)+1 AS ord')
                ->from('%n', self::T_PARTICIPANTS)
                ->leftJoin('%n ON applications.id = participants.application_id', ApplicationsModel::T_APPLICATIONS)
                ->where('applications.session = %i AND queue < %i', $sessionId, $queueNumber)
                ->fetchSingle();
        }
    }
/**
     * @return array
     */
    public function getParticipantsGrid(): Fluent
    {
        return $this->db->select("participants.application_id, participants.status, participants.queue, participants.create_at, lod.name, app.firstname, app.lastname, app.session, sessions.title AS sessionName,inv.invited_friends")
            ->from('%n', self::T_PARTICIPANTS)
            ->leftJoin('%n AS app ON app.id = participants.application_id', ApplicationsModel::T_APPLICATIONS)
            ->leftJoin('%n ON app.session = sessions.id', SessionsModel::T_SESSIONS)
            ->leftJoin(LodgingModel::T_LODGING)->as('lod')->on('lod.id = participants.room')
            ->leftJoin("(Select inv.application_id,COUNT(*) as invited_friends 
            from %n as inv 
            Left Join %n as app on inv.email = app.email 
            Left Join %n as par on par.application_id = app.id 
            where par.status = %i
             Group by application_id ) as inv on inv.application_id = app.id",ApplicationsModel::T_INVITED_FRIENDS,ApplicationsModel::T_APPLICATIONS, self::T_PARTICIPANTS,self::STATUS_CONFIRMED)

            ->where('session IS NOT NULL AND participants.status = %i', self::STATUS_CONFIRMED)
            ->orderBy('session ASC, lastname ASC');
    }

    public function getQueuesGrid()
    {
        $select = $this->db->select("participants.*, app.firstname, app.lastname, app.session, sessions.title AS sessionName")
            ->from('%n', self::T_PARTICIPANTS)
            ->leftJoin('%n AS app ON app.id = participants.application_id', ApplicationsModel::T_APPLICATIONS)
            ->leftJoin('%n ON app.session = sessions.id', SessionsModel::T_SESSIONS)
            ->where('queue IS NOT NULL')->fetchAll();

        foreach ($select as $id => $participant) {
            $ord = $this->getQueueOrder($participant['application_id'], $participant['session']);

            $select[$id]['queueOrder'] = $ord;
        }

        return $select;
    }


    public function getZajemciGrid(): array
    {
        $select = $this->db->select("participants.*, app.firstname, app.lastname, app.session, sessions.title AS sessionName")
            ->from('%n', self::T_PARTICIPANTS)
            ->leftJoin('%n AS app ON app.id = participants.application_id', ApplicationsModel::T_APPLICATIONS)
            ->leftJoin('%n ON app.session = sessions.id', SessionsModel::T_SESSIONS)
            ->where('participants.status = %i', self::STATUS_ZAJEMCE)
            ->orderBy('session ASC, lastname ASC')
            ->fetchAll();

        foreach ($select as $id => $participant) {
            $sum = $this->db->select('SUM(amount)')
                ->from('%n', PaymentsModel::T_PAYMENTS)
                ->where('application_id = %i', $participant['application_id'])
                ->fetchSingle();
            if (!empty($sum)) {
                $paymentsSum = (int) $sum;
            } else {
                $paymentsSum = 0;
            }

            $select[$id]['paymentsSum'] = $paymentsSum;
        }

        return $select;
    }

    public function getParticipantsStats(): array
    {
        $sessions = $this->db->select('id, title')->from('%n', SessionsModel::T_SESSIONS)->fetchAssoc('id');
        $stats = [];
        foreach ($sessions as $sessionId => $session) {
            $stats[$session['title']][self::STATUS_ZAJEMCE] = $this->db->select('COUNT(application_id)')
                ->from('%n', self::T_PARTICIPANTS)
                ->leftJoin('%n ON applications.id = participants.application_id', ApplicationsModel::T_APPLICATIONS)
                ->leftJoin('%n ON sessions.id = applications.session', SessionsModel::T_SESSIONS)
                ->where('applications.session = %i AND participants.status = %i', $sessionId, self::STATUS_ZAJEMCE)
                ->fetchSingle();
            $stats[$session['title']][self::STATUS_GUEST] = $this->db->select('COUNT(application_id)')
                ->from('%n', self::T_PARTICIPANTS)
                ->leftJoin('%n ON applications.id = participants.application_id', ApplicationsModel::T_APPLICATIONS)
                ->leftJoin('%n ON sessions.id = applications.session', SessionsModel::T_SESSIONS)
                ->where('applications.session = %i AND participants.status = %i', $sessionId, self::STATUS_GUEST)
                ->fetchSingle();
            $stats[$session['title']][self::STATUS_CONFIRMED] = $this->db->select('COUNT(application_id)')
                ->from('%n', self::T_PARTICIPANTS)
                ->leftJoin('%n ON applications.id = participants.application_id', ApplicationsModel::T_APPLICATIONS)
                ->leftJoin('%n ON sessions.id = applications.session', SessionsModel::T_SESSIONS)
                ->where('applications.session = %i AND participants.status = %i', $sessionId, self::STATUS_CONFIRMED)
                ->fetchSingle();
        }
        return $stats;
    }

    /**
     * @return array
     */
    public function getParticipantsStatsNullSession(): array
    {
        $stats = [];
        $stats[self::STATUS_ZAJEMCE] = $this->db->select('COUNT(application_id)')
            ->from('%n', self::T_PARTICIPANTS)
            ->leftJoin('%n ON applications.id = participants.application_id', ApplicationsModel::T_APPLICATIONS)
            ->leftJoin('%n ON sessions.id = applications.session', SessionsModel::T_SESSIONS)
            ->where('applications.session IS NULL AND participants.status = %i', self::STATUS_ZAJEMCE)
            ->fetchSingle();
        $stats[self::STATUS_WAITING_FOR_ACTION] = $this->db->select('COUNT(application_id)')
            ->from('%n', self::T_PARTICIPANTS)
            ->leftJoin('%n ON applications.id = participants.application_id', ApplicationsModel::T_APPLICATIONS)
            ->leftJoin('%n ON sessions.id = applications.session', SessionsModel::T_SESSIONS)
            ->where('applications.session IS NULL AND participants.status = %i', self::STATUS_WAITING_FOR_ACTION)
            ->fetchSingle();

        return $stats;
    }

    /**
     * @param int $applicationId
     * @param int $madeBy
     * @throws Exception
     * @throws JsonException
     * @throws MailerSendAssertException
     * @throws ClientExceptionInterface
     */
    public function placeCheck(int $applicationId, int $madeBy)
    {
        $application = $this->db->select('*')->from('%n', ApplicationsModel::T_APPLICATIONS)->where('id = %i', $applicationId)->fetch();
        $mailValues = new ArrayHash();
        $mailValues['email'] = $application['email'];
        $mailValues['firstname'] = $application['firstname'];

        // Ověřit místa na zvoleném turnusu (free spots = (full capacity - instructors capacity) - confirmed participants)
        $freeSpots = $this->getFreeSpots($application['session']);

        // Pokud je na zvoleném turnusu místo, přiřaď na něj účastníka
        if ($freeSpots > 0) {
            // Tzn zmen stav ucastnika na confirmed a zaloguj
            $this->db->update(ParticipantsModel::T_PARTICIPANTS, ['status' => ParticipantsModel::STATUS_CONFIRMED])->where('application_id = %i', $applicationId)->execute();
            $oldStatus = ParticipantsModel::STATUS_GUEST;
            $this->logStatusChange($applicationId, $oldStatus, ParticipantsModel::STATUS_CONFIRMED, $madeBy, true);
            $this->mailerSendService->placeConfirmed($mailValues);
        // Pokud není a účastník má vybraný turnus, zařaď ho do pořadníku.
        } else {
            if (!is_null($application['session'])) {
                // Tzn nech status na 'Guest' a přiřaď queue number
                $this->enqueue($applicationId, $application['session']);
                $this->mailerSendService->placeFullEnqueued($mailValues);
            // Pokud nemá vybraný turnus, pak změň stav účastníka na 'Waiting for action'.
            } else {
                $this->db->update(ParticipantsModel::T_PARTICIPANTS, ['status' => ParticipantsModel::STATUS_WAITING_FOR_ACTION])->where('application_id = %i', $applicationId)->execute();
                $this->logStatusChange($applicationId, ParticipantsModel::STATUS_GUEST, ParticipantsModel::STATUS_WAITING_FOR_ACTION, $madeBy, true);
                $this->mailerSendService->placeFullWaitingForAction($mailValues);
            }
        }
    }

    /**
     * @param int $applicationId
     * @param int $oldStatus
     * @param int $newStatus
     * @param int $madeBy
     * @param bool $participant
     * @throws Exception
     */
    public function logStatusChange(int $applicationId, int $oldStatus, int $newStatus, int $madeBy, bool $participant = false)
    {
        $values['application_id'] = $applicationId;
        $values['column'] = 'Status';
        $values['made_by'] = $madeBy;
        if ($participant) {
            $values['action_type'] = 4;
            $values['old_value'] = $this->db->select('name')->from('%n', ParticipantsModel::T_PARTICIPANT_STATUS)->where('id = %i', $oldStatus)->fetchSingle();
            $values['new_value'] = $this->db->select('name')->from('%n', ParticipantsModel::T_PARTICIPANT_STATUS)->where('id = %i', $newStatus)->fetchSingle();
        } else {
            $values['action_type'] = 2; // Table application_action_type
            $values['old_value'] = $this->db->select('name')->from('%n', ApplicationsModel::T_APPLICATION_STATUS)->where('id = %i', $oldStatus)->fetchSingle();
            $values['new_value'] = $this->db->select('name')->from('%n', ApplicationsModel::T_APPLICATION_STATUS)->where('id = %i', $newStatus)->fetchSingle();
        }

        $this->db->insert(ApplicationsModel::T_APPLICATION_LOG, $values)->execute();
    }

    /**
     * @param int $applicationId
     * @param int $sessionId
     * @throws Exception
     */
    public function logParticipantEnqueued(int $applicationId, int $sessionId)
    {
        $values['application_id'] = $applicationId;
        $values['column'] = 'Pořadník';
        $values['made_by'] = AppComponent::SYSTEM_USER;
        $values['action_type'] = 6;
        $values['new_value'] = $this->getQueueOrder($applicationId, $sessionId);
        $this->db->insert(ApplicationsModel::T_APPLICATION_LOG, $values)->execute();
    }

    /**
     * @param int $applicationId
     * @throws Exception
     */
    public function logParticipantDequeued(int $applicationId)
    {
        $values['application_id'] = $applicationId;
        $values['column'] = 'Pořadník';
        $values['made_by'] = AppComponent::SYSTEM_USER;
        $values['action_type'] = 7;

        $this->db->insert(ApplicationsModel::T_APPLICATION_LOG, $values)->execute();
    }
}
