<?php

namespace App\Components;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Nette\Application\AbortException;
use Nette\Application\UI\Control;
use Nextras\Application\UI\SecuredLinksControlTrait;

abstract class AppComponent extends Control
{
    use SecuredLinksControlTrait;

    const SYSTEM_USER = 1;

    const ROLE_ADMIN = 1;
    const ROLE_LEADER = 2;

    /** @var Logger */
    protected $logger;

    /** @var string */
    protected $redirectDest = 'this';

    /** @var array */
    protected $redirectArgs = [];

    /**
     * AppComponent constructor.
     */
    public function __construct()
    {
        $this->logger = new Logger('dev');
        $this->logger->pushHandler(new StreamHandler('/log/dev.log', Logger::WARNING));
    }

    /**
     * Redirect all redirect() calls to presenter.
     *
     * @param mixed $code
     * @param int|null|array   $destination
     * @param array $args
     *
     * @throws AbortException
     */
    public function redirect($code, $destination = null, array $args = []): void
    {
        if ($args) {
            $this->presenter->redirect($code, $destination, $args);
        } else {
            $this->presenter->redirect($code, $destination);
        }
    }

    /**
     * Set page component should redirect to, if it uses redirect.
     *
     * @param string $redirectDest
     * @param array  $redirectArgs
     *
     * @return AppComponent
     */
    public function setRedirect(string $redirectDest, array $redirectArgs = []): self
    {
        $this->redirectDest = $redirectDest;
        $this->redirectArgs = $redirectArgs;

        return $this;
    }

    /**
     * Redirect all flashMessage() calls to presenter.
     *
     * @param mixed  $message
     * @param string $type
     *
     * @return \stdClass
     */
    public function flashMessage($message, string $type = 'info'): \stdClass
    {
        return $this->presenter->flashMessage($message, $type);
    }

    /**
     * Redirect user to destination or redraw control if Ajax.
     *
     * @param null|string $redirectDest
     * @param array       $redirectArgs
     *
     * @throws AbortException
     */
    public function finishHandler(string $redirectDest = null, array $redirectArgs = [])
    {
        if (null === $redirectDest) {
            $redirectDest = $this->redirectDest;
            $redirectArgs = $this->redirectArgs;
        }

        if ($this->presenter->isAjax()) {
            if ($form = $this->getComponent('form', false)) {
                $form->setValues([], true);
            }
            $this->redrawControl();
        } else {
            $this->presenter->redirect($redirectDest, $redirectArgs);
        }
    }

    public function isLeader(): bool
    {
        $roles = $this->presenter->getUser()->getRoles();
        return (in_array(self::ROLE_ADMIN, $roles) || in_array(self::ROLE_LEADER, $roles));
    }

    public function isAdmin(): bool
    {
        $roles = $this->presenter->getUser()->getRoles();
        return (in_array(self::ROLE_ADMIN, $roles));
    }
}