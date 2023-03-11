<?php

declare(strict_types=1);

namespace App\Components\CssLoader;

use Nette;
use WebLoader\Compiler;
use WebLoader\FileCollection;
use WebLoader\Filter\ScssFilter;
use WebLoader\IFileCollection;
use WebLoader\InvalidArgumentException;

class CssLoader extends Nette\Application\UI\Control
{
    const webtemp = '/webtemp';
    /** @var string */
    private $media = 'screen'; //'screen,projection,tv,print'

    /** @var array */
    private $styles = [];

    /** @var array */
    private $externalStyles = [];

    /** @var string */
    private $public = '';

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
        $this->template->setFile(__DIR__ . '/templates/css.latte');
        $this->template->render();
    }

    // SET ----------------------------------------------------------------------------------------------------------

    /**
     * @param array $media
     *
     * @return CssLoader
     */
    public function setMedia(array $media): self
    {
        $this->media = implode(',', array_unique($media));

        return $this;
    }

    /**
     * @param array $styles
     *
     * @return CssLoader
     */
    public function setStyles(array $styles): self
    {
        $this->styles = $styles;

        return $this;
    }

    /**
     * @param string $folder
     *
     * @return CssLoader
     */
    public function setPath(string $folder): self
    {
        $this->path = $folder;

        return $this;
    }

    /**
     * @param array $externalStyles
     *
     * @return CssLoader
     */
    public function setExternalStyles(array $externalStyles): self
    {
        $this->externalStyles = $externalStyles;

        return $this;
    }

    // CREATE -------------------------------------------------------------------------------------------------------

    /**
     * @param IFileCollection $files
     *
     * @throws InvalidArgumentException
     *
     * @return Compiler
     */
    protected function createCompilerCss(IFileCollection $files): Compiler
    {
        $compiler = \WebLoader\Compiler::createCssCompiler($files, $this->public . self::webtemp);
        $compiler->addFileFilter(new ScssFilter());
        $compiler->addFilter(function ($code) {
            return \CssMin::minify($code, ['remove-last-semicolon']);
        });

        return $compiler;
    }

    /**
     * @throws InvalidArgumentException
     *
     * @return \WebLoader\Nette\CssLoader
     */
    protected function createComponentCss()
    {
        $files = new FileCollection($this->public);
        $files->addRemoteFiles($this->externalStyles);
        $files->addFiles($this->styles);

        if ($this->path) {
            $files->addWatchFiles(Nette\Utils\Finder::findFiles('*.scss')->from($this->path));
        }

        $compiler = $this->createCompilerCss($files);

        $control = new \WebLoader\Nette\CssLoader($compiler, $this->template->basePath . self::webtemp, false);
        $control->setMedia($this->media);

        return $control;
    }
}

