<?php
declare(strict_types=1);

namespace App\Classes\Nette\Security;

use App\Model\ApplicationsModel;
use Nette\Security\AuthenticationException;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;

class Authenticator implements \Nette\Security\Authenticator
{
    /** @var Passwords */
    protected Passwords $passwords;

    /** @var ApplicationsModel */
    protected ApplicationsModel $applicationsModel;

    public function __construct(Passwords $passwords, ApplicationsModel $applicationsModel)
    {
        $this->passwords = $passwords;
        $this->applicationsModel = $applicationsModel;
    }

    function authenticate(string $user, string $password): IIdentity
    {
        $applicant = $this->applicationsModel->getByEmail($user);


        if (!$applicant) {
            throw new AuthenticationException('Neplatné přihlašovací údaje');
        }

        if (!$this->passwords->verify($password, $applicant['password'])) {
            throw new AuthenticationException('Neplatné přihlašovací údaje');
        }

        return new SimpleIdentity($applicant->id, null, $applicant);
    }
}