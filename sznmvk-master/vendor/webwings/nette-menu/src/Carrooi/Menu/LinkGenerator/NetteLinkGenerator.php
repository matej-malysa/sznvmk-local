<?php

declare(strict_types=1);

namespace Carrooi\Menu\LinkGenerator;

use Carrooi\Menu\IMenuItem;
use Nette\Application\Application;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\InvalidLinkException;

/**
 * @author David Kudera <kudera.d@gmail.com>
 */
final class NetteLinkGenerator implements ILinkGenerator
{
    /** @var LinkGenerator */
    private $linkGenerator;

    /** @var \Nette\Application\Application */
    private $application;

    /**
     * NetteLinkGenerator constructor.
     * @param LinkGenerator $linkGenerator
     */
    public function __construct(LinkGenerator $linkGenerator, Application $application)
    {
        $this->linkGenerator = $linkGenerator;
        $this->application = $application;
    }

    /**
     * @param IMenuItem $item
     * @return string
     * @throws InvalidLinkException
     */
    public function link(IMenuItem $item): string
    {
        if (($action = $item->getAction()) !== null) {
            try {
                return $this->linkGenerator->link($action, $item->getActionParameters());
            } catch (InvalidLinkException $ex) {
                $presenter = $this->application->getPresenter();
                if ($presenter->isLinkCurrent($action)) {
                    return $presenter->link('this');
                } else {
                    return '#';
                }
            }
        } elseif (($link = $item->getLink()) !== null) {
            return $link;
        }

        return '#';
    }
}
