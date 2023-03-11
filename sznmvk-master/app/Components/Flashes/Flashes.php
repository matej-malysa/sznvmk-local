<?php

declare(strict_types=1);

namespace App\Components\Flashes;

use App\Components\AppComponent;

class Flashes extends AppComponent
{
    const FLASH_SUCCESS = 'success';
    const FLASH_INFO = 'info';
    const FLASH_WARNING = 'warning';
    const FLASH_DANGER = 'danger';

    const SNIPPET_NAME = 'flashes';

    public function __construct()
    {
        parent::__construct();
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/control.latte');
        $this->template->flashes = $this->presenter->template->flashes;
        $this->template->snippet = self::SNIPPET_NAME;
        $this->template->render();
    }
}
