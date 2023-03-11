<?php

declare(strict_types=1);

namespace App\Components\JsLoader;

use JShrink\Minifier;
use Nette;
use WebLoader\Compiler;
use WebLoader\IFileCollection;
use WebLoader\Nette\JavaScriptLoader;

class JsLoader extends Nette\Application\UI\Control
{
    const webtemp = '/webtemp';
    /** @var array */
    private $scripts = [];

    /** @var array */
    private $externalScripts = [];

    /** @var string */
    private $public;

    /** @var string */
    private $path = '';

    public function __construct(string $public)
    {
        $this->public = $public;

        if (!is_dir($this->public . self::webtemp)) {
            mkdir($this->public . self::webtemp);
        }
    }

    public function render()
    {
        $this->template->setFile(__DIR__ . '/templates/js.latte');
        $this->template->render();
    }

    // SET ----------------------------------------------------------------------------------------------------------

    public function setScripts(array $scripts)
    {
        $this->scripts = $scripts;
    }

    public function setPath(string $folder)
    {
        $this->path = $folder;
    }

    public function setExternalScripts(array $externalScripts)
    {
        $this->externalScripts = $externalScripts;
    }

    // CREATE -------------------------------------------------------------------------------------------------------

    /**
     * @param IFileCollection $files
     *
     * @throws \WebLoader\InvalidArgumentException
     *
     * @return Compiler
     */
    protected function createCompilerJs(IFileCollection $files): Compiler
    {
        $compiler = \WebLoader\Compiler::createJsCompiler($files, $this->public . self::webtemp);
        $compiler->addFilter(function ($code) {
            return Minifier::minify($code);
        });

        return $compiler;
    }

    /**
     * @throws \WebLoader\InvalidArgumentException
     *
     * @return JavaScriptLoader
     */
    protected function createComponentJs()
    {
        $files = new \WebLoader\FileCollection($this->public);
        $files->addFiles($this->scripts);

        if ($this->path) {
            $files->addWatchFiles(Nette\Utils\Finder::findFiles('*.JsLoader')->from($this->path));
        }

        $files->addRemoteFiles($this->externalScripts);
        $compiler = $this->createCompilerJs($files);

        return new JavaScriptLoader($compiler, $this->template->basePath . self::webtemp, false);
    }
}
