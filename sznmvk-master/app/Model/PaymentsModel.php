<?php
declare(strict_types=1);

namespace App\Model;

use App\Classes\Exceptions\AddNewPaymentException;
use App\Classes\Mail\MessageCenter;
use App\Classes\MailerSend\MailerSendService;
use Dibi\Connection;
use Dibi\Exception;
use Dibi\Fluent;
use JsonException;
use MailerSend\Exceptions\MailerSendAssertException;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Psr\Http\Client\ClientExceptionInterface;

class PaymentsModel extends BaseModel
{
    const T_PAYMENTS = 'payments';
    const T_PAYMENTS_ACTION_TYPE = 'payment_action_type';
    const T_PAYMENT_STATUS = 'payment_status';
    const T_PAYMENTS_LOG = 'payments_log';

    const STATUS_RECEIVED = 1;
    const STATUS_JOINED = 2;
    const STATUS_K_REFUNDACI = 3;
    const STATUS_REPLACED = 5;

    const OUR_ACCOUNTS = ['2400430631/2010'];

    /** @var ParticipantsModel  */
    protected ParticipantsModel $participantsModel;

    /** @var PaymentsLimitsModel */
    protected PaymentsLimitsModel $paymentsLimitsModel;

    /** @var MessageCenter */
    protected MessageCenter $messageCenter;

    /** @var MailerSendService */
    protected MailerSendService $mailerSendService;

    /** @var Schema */
    protected Schema $newPaymentSchema;

    public function __construct(Connection $connection, ParticipantsModel $participantsModel, PaymentsLimitsModel $paymentsLimitsModel, MessageCenter $messageCenter, MailerSendService $mailerSendService)
    {
        parent::__construct($connection);
        $this->participantsModel = $participantsModel;
        $this->paymentsLimitsModel = $paymentsLimitsModel;
        $this->messageCenter = $messageCenter;
        $this->mailerSendService = $mailerSendService;
        $this->newPaymentSchema = Expect::structure([
            'transaction_id' => Expect::int()->required(),
            'account_number' => Expect::string()->required(),
            'bank_code' => Expect::string()->nullable(),
            'account_name' => Expect::string()->required(),
            'amount' => Expect::float()->required(),
            'currency' => Expect::string()->required(),
            'vs' => Expect::int()->nullable(),
            'ks' => Expect::int()->nullable(),
            'message' => Expect::string()->nullable(),
            'payment_date' => Expect::type(DateTime::class)->before(function ($v) {
                return DateTime::from($v);
            }),
            'status' => Expect::int()->required(),
        ])->castTo(ArrayHash::class);
    }

    /**
     * @return Fluent
     */
    public function getGrid(): Fluent
    {
        return $this->db->select('payments.*, status.name AS statusName')
            ->from('%n', self::T_PAYMENTS)
            ->leftJoin('%n AS status', self::T_PAYMENT_STATUS)->on('status.id = payments.status')
            ->orderBy(['status', 'payment_date', 'id']);
    }

    /**
     * @return array
     */
    public function getStatusesForSelect(): array
    {
        return $this->db->select('*')->from('%n', self::T_PAYMENT_STATUS)->fetchPairs();
    }

    /**
     * @param int $applicationId
     * @return array
     */
    public function getPaymentsForApplication(int $applicationId): array
    {
        return $this->db->select('*')
            ->from('%n', self::T_PAYMENTS)
            ->where('application_id = %i && status != %i', $applicationId, self::STATUS_K_REFUNDACI)
            ->fetchAll();
    }

    /**
     * @param int $applicationId
     * @return float
     */
    public function getPaymentsSumForApplication(int $applicationId): float
    {
        $sum = $this->db->select('SUM(amount)')->from('%n', self::T_PAYMENTS)->where('application_id = %i', $applicationId)->fetchSingle();
        if (!empty($sum)) {
            return (int) $sum;
        } else {
            return 0;
        }
    }

    /**
     * @param int $paymentId
     * @return \Dibi\Row|null
     */
    public function getDetails(int $paymentId)
    {
        return $this->db->select('*')
            ->from('%n', self::T_PAYMENTS)
            ->where('id = %i', $paymentId)
            ->fetch();
    }

    /**
     * @param int $statusId
     * @return string
     */
    public function getPaymentsStatusName(int $statusId): string
    {
        return $this->db->select('name')->from('%n', self::T_PAYMENT_STATUS)->where('id = %i', $statusId)->fetchSingle();
    }

    /**
     * @return int
     */
    public function getLastMoveId(): int
    {
        $lastMoveId = $this->db->select('MAX(transaction_id)')->from('%n', self::T_PAYMENTS)->fetchSingle();
        if (empty($lastMoveId)) {
            return 0;
        } else {
            return $lastMoveId;
        }
    }

    /**
     * @return array
     */
    public function getAvailableForAutoAssign(): array
    {
        return $this->db->select('applications.*, applications.id AS applicationId, payments.*, payments.id AS paymentId')
            ->from('%n', self::T_PAYMENTS)
            ->join('%n ON payments.vs = applications.vs', ApplicationsModel::T_APPLICATIONS)
            ->where('payments.application_id IS NULL AND payments.vs != 0')
            ->orderBy('paymentId')->fetchAssoc('paymentId');
    }

    /**
     * @param int $paymentId
     * @return array
     */
    public function getPaymentsLogGrid(int $paymentId): array
    {
        return $this->db->select('log.*, action.text, users.username')->from('%n AS log', self::T_PAYMENTS_LOG)
            ->leftJoin('%n AS action ON action.id = log.action_type', self::T_PAYMENTS_ACTION_TYPE)
            ->leftJoin('%n ON log.made_by = users.id', UserModel::T_USERS)
            ->where('payment_id = %i', $paymentId)
            ->orderBy('log.created_at')
            ->fetchAll();
    }

    /**
     * @param int $applicationId
     * @return bool
     */
    public function anyPaymentsForStorno(int $applicationId): bool
    {
        $select = $this->db->select('application_id')->from('%n', self::T_PAYMENTS)->where('application_id = %i AND status IN %in', $applicationId, [self::STATUS_K_REFUNDACI, self::STATUS_REPLACED])->fetchSingle();
        if (empty($select)) {
            return false;
        } else {
            return true;
        }
    }


    /**
     * @param int $paymentId
     * @param int $applicationId
     * @param int $madeBy
     * @param bool $auto
     * @throws Exception
     * @throws JsonException
     * @throws MailerSendAssertException
     * @throws ClientExceptionInterface
     */
    public function assignPayment(int $paymentId, int $applicationId, int $madeBy, bool $auto = false)
    {
        // Zapis platbu k prihlasce a zmen stav platby, zaloguj k platbe
        $this->db->update(self::T_PAYMENTS, ['application_id' => $applicationId, 'status' => self::STATUS_JOINED])->where('id = %i', $paymentId)->execute();
        $this->logPaymentStatusChange($paymentId, 1, $madeBy);

        $application = $this->db->select('*')->from('%n', ApplicationsModel::T_APPLICATIONS)->where('id = %i', $applicationId)->fetch();
        $participant = $this->db->select('*')->from('%n', ParticipantsModel::T_PARTICIPANTS)->where('application_id = %i', $applicationId)->fetch();
        $limits = $this->db->select('name, amount')->from('%n', PaymentsLimitsModel::T_PAYMENTS_LIMITS)->fetchPairs();
        $paymentsSum = $this->getPaymentsSumForApplication($applicationId);
        $payment = $this->getDetails($paymentId);

        // Je uz zalozeny ucastnik?
        if (empty($participant)) {
            // Pokud ne a pokud je celkova zaplacena castka navazana na prihlasku vyssi nez limit pro zalohu, zaloz ucastnika
            if ($paymentsSum >= $limits['Záloha']) {
                $this->participantsModel->createParticipantFromApplication($applicationId);
                // Zmen stav prihlasky na ucastnika a zaloguj k prihlasce
                $oldStatus = $application['status'];
                $this->db->update(ApplicationsModel::T_APPLICATIONS, ['status' => ApplicationsModel::STATUS_PARTICIPANT])->where('id = %i', $applicationId)->execute();
                $application = $this->db->select('*')->from('%n', ApplicationsModel::T_APPLICATIONS)->where('id = %i', $applicationId)->fetch();
                $this->participantsModel->logStatusChange($applicationId, $oldStatus, ApplicationsModel::STATUS_PARTICIPANT, $madeBy);
            }
        }

        $mailValues = new ArrayHash();
        $mailValues->email = $application['email'];
        $mailValues->firstname = $application['firstname'];
        $mailValues->platba_kolik = $payment['amount'];

        // Je soucet plateb prihlasky vyssi nebo roven celkove castce?
        if ($paymentsSum >= $limits['Celá cena']) {
            // Zmen stav ucastnika, zaloguj
            $participant = $this->db->select('*')->from('%n', ParticipantsModel::T_PARTICIPANTS)->where('application_id = %i', $applicationId)->fetch();
            $oldStatus = $participant['status'];
            $this->db->update(ParticipantsModel::T_PARTICIPANTS, ['status' => ParticipantsModel::STATUS_GUEST])->where('application_id = %i', $applicationId)->execute();
            $this->participantsModel->logStatusChange($applicationId, $oldStatus, ParticipantsModel::STATUS_GUEST, $madeBy, true);

            // Odesli mail potvrzeni o zaplaceni cele castky
            $this->mailerSendService->paymentReceivedFull($mailValues);
            //$this->messageCenter->createFullReceivedMail(['email' => $application['email'], 'amount' => $payment['amount']]);
            $this->participantsModel->placeCheck($applicationId, $madeBy);
        } else {
            // Odesli mail s potvrzenim o zaplaceni
            $this->mailerSendService->paymentReceived($mailValues);
            //$this->messageCenter->createPaymentReceivedMail(['email' => $application['email'], 'amount' => $payment['amount']]);
        }
    }


    /**
     * @param array $params
     * @return int
     * @throws AddNewPaymentException
     * @throws Exception
     */
    public function addNewPayment(array $params): int
    {
        $payment['transaction_id'] = $params['moveId'];
        $payment['account_number'] = $params['toAccount'];
        $payment['bank_code'] = $params['bankCode'];
        $payment['account_name'] = $params['nameAccountTo'];
        $payment['amount'] = $params['volume'];
        $payment['currency'] = $params['currency'];
        if (!empty($params['variableSymbol'])) {
            $payment['vs'] = (int)$params['variableSymbol'];
        }
        if (!empty($params['constantSymbol'])) {
            $payment['ks'] = (int)$params['constantSymbol'];
        }
        $payment['message'] = $params['messageTo'];
        $payment['payment_date'] = $params['moveDate'];
        $payment['status'] = self::STATUS_RECEIVED;

        try {
            $payment = $this->validate($this->newPaymentSchema, $payment);
            $this->db->insert(self::T_PAYMENTS, $payment)->execute();
        } catch (Exception $ex) {
            throw new AddNewPaymentException();
        }

        return $this->db->getInsertId();
    }

    /**
     * @param int $id
     * @param int $madeBy
     * @throws Exception
     */
    public function deletePayment(int $id, int $madeBy)
    {
        $oldStatus = $this->db->select('status')->from('%n', self::T_PAYMENTS)->where('id = %i', $id)->fetchSingle();
        $this->db->update(self::T_PAYMENTS, ['status' => self::STATUS_K_REFUNDACI])->where('id = %i', $id)->execute();
        $this->logPaymentStatusChange($id, 3, $madeBy, $oldStatus, self::STATUS_K_REFUNDACI);
    }

    /**
     * @param int $id
     * @param int $madeBy
     * @throws Exception
     */
    public function markPaymentAsReplaced(int $id, int $madeBy)
    {
        $oldStatus = $this->db->select('status')->from('%n', self::T_PAYMENTS)->where('id = %i', $id)->fetchSingle();
        $this->db->update(self::T_PAYMENTS, ['status' => self::STATUS_REPLACED])->where('id = %i', $id)->execute();
        $this->logPaymentStatusChange($id, 3, $madeBy, $oldStatus, self::STATUS_REPLACED);
    }

    /**
     * @param iterable $assignedPayments
     * @param int $madeBy
     * @throws Exception
     */
    public function createReplacementPayment(iterable $assignedPayments, int $madeBy)
    {
        $assignedPaymentsDetails = [];
        foreach ($assignedPayments as $paymentId => $amount) {
            $assignedPaymentsDetails[] = $this->db->select('*')->from('%n', self::T_PAYMENTS)->where('id = %i', $paymentId)->fetch();
        }

        $match = false;
        if (count($assignedPaymentsDetails) == 1) {
            $match = true;
        } else {
            foreach ($assignedPaymentsDetails as $key => $payment) {
                if ($key == 0) {
                    continue;
                }
                if ($payment['account_number'] == $assignedPaymentsDetails[$key - 1]['account_number'] &&
                    $payment['bank_code'] == $assignedPaymentsDetails[$key - 1]['bank_code']) {
                    $match = true;
                } else {
                    $match = false;
                    break;
                }
            }
        }

        $newPaymentValues = [];
        // If the payments details match
        if ($match == true) {
            // Create new payment with these details
            $newPaymentValues['account_number'] = $assignedPaymentsDetails[0]['account_number'];
            $newPaymentValues['bank_code'] = $assignedPaymentsDetails[0]['bank_code'];
            $newPaymentValues['account_name'] = $assignedPaymentsDetails[0]['account_name'];
        } else {
            // If not, create new payment blank
            $newPaymentValues['account_number'] = 'Více původních účtů - nutno dohledat';
        }

        $newPaymentValues['status'] = self::STATUS_K_REFUNDACI;
        $newPaymentValues['application_id'] = $assignedPaymentsDetails[0]['application_id'];
        $newPaymentValues['currency'] = 'CZK';

        // Amount k vrácení = paymentsSum - záloha
        $amount = $this->getPaymentsSumForApplication($newPaymentValues['application_id']) - $this->paymentsLimitsModel->getById(PaymentsLimitsModel::ZALOHA_ID)['amount'];
        $newPaymentValues['amount'] = $amount;

        $this->db->insert(self::T_PAYMENTS, $newPaymentValues)->execute();
        $newPaymentId = $this->db->getInsertId();
        $this->db->insert(self::T_PAYMENTS_LOG, ['payment_id' => $newPaymentId, 'made_by' => $madeBy, 'action_type' => 2])->execute();
    }


    /**
     * @param int $paymentId
     * @param int $actionType
     * @param int $madeBy
     * @param int|null $oldValue
     * @param int|null $newValue
     * @throws Exception
     */
    public function logPaymentStatusChange(int $paymentId, int $actionType, int $madeBy, int $oldValue = null, int $newValue = null)
    {
        $values['payment_id'] = $paymentId;
        $values['made_by'] = $madeBy;
        $values['action_type'] = $actionType; // Table payment_action_type
        if (!empty($oldValue)) {
            $values['old_value'] = $this->db->select('name')->from('%n', self::T_PAYMENT_STATUS)->where('id = %i', $oldValue)->fetchSingle();
        }
        if (!empty($newValue)) {
            $values['new_value'] = $this->db->select('name')->from('%n', self::T_PAYMENT_STATUS)->where('id = %i', $newValue)->fetchSingle();
        }

        $this->db->insert(self::T_PAYMENTS_LOG, $values)->execute();
    }
}