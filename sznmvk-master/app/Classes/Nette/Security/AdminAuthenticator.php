<?php
declare(strict_types=1);

namespace App\Classes\Nette\Security;

use App\Model\UserModel;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\IIdentity;
use Nette\Security\Passwords;
use Nette\Security\SimpleIdentity;

class AdminAuthenticator implements IAuthenticator
{
    /** @var Passwords */
    protected Passwords $passwords;

    /** @var UserModel */
    protected UserModel $userModel;

    public function __construct(Passwords $passwords, UserModel $userModel)
    {
        $this->passwords = $passwords;
        $this->userModel = $userModel;
    }


    /**
     * @param array $credentials
     * @return IIdentity
     * @throws AuthenticationException
     */
    public function authenticate(array $credentials): IIdentity
    {
        list($username, $password) = $credentials;

        $user = $this->userModel->getByUsername($username);

        if (!$user) {
            throw new AuthenticationException('Neplatné přihlašovací údaje');
        }

        if ($user['id'] !== 1) {
            if (!$this->passwords->verify($password, $user['password'])) {
                throw new AuthenticationException('Neplatné přihlašovací údaje');
            }

            if (!$user['enabled']) {
                throw new AuthenticationException('Uživatel je zablokován');
            }

            return new SimpleIdentity($user->id, $user->role_id, $user);
        } else {
            throw new AuthenticationException('Neplatné přihlašovací údaje');
        }
    }
}
