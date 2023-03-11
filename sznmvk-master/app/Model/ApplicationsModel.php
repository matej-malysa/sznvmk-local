<?php
declare(strict_types=1);

namespace App\Model;

use App\Classes\Mail\MessageCenter;
use App\Classes\MailerSend\MailerSendService;
use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
use Dibi\Row;
use Dibi\UniqueConstraintViolationException;
use JsonException;
use MailerSend\Exceptions\MailerSendAssertException;
use Nette\Schema\Elements\Structure;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Security\Passwords;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Psr\Http\Client\ClientExceptionInterface;

class ApplicationsModel extends BaseModel
{
    const T_APPLICATIONS = 'applications';
    const T_APPLICATION_STATUS = 'application_status';
    const T_APPLICATION_LOG = 'applications_log';
    const T_APPLICATION_ACTION_TYPE = 'application_action_type';
    const T_INVITED_FRIENDS = 'invited_friends';

    const STATUS_INSERTED_INSTRUCTOR = 1;
    const STATUS_INSERTED_PARTICIPANT = 2;
    const STATUS_PARTICIPANT = 3;
    const STATUS_DELETED = 4;

    const ACTION_CREATE = 1;
    const ACTION_EDIT = 2;
    const ACTION_DELETE = 3;
    const ACTION_EDIT_PARTICIPANT = 4;

    /** @var TransportModel */
    protected TransportModel $transportModel;

    /** @var AllergiesModel */
    protected AllergiesModel $allergiesModel;

    /** @var SessionsModel */
    protected SessionsModel $sessionsModel;

    /** @var BonusesModel */
    protected BonusesModel $bonusesModel;

    /** @var PaymentsModel */
    protected PaymentsModel $paymentsModel;

    /** @var PaymentsLimitsModel */
    protected PaymentsLimitsModel $paymentsLimitsModel;

    /** @var FacultiesModel */
    protected FacultiesModel $facultiesModel;

    /** @var ImportantDatesModel */
    protected ImportantDatesModel $importantDatesModel;

    /** @var ParticipantsModel */
    protected ParticipantsModel $participantsModel;

    /** @var GendersModel */
    protected GendersModel $gendersModel;

    /** @var Structure|Schema */
    protected Schema|Structure $applicationSchema;

    /** @var Structure|Schema */
    protected Schema|Structure $adminApplicationSchema;

    /** @var Structure|Schema */
    protected Schema|Structure $adminEditSchema;

    /** @var MailerSendService */
    protected MailerSendService $mailerSendService;

    protected Passwords $passwords;

    public function __construct(Connection $connection, TransportModel $transportModel, SessionsModel $sessionsModel, BonusesModel $bonusesModel, FacultiesModel $facultiesModel, PaymentsModel $paymentsModel, ImportantDatesModel $importantDatesModel, ParticipantsModel $participantsModel, MailerSendService $mailerSendService, Passwords $passwords,GendersModel $gendersModel, AllergiesModel $allergiesModel,  PaymentsLimitsModel $paymentsLimitsModel)
    {
        parent::__construct($connection);
        $this->transportModel = $transportModel;
        $this->sessionsModel = $sessionsModel;
        $this->bonusesModel = $bonusesModel;
        $this->facultiesModel = $facultiesModel;
        $this->paymentsModel = $paymentsModel;
        $this->importantDatesModel = $importantDatesModel;
        $this->participantsModel = $participantsModel;
        $this->mailerSendService = $mailerSendService;
        $this->passwords = $passwords;
        $this->gendersModel = $gendersModel;
        $this->allergiesModel = $allergiesModel;
        $this->paymentsLimitsModel = $paymentsLimitsModel;

        $this->applicationSchema = Expect::structure([
            'firstname' => Expect::string()->required()->max(180),
            'lastname' => Expect::string()->required()->max(180),
            'email' => Expect::email()->required(),
            'password' => Expect::string()->required()->max(180),
            'status' => Expect::int()->required(),
            'gender' => Expect::int()->required(),
            'phone' => Expect::string()->required()->min(13)->max(13),
            'vs' => Expect::string()->required()->min(9)->max(9),
            'birthdate' => Expect::type(DateTime::class)->before(function ($v) {
                return DateTime::from($v);
            }),
            'faculty' => Expect::int()->required(),
            'transport' => Expect::int()->required(),
            'session' => Expect::int()->required(),
            'created_by' => Expect::int(),
        ])->castTo(ArrayHash::class);

        $this->adminApplicationSchema = Expect::structure([
            'firstname' => Expect::string()->required()->max(180),
            'lastname' => Expect::string()->required()->max(180),
            'email' => Expect::email()->required(),
            'password' => Expect::string(),
            'status' => Expect::int()->required(),
            'gender' => Expect::int()->required(),
            'phone' => Expect::string()->required()->min(13)->max(13)->nullable(),
            'vs' => Expect::string()->required()->min(9)->max(9)->nullable(),
            'birthdate' => Expect::anyOf(null, Expect::type(DateTime::class)->before(function ($v) {
                return DateTime::from($v);
            })),
            'faculty' => Expect::int()->required()->nullable(),
            'transport' => Expect::int()->required()->nullable(),
            'session' => Expect::int()->required()->nullable(),
            'bonus' => Expect::int(),
            'created_by' => Expect::int(),
        ])->castTo(ArrayHash::class);

        $this->adminEditSchema = Expect::structure([
            'firstname' => Expect::string()->required()->max(180),
            'lastname' => Expect::string()->required()->max(180),
            'email' => Expect::email()->required(),
            'gender' => Expect::int()->required(),
            'password' => Expect::string(),
            'phone' => Expect::string()->required()->min(13)->max(13)->nullable(),
            'vs' => Expect::string()->required()->min(9)->max(9)->nullable(),
            'birthdate' => Expect::anyOf(null, Expect::type(DateTime::class)->before(function ($v) {
                return DateTime::from($v);
            })),
            'faculty' => Expect::int()->required()->nullable(),
            'transport' => Expect::int()->required()->nullable(),
            'session' => Expect::int()->nullable(),
            'bonus' => Expect::int(),
            'spz' => Expect::string()->nullable(),
        ])->castTo(ArrayHash::class);
    }

    /**
     * @param ArrayHash $values
     * @return int
     * @throws Exception
     */
    public function createApplicationApplicant(ArrayHash $values): int
    {
        $values['status'] = self::STATUS_INSERTED_PARTICIPANT;
        $values['vs'] = $values['phone'];
        $values['phone'] = $values['fullPhone'];
        unset($values['fullPhone'], $values['gdpr']);

        $values = $this->validate($this->applicationSchema, $values);
        return $this->createApplication($values);
    }

    /**
     * @param ArrayHash $values
     * @return int
     * @throws Exception
     */
    public function createApplicationAdmin(ArrayHash $values): int
    {
        if ($values['birthdate'] == '') {
            $values['birthdate'] = null;
        }

        if ($values['phone'] == '') {
            $values['phone'] = null;
            $values['fullPhone'] = null;
        }

        $values['status'] = self::STATUS_INSERTED_INSTRUCTOR;
        $values['vs'] = $values['phone'];
        $values['phone'] = $values['fullPhone'];
        unset($values['fullPhone'], $values['gdpr']);

        $values = $this->validate($this->adminApplicationSchema, $values);
        return $this->createApplication($values);
    }

    /**
     * @param ArrayHash $values
     * @return int
     * @throws Exception
     */
    public function createApplication(ArrayHash $values): int
    {
        if (!empty($this->db->select('id')->from('%n', self::T_APPLICATIONS)->where('email = %s', $values['email'])->fetchSingle())) {
            throw new UniqueConstraintViolationException();
        }
        $values['password'] = $this->passwords->hash($values['password']);
        $this->db->insert(self::T_APPLICATIONS, $values)->execute();
        $applicationId = $this->db->getInsertId();

        $this->logCreate($applicationId, $values['created_by']);
        return $applicationId;
    }

    /**
     * @param int $id
     * @param ArrayHash $values
     * @param int $madeBy
     * @throws Exception
     */
    public function editApplication(int $id, ArrayHash $values, int $madeBy)
    {

        if ($values['birthdate'] == '') {
            $values['birthdate'] = null;
        }

        if ($values['phone'] == '') {
            $values['phone'] = null;
            $values['fullPhone'] = null;
        } else {
            $values['phone'] = str_replace(' ', '', $values['phone']);
        }

        $values['vs'] = $values['phone'];
        $values['phone'] = $values['fullPhone'];
        unset($values['fullPhone']);

        $values = $this->validate($this->adminEditSchema, $values);
        $oldValues = $this->getApplicationRaw($id);

        if (empty($values['session'])) {
            unset($values['session']);
            unset($oldValues['session']);
        }

        $part = $this->participantsModel->getParticipantByApplicationId($id);
        if (!empty($part) && $part['status'] == ParticipantsModel::STATUS_WAITING_FOR_ACTION) {
            if (!empty($values['session'])) {
                $this->changeSession($id, $values['session']);
                unset($values['session']);
                unset($oldValues['session']);
            }
        }

        $values['password'] = $oldValues['password'];
        $this->db->update(self::T_APPLICATIONS, $values)->where('id = %i', $id)->execute();
        $this->logEdit($id, $madeBy, $oldValues, $values);
    }

    /**
     * @param int $id
     * @param ArrayHash $values
     * @throws Exception
     * @throws MailerSendAssertException
     */
    public function resendCreateApplicationMail(int $id, ArrayHash $values)
    {
        $generalInfo['sessions'] = $this->sessionsModel->getAllToRadioSelect();
        $generalInfo['dates'] = $this->importantDatesModel->getAll()->fetchAssoc('id');
        $generalInfo['prices'] = $this->paymentsLimitsModel->getAll()->fetchAssoc('id');

        $password = self::generateRandomPassword();
        $this->setNewPassword($id, $this->passwords->hash($password));
        $values['password'] = $password;

        $this->mailerSendService->createApplication($values, $generalInfo);
    }

    /**
     * @param int $applicationId
     * @param string $hash
     * @throws Exception
     */
    public function setNewPassword(int $applicationId, string $hash)
    {
        $this->db->update(self::T_APPLICATIONS, ['password' => $hash])->where('id = %i', $applicationId)->execute();
    }

    /**
     * @param int $id
     * @param int $madeBy
     * @throws Exception
     * @throws JsonException
     * @throws MailerSendAssertException
     * @throws ClientExceptionInterface
     */
    public function deleteApplication(int $id, int $madeBy)
    {
        // Check for assigned payments
        $assignedPayments = $this->db->select('id, amount')->from(PaymentsModel::T_PAYMENTS)->where('application_id = %i', $id)->fetchPairs();
        $application = $this->getApplication($id);
        if (!empty($assignedPayments)) {
          // If there are any, check deadlines for refunding
            $refundDates = $this->importantDatesModel->getRefundDates();
            $mailValues = new ArrayHash();
            $mailValues['firstname'] = $application['firstname'];
            $mailValues['email'] = $application['email'];
            $mailValues['refundDate'] = date_format($refundDates[ImportantDatesModel::REFUNDACE]['deadline'], 'd.m.Y');
            $mailValues['deadlineZaloha'] = date_format($refundDates[ImportantDatesModel::ZALOHA_1]['deadline'], 'd.m.Y');
            $mailValues['deadlineDoplatek'] = date_format($refundDates[ImportantDatesModel::DOPLATEK]['deadline'], 'd.m.Y');

            // Do terminu "Zaloha 1" vracime vse
            if (date('Y-m-d') <= $refundDates[ImportantDatesModel::ZALOHA_1]['deadline']) {
                foreach ($assignedPayments as $paymentId => $amount) {
                    $this->paymentsModel->deletePayment($paymentId, $madeBy);
                }
                $this->mailerSendService->stornoFull($mailValues);
                //$this->messageCenter->createPaymentFullStornoMail(['email' => $email, 'refundDate' => $refundDates[ImportantDatesModel::REFUNDACE]['deadline']]);
            // Do terminu "Doplatek" vracime pouze doplatek, zalohu ne
            } elseif (date('Y-m-d') <= $refundDates[ImportantDatesModel::DOPLATEK]['deadline']) {
                foreach ($assignedPayments as $paymentId => $amount) {
                    // Oznac statusem "Nahrazeno"
                    $this->paymentsModel->markPaymentAsReplaced($paymentId, $madeBy);
                }
                // Vytvor novou platbu se statusem "Oznaceno k refundaci", s udaji podle puvodnich plateb
                $this->paymentsModel->createReplacementPayment($assignedPayments, $madeBy);
                // Posli mail ze se vraci jen doplatek
                $this->mailerSendService->stornoDoplatek($mailValues);
                //$this->messageCenter->createPaymentZalohaStornoMail(['email' => $email, 'refundDate' => $refundDates[ImportantDatesModel::REFUNDACE]['deadline'], 'fullDeadline' => $refundDates[ImportantDatesModel::ZALOHA_1]['deadline']]);

            // Po terminu "Doplatek" nevracime nic, tedy žádná akce není potřeba.
            } else {
                // Posli mail ze se uz nic nevraci
                $this->mailerSendService->stornoAfterDeadline($mailValues);
                //$this->messageCenter->createPaymentNoRefundStornoMail(['email' => $email, 'deadline' => $refundDates[ImportantDatesModel::DOPLATEK]['deadline']]);
            }
        }

        // Recount queue of participants session
        if (!empty($application['session'])) {
            $this->participantsModel->recountQueue($application['session']);
        }

        // Check if this application is already participant, if so delete
        $this->db->delete(ParticipantsModel::T_PARTICIPANTS)->where('application_id = %i', $id)->execute();

        // Set application status to Deleted, log
        $this->db->update(self::T_APPLICATIONS, ['status' => self::STATUS_DELETED])->where('id = %i', $id)->execute();
        $this->logDelete($id, $madeBy);
    }

    /**
     * @param int $id
     * @param int $madeBy
     * @throws ClientExceptionInterface
     * @throws Exception
     * @throws JsonException
     * @throws MailerSendAssertException
     */
    public function restoreApplication(int $id, int $madeBy)
    {
        $participant = $this->db->select('new_value')
            ->from('%n', self::T_APPLICATION_LOG)
            ->where("`application_id` = %i AND `action_type` = 2 AND `column` = 'Status'", $id)
            ->fetch();

        // Pokud ucastnik nebyl vytvoren, pak obnov jen prihlasku
        if (empty($participant)) {
            $create = $this->db->select('made_by')
                ->from('%n', self::T_APPLICATION_LOG)
                ->where('application_id = %i AND action_type = 1', $id)
                ->fetchSingle();

            // Pokud byla prihlaska vytvorena uzivatelem 1, pak je puvodni application_status=2, jinak 1
            $restoredStatus = $create == 1 ? self::STATUS_INSERTED_PARTICIPANT : self::STATUS_INSERTED_INSTRUCTOR;

            $this->db->update(self::T_APPLICATIONS, ['status' => $restoredStatus])->where('id = %i', $id)->execute();
            $this->logRestore($id, $madeBy);
        } else {
            // Nacti jeho platby
            $assignedPayments = $this->db->select('id, status')->from(PaymentsModel::T_PAYMENTS)->where('application_id = %i', $id)->fetchAll();
            $isReplaced = false;
            foreach ($assignedPayments as $payment) {
                $isReplaced = $payment['status'] == PaymentsModel::STATUS_REPLACED;
            }
            // Pokud nejaka z nich ma status 5 - preved vsechny na status 2 a smaz platbu se stavem 3
            if ($isReplaced) {
                foreach ($assignedPayments as $payment) {
                    if ($payment->status == PaymentsModel::STATUS_REPLACED) {
                        $this->db->update(PaymentsModel::T_PAYMENTS, ['status' => PaymentsModel::STATUS_JOINED])->where('id = %i', $payment->id)->execute();
                        $this->paymentsModel->logPaymentStatusChange($payment->id, 3, $madeBy, PaymentsModel::STATUS_REPLACED, PaymentsModel::STATUS_JOINED);
                    } elseif ($payment->status == PaymentsModel::STATUS_K_REFUNDACI) {
                        $this->db->delete(PaymentsModel::T_PAYMENTS)->where('id = %i', $payment->id)->execute();
                    }
                }
            // Pokud zadna z nich nema status 5 - preved vsechny na status 2
            } else {
                foreach ($assignedPayments as $payment) {
                    $oldStatus = $payment->status;
                    $this->db->update(PaymentsModel::T_PAYMENTS, ['status' => PaymentsModel::STATUS_JOINED])->where('id = %i', $payment->id)->execute();
                    $this->paymentsModel->logPaymentStatusChange($payment->id, 3, $madeBy, $oldStatus, PaymentsModel::STATUS_JOINED);
                }
            }

            $this->db->update(self::T_APPLICATIONS, ['status' => self::STATUS_PARTICIPANT])->where('id = %i', $id)->execute();
            $this->participantsModel->createParticipantFromApplication($id);
            $this->logRestoreParticipant($id, $madeBy);

            // Pokud byl ucastnik v okamziku smazani v jinem stavu nez "zajemce"
            $participantChangeStatus = $this->db->select('new_value')->from('%n', self::T_APPLICATION_LOG)->where('application_id = %i AND action_type = %i', $id, self::ACTION_EDIT_PARTICIPANT)->fetch();
            if (!empty($participantChangeStatus)) {
                // Nastav mu vzdy status Guest
                $this->db->update(ParticipantsModel::T_PARTICIPANTS, ['status' => ParticipantsModel::STATUS_GUEST])->where('application_id = %i', $id)->execute();
                // A spust kontrolu mista
                $this->participantsModel->placeCheck($id, $madeBy);
            }
        }
    }

    /**
     * @return Fluent
     */
    public function getApplicationsAll(): Fluent
    {
        return $this->db->select('app.*, statuses.name AS statusName, faculties.code AS facultyCode, transport_types.name AS transportName')
            ->from('%n', self::T_APPLICATIONS)->as('app')
            ->leftJoin('%n AS statuses ON app.status = statuses.id', self::T_APPLICATION_STATUS)
            ->leftJoin('%n ON app.faculty = faculties.id', FacultiesModel::T_FACULTIES)
            ->leftJoin('%n ON app.transport = transport_types.id', TransportModel::T_TRANSPORTS);
    }

    /**
     * @return array
     */
    public function getEmailsOfNotPaidApplications(): array
    {
        $array = array();
        $unis =  $this->db->select('id,name')
            ->from('%n AS app', FacultiesModel::T_SCHOOLS)
            ->fetchAll();
        foreach ($unis as $uni)
        {
            $array[$uni['name']] = $this->db->select('email')
                ->from('%n AS app', self::T_APPLICATIONS)
                ->leftJoin('%n ON app.id = payments.application_id', PaymentsModel::T_PAYMENTS)
                ->leftJoin('%n as fac ON app.faculty = fac.id', FacultiesModel::T_FACULTIES)
                ->where('payments.application_id IS NULL and fac.school = %i',$uni['id'])
                ->fetchAll();
        }
        return $array;
    }

    /**
     * @return array
     */
    public function getApplicationsToSelect(): array
    {
        return $this->db->select("id, IF(phone IS NOT NULL, CONCAT(lastname,' ', firstname, ' - Tel: ', phone, ', E-mail: ', email), CONCAT(lastname,' ', firstname, ' - Tel: nezadán, E-mail: ', email)) AS name")
            ->from('%n', self::T_APPLICATIONS)
            ->where('status != %i', self::STATUS_DELETED)
            ->orderBy('lastname')
            ->fetchPairs();
    }

    /**
     * @return Fluent
     */
    public function getApplicationsActive(): Fluent
    {
        return $this->getApplicationsAll()
            ->where('app.status != %i', self::STATUS_DELETED);
    }

    /**
     * @return Fluent
     */
    public function getApplicationsDeleted(): Fluent
    {
        return $this->getApplicationsAll()
            ->where('app.status = %i', self::STATUS_DELETED);
    }

    /**
     * @param string $email
     * @return Row|false|null
     */
    public function getByEmail(string $email): Row|false|null
    {
        return $this->db->select('*')->from('%n', self::T_APPLICATIONS)->where('email LIKE %s', $email)->fetch();
    }

    public function getEmailByID(int $id)
    {
        return $this->db->select('email')->from('%n', self::T_APPLICATIONS)->where('id = %i', $id)->fetchSingle();
    }

    /**
     * @param int $id
     * @return Row|null
     */
    public function getApplication(int $id): ?Row
    {
        return $this->db->select('app.*, statuses.name AS statusName, transport_types.name AS transportName, bonuses.name AS bonusName, sessions.title AS sessionTitle')
            ->from('%n', self::T_APPLICATIONS)->as('app')
            ->leftJoin('%n AS statuses ON app.status = statuses.id', self::T_APPLICATION_STATUS)
            ->leftJoin('%n ON app.transport = transport_types.id', TransportModel::T_TRANSPORTS)
            ->leftJoin('%n ON app.bonus = bonuses.id', BonusesModel::T_BONUSES)
            ->leftJoin('%n ON app.session = sessions.id', SessionsModel::T_SESSIONS)
            ->where('app.id = %i', $id)->fetch();
    }

    /**
     * @param int $id
     * @return Row
     */
    public function getApplicationRaw(int $id): Row
    {
        return $this->db->select('faculty, firstname, lastname, email, password, gender, birthdate, phone, session, transport, bonus, spz')
            ->from('%n', self::T_APPLICATIONS)
            ->where('id = %i', $id)
            ->fetch();
    }

    /**
     * @return array
     */
    public function getApplicationStatusesToSelect(): array
    {
        return $this->db->select('id, name')->from('%n', self::T_APPLICATION_STATUS)->fetchPairs();
    }

    /**
     * @return array
     */
    public function getApplicationStatusesToSelectNotDeleted(): array
    {
        return $this->db->select('id, name')->from('%n', self::T_APPLICATION_STATUS)->where('id != %i', self::STATUS_DELETED)->fetchPairs();
    }

    /**
     * @return array
     */
    public function exportApplicationsEverything(): array
    {
        return $this->db->select('app.id, app.firstname, app.lastname, app.email, appSt.name AS appStatus, app.phone, 
        app.birthdate, IF(app.faculty != %i, fac.name, schools.name) AS faculty, tran.name AS transport, sess.title AS session, bonuses.name AS bonus, 
        app.created_at, app.spz, partSt.name AS partStatus, partSt.status_text AS partStatusExplain, part.queue AS queue', FacultiesModel::NO_SCHOOL_ID)
            ->from('%n AS app', self::T_APPLICATIONS)
            ->leftJoin('%n AS appSt ON appSt.id = app.status', self::T_APPLICATION_STATUS)
            ->leftJoin('%n AS fac ON fac.id = app.faculty', FacultiesModel::T_FACULTIES)
            ->leftJoin('%n ON fac.school = schools.id', FacultiesModel::T_SCHOOLS)
            ->leftJoin('%n AS tran ON tran.id = app.transport', TransportModel::T_TRANSPORTS)
            ->leftJoin('%n AS sess ON sess.id = app.session', SessionsModel::T_SESSIONS)
            ->leftJoin('%n ON bonuses.id = app.bonus', BonusesModel::T_BONUSES)
            ->leftJoin('%n AS part ON part.application_id = app.id', ParticipantsModel::T_PARTICIPANTS)
            ->leftJoin('%n AS partSt ON partSt.id = part.status', ParticipantsModel::T_PARTICIPANT_STATUS)
            ->orderBy('app.id')
            ->fetchAll();
    }

    /**
     * @param int $length
     * @return string
     */
    public static function generateRandomPassword(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $randomString;
    }


     /**
     * @return array
     */
    public function getApplicationsConfirmParticipantsNullGender(): array
    {
        return $this->db->select("app.id, app.firstname,app.lastname,app.gender,
            IF(app.status = 3,par_sts.name,IF(app.status = 4,'Smazaný','Prihlásený') )as status")
            ->from('%n', self::T_APPLICATIONS)->as('app')
            ->leftJoin('%n as app_sts ON app.status = app_sts.id',self::T_APPLICATION_STATUS)
            ->leftJoin('%n as par ON app.id = par.application_id',ParticipantsModel::T_PARTICIPANTS)
            ->leftJoin('%n as par_sts ON par_sts.id = par.status',ParticipantsModel::T_PARTICIPANT_STATUS)
            ->where('gender is null')
            ->orderBy("(CASE app.status
                                WHEN 3 	 THEN 1
                                WHEN 1 	 THEN 2
                                WHEN 2 	 THEN 3
                                WHEN 4 THEN 4
                                END) ASC,
                              (CASE par.status
                                WHEN 3 	 THEN 1
                                WHEN 4 	 THEN 2
                                WHEN 2 	 THEN 3
                                WHEN 1 THEN 4
                                ELSE 100 END) ASC,
                                app.id")
            ->fetchAll();
    }

    /**
     * @param int $applicationId
     * @param int $sessionId
     * @param int $madeBy
     * @throws Exception
     */
    public function changeSession(int $applicationId, int $sessionId, int $madeBy = 1)
    {
        // Change session
        $old = $this->db->select('session')->from('%n', self::T_APPLICATIONS)->where('id = %i', $applicationId)->fetchSingle();
        $this->db->update(self::T_APPLICATIONS, ['session' => $sessionId])->where('id = %i', $applicationId)->execute();
        $this->logEdit($applicationId, $madeBy, ['session' => $old], ArrayHash::from(['session' => $sessionId]));
        // Change participant status
        if ($this->participantsModel->getFreeSpots($sessionId) > 0) {
            $this->db->update(ParticipantsModel::T_PARTICIPANTS, ['status' => ParticipantsModel::STATUS_CONFIRMED, 'queue' => null])->where('application_id = %i', $applicationId)->execute();
            $this->participantsModel->logStatusChange($applicationId, ParticipantsModel::STATUS_WAITING_FOR_ACTION, ParticipantsModel::STATUS_CONFIRMED, $madeBy, true);
        } else {
            $this->db->update(ParticipantsModel::T_PARTICIPANTS, ['status' => ParticipantsModel::STATUS_GUEST])->where('application_id = %i', $applicationId)->execute();
            $this->participantsModel->logStatusChange($applicationId, ParticipantsModel::STATUS_WAITING_FOR_ACTION, ParticipantsModel::STATUS_GUEST, $madeBy, true);
            $this->participantsModel->enqueue($applicationId, $sessionId);
        }
    }

    public function ScriptAddGender(int $gender, int $id)
    {
        $this->db->update(self::T_APPLICATIONS, ['gender' => $gender])->where('id = %i', $id)->execute();
        $this->logEdit($id, 1, ['gender' => NULL], ArrayHash::from(['gender' => $gender]));
    }

    /* ************** */
    /* LOG ********** */
    /* ************** */

    /**
     * @param int $applicationId
     * @return array
     */
    public function getApplicationsLogGrid(int $applicationId): array
    {
        return $this->db->select('logs.*, action.text, users.username')->from('%n AS logs', self::T_APPLICATION_LOG)
            ->leftJoin('%n AS action ON logs.action_type = action.id', self::T_APPLICATION_ACTION_TYPE)
            ->leftJoin('%n ON logs.made_by = users.id', UserModel::T_USERS)
            ->where('logs.application_id = %i', $applicationId)
            ->orderBy('logs.created_at')
            ->fetchAll();
    }

    /**
     * @param int $applicationId
     * @param int $madeBy
     * @throws Exception
     */
    public function logCreate(int $applicationId, int $madeBy)
    {
        $values['application_id'] = $applicationId;
        $values['made_by'] = $madeBy;
        $values['action_type'] = 1; // Table application_action_type

        $this->db->insert(self::T_APPLICATION_LOG, $values)->execute();
    }

    /**
     * @param int $applicationId
     * @param int $madeBy
     * @param iterable $oldValues
     * @param ArrayHash $newValues
     * @throws Exception
     */
    public function logEdit(int $applicationId, int $madeBy, iterable $oldValues, ArrayHash $newValues)
    {
        $values['application_id'] = $applicationId;
        $values['made_by'] = $madeBy;
        $values['action_type'] = 2;

        foreach ($oldValues as $column => $value) {
            if ($value != $newValues[$column]) {
                $values['column'] = $column;

                switch ($column) {
                    case "faculty":
                        if (!empty($value)) {
                            $values['old_value'] = $this->facultiesModel->getSchoolCodeAndFacultyCode($oldValues[$column]);
                        } else {
                            $values['old_value'] = $value;
                        }
                        $values['new_value'] = $this->facultiesModel->getSchoolCodeAndFacultyCode($newValues[$column]);
                        break;
                    case "birthdate":
                        if (!empty($value)) {
                            $values['old_value'] = $oldValues[$column]->format('d.m.Y');
                        } else {
                            $values['old_value'] = $value;
                        }
                        $values['new_value'] = $newValues[$column]->format('d.m.Y');
                        break;
                    case "transport":
                        if (!empty($value)) {
                            $values['old_value'] = $this->transportModel->getNameById($oldValues[$column]);
                        } else {
                            $values['old_value'] = $value;
                        }
                        $values['new_value'] = $this->transportModel->getNameById($newValues[$column]);
                        break;
                    case "session":
                        if (!empty($value)) {
                            $values['old_value'] = $this->sessionsModel->getNameById($oldValues[$column]);
                        } else {
                            $values['old_value'] = $value;
                        }
                        $values['new_value'] = $this->sessionsModel->getNameById($newValues[$column]);
                        break;
                    case "bonus":
                        if (!empty($value)) {
                            $values['old_value'] = $this->bonusesModel->getNameById($oldValues[$column]);
                        } else {
                            $values['old_value'] = $value;
                        }
                        $values['new_value'] = $this->bonusesModel->getNameById($newValues[$column]);
                        break;
                    case "gender":
                        if (!empty($value)) {
                            $values['old_value'] = $this->gendersModel->getNameById($oldValues[$column]);
                        } else {
                            $values['old_value'] = $value;
                        }
                        $values['new_value'] = $this->gendersModel->getNameById($newValues[$column]);
                        break;
                    default:
                        $values['old_value'] = $oldValues[$column];
                        $values['new_value'] = $newValues[$column];
                }
                $this->db->insert(self::T_APPLICATION_LOG, $values)->execute();
            }
        }
    }

    /**
     * @param int $applicationId
     * @param int $madeBy
     * @throws Exception
     */
    public function logDelete(int $applicationId, int $madeBy)
    {
        $values['application_id'] = $applicationId;
        $values['made_by'] = $madeBy;
        $values['action_type'] = 3; // Table application_action_type

        $this->db->insert(self::T_APPLICATION_LOG, $values)->execute();
    }

    /**
     * @param int $applicationId
     * @param int $madeBy
     * @throws Exception
     */
    public function logRestore(int $applicationId, int $madeBy)
    {
        $values['application_id'] = $applicationId;
        $values['made_by'] = $madeBy;
        $values['action_type'] = 9; // Table application_action_type

        $this->db->insert(self::T_APPLICATION_LOG, $values)->execute();
    }

    /**
     * @param int $applicationId
     * @param int $madeBy
     * @throws Exception
     */
    public function logRestoreParticipant(int $applicationId, int $madeBy)
    {
        $values['application_id'] = $applicationId;
        $values['made_by'] = $madeBy;
        $values['action_type'] = 10; // Table application_action_type

        $this->db->insert(self::T_APPLICATION_LOG, $values)->execute();
    }

    /**
     * @param int $id
     */
    public function getInvitedFriends(int $id): array
    {
        $array = $this->db->select('inv.email, par.status')
            ->from('%n as inv',self::T_INVITED_FRIENDS)
            ->leftJoin('%n as app ON app.email = inv.email', self::T_APPLICATIONS)
            ->leftJoin('%n as par ON par.application_id = app.id', ParticipantsModel::T_PARTICIPANTS)
            ->where('inv.application_id = %i',$id)
            ->fetchAll();

        return $array;
    }

    /**
     * @param int $id
     */
    public function InviteFriend(int $id,string $email): int
    {
        //Pozvanie sameho seba.
        $oldEmail = $this->db->select('app.email')
            ->from('%n as app ', self::T_APPLICATIONS)
            ->where('app.id = %i',$id)
            ->fetchSingle();
        if($oldEmail == $email)
            return 2;

        //Max pozvanok.
        $invicnt = count($this->db->select('inv.email')
            ->from('%n as inv ', self::T_INVITED_FRIENDS)
            ->where('inv.application_id = %i',$id)
            ->fetchAll());
        if($invicnt >= 12)
            return 3;

        //Navzajom.
        $appEmail = $this->db->select('app.email')
        ->from('%n as app ', self::T_APPLICATIONS)
        ->where('app.id = %i',$id)
        ->fetchSingle();
        $inviId = $this->db->select('app.id')
            ->from('%n as app ', self::T_APPLICATIONS)
            ->where('app.email = %s',$email)
            ->fetchSingle();

        $invicnt = count($this->db->select('inv.email')
            ->from('%n as inv ', self::T_INVITED_FRIENDS)
            ->where('inv.application_id = %i and inv.email = %s',$inviId,$appEmail)
            ->fetchAll());
        if($invicnt >= 1)
            return 4;

        //Email uz bol pozvany.
        $emailcnt = count($this->db->select('inv.email')
            ->from('%n as inv ', self::T_INVITED_FRIENDS)
            ->where('inv.email = %s',$email)
            ->fetchAll());
        if($emailcnt >= 1)
            return 5;

        $status = $this->db->select('par.status')
            ->from('%n as app ', self::T_APPLICATIONS)
            ->leftJoin('%n as par ON par.application_id = app.id', ParticipantsModel::T_PARTICIPANTS)
            ->where('app.email = %s',$email)
            ->fetchSingle();

        if($status !== 3)
        {
            $this->db->insert(self::T_INVITED_FRIENDS, ['application_id' => $id, 'email'=>$email])->execute();
            return 0;
        }
        else{
            return 1;
        }


    }
}
