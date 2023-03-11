<?php
declare(strict_types=1);

namespace App\Components\JsLoader;


interface IJsLoaderFactory
{
    /**
     * @return JsLoader
     */
    public function create();
}
