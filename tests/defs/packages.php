<?php

use Jaxon\CallableClass;
use Jaxon\Plugin\Package;
use Jaxon\Response\Response;

class SamplePackageClass extends CallableClass
{
    public function home(): Response
    {
        $this->response->debug('This class is registered by a package!!');
        return $this->response;
    }
}

class SamplePackage extends Package
{
    /**
     * @inheritDoc
     */
    public static function config(): array
    {
        return [
            'classes' => [
                SamplePackageClass::class,
            ],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getHtml(): string
    {
        return '';
    }
}

class BadConfigPackage extends Package
{
    /**
     * @inheritDoc
     */
    public static function config()
    {
        return true; // This is wrong. The return value must be a string or an array.
    }

    /**
     * @inheritDoc
     */
    public function getHtml(): string
    {
        return '';
    }
}