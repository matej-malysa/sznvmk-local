<?php

declare(strict_types=1);

namespace App\Modules;

use App\Components\CssLoader\CssLoader;
use App\Components\CssLoader\ICssLoaderFactory;
use App\Components\Flashes\Flashes;
use App\Components\Flashes\IFlashesFactory;
use App\Components\JsLoader\IJsLoaderFactory;
use App\Components\JsLoader\JsLoader;
use Carrooi\Menu\UI\IMenuComponentFactory;
use Nette;
use Nextras\Application\UI\SecuredLinksPresenterTrait;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter
{
    use SecuredLinksPresenterTrait;

    /** @var ICssLoaderFactory @inject */
    public $cssLoaderFactory;

    /** @var IJsLoaderFactory @inject */
    public $jsLoaderFactory;

    /** @var IFlashesFactory @inject */
    public $flashesFactory;

    /** @var IMenuComponentFactory @inject */
    public $menuFactory;

    /**
     * @inheritDoc
     */
    public function redrawControl(string $snippet = null, bool $redraw = true): void
    {
        parent::redrawControl($snippet, $redraw);

        if ($snippet !== 'flashes') {
            $this->redrawControl('flashes');
        }
    }

    /**
     * @param mixed  $message
     * @param string $type
     *
     * @return \stdClass
     */
    public function flashMessage($message, string $type = 'info'): \stdClass
    {
        $this['flashes']->redrawControl();

        return parent::flashMessage($message, $type);
    }

    /**
     * @return CssLoader
     */
    protected function createComponentCss()
    {
        $control = $this->cssLoaderFactory->create();
        $control->setPath(ROOT_DIR . '/assets/sass');

        $control->setStyles([
            ROOT_DIR . '/assets/css/normalize.css',
            ROOT_DIR . '/assets/css/webflow.css',
            ROOT_DIR . '/assets/css/seznamovak.webflow.css',
            ROOT_DIR . '/assets/vendor/intl-tel-input/css/intlTelInput.css',
            ROOT_DIR . '/assets/css/jquery.fancybox.min.css',
            ROOT_DIR . '/assets/css/admin/extras.css',
            ROOT_DIR . '/assets/css/flatpickr.min.css',
        ]);

        return $control;
    }

    /**
     * @return CssLoader
     */
    public function createComponentCssAdmin()
    {
        $control = $this->cssLoaderFactory->create();
        $control->setPath(ROOT_DIR . '/assets/sass');

        $control->setStyles([
            ROOT_DIR . '/assets/css/admin/bootstrap.css',
            ROOT_DIR . '/assets/css/admin/happy.css',
            ROOT_DIR . '/assets/css/flatpickr.min.css',
            ROOT_DIR . '/assets/css/admin/datagrid.css',
            ROOT_DIR . '/assets/css/seznamovak.webflow.css',
            ROOT_DIR . '/assets/vendor/intl-tel-input-admin/css/intlTelInput.css',
            ROOT_DIR . '/assets/css/admin/grids.css',
            ROOT_DIR . '/assets/css/admin/extras.css',
        ]);

        return $control;
    }

    /**
     * @return JsLoader
     */
    protected function createComponentJs()
    {
        $control = $this->jsLoaderFactory->create();
        $control->setPath(ROOT_DIR . '/assets/js');

        $control->setScripts([
            ROOT_DIR . '/assets/js/jquery-3.4.1.min.js',
            ROOT_DIR . '/assets/js/webflow.js',
            ROOT_DIR . '/assets/js/webfont.js',
            ROOT_DIR . '/assets/js/bootstrap/bootstrap.js',
            ROOT_DIR . '/assets/js/bootstrap/bootstrap-confirmation.min.js',
            ROOT_DIR . '/assets/js/flatpickr/flatpickr.js',
            ROOT_DIR . '/assets/js/flatpickr/cs.js',
            ROOT_DIR . '/assets/js/flashes.js',
            ROOT_DIR . '/assets/js/live-form-validation.js',
            ROOT_DIR . '/assets/js/jquery.fancybox.min.js',
            ROOT_DIR . '/assets/js/main.js',
            ROOT_DIR . '/assets/vendor/intl-tel-input/js/intlTelInput.js',
            ]);

        return $control;
    }

    /**
     * @return JsLoader
     */
    protected function createComponentJsAdmin()
    {
        $control = $this->jsLoaderFactory->create();
        $control->setPath(ROOT_DIR . '/assets/js');

        $control->setScripts([
            ROOT_DIR . '/assets/js/nomodule-es5-fallback.js',
            ROOT_DIR . '/assets/js/jquery-3.4.1.min.js',
            ROOT_DIR . '/assets/js/flashes.js',
            ROOT_DIR . '/assets/js/bootstrap/bootstrap.js',
            ROOT_DIR . '/assets/js/flatpickr/flatpickr.js',
            ROOT_DIR . '/assets/js/flatpickr/cs.js',
            ROOT_DIR . '/assets/js/jquery-ui.min.js',
            ROOT_DIR . '/assets/js/nette.ajax.js',
            ROOT_DIR . '/assets/js/datagrid.js',
            ROOT_DIR . '/assets/js/netteForms.min.js',
            ROOT_DIR . '/assets/js/datagrid-instant-url-refresh.js',
            ROOT_DIR . '/assets/vendor/intl-tel-input-admin/js/intlTelInput.js',
        ]);

        return $control;
    }

    /**
     * @return Flashes
     */
    protected function createComponentFlashes()
    {
        return $this->flashesFactory->create();
    }

}
