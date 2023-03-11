<?php
declare(strict_types=1);

namespace App\Components\CssLoader;

interface ICssLoaderFactory
{
    /**
     * @return CssLoader
     */
    public function create(): CssLoader;
}