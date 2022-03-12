<?php

/**
 * Plugin.php - This class implements file upload with Ajax.
 *
 * @package jaxon-core
 * @author Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @copyright 2017 Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/jaxon-php/jaxon-core
 */

namespace Jaxon\Request\Upload;

use Jaxon\Jaxon;
use Jaxon\Plugin\Request as RequestPlugin;
use Jaxon\Response\Manager as ResponseManager;
use Jaxon\Response\UploadResponse;
use Jaxon\Utils\Translation\Translator;
use Jaxon\Exception\RequestException;

use Closure;
use Exception;

use function count;
use function trim;

class Plugin extends RequestPlugin
{
    /**
     * The response manager
     *
     * @var ResponseManager
     */
    protected $xResponseManager;

    /**
     * HTTP file upload support
     *
     * @var Upload
     */
    protected $xUpload = null;

    /**
     * @var Translator
     */
    protected $xTranslator;

    /**
     * The uploaded files copied in the user dir
     *
     * @var array
     */
    protected $aUserFiles = [];

    /**
     * The name of file containing upload data
     *
     * @var string
     */
    protected $sTempFile = '';

    /**
     * Is the current request an HTTP upload
     *
     * @var bool
     */
    protected $bIsAjaxRequest = true;

    /**
     * The constructor
     *
     * @param Upload $xUpload    HTTP file upload support
     * @param Translator $xTranslator
     * @param ResponseManager $xResponseManager
     */
    public function __construct(Upload $xUpload,
        Translator $xTranslator, ResponseManager $xResponseManager)
    {
        $this->xResponseManager = $xResponseManager;
        $this->xUpload = $xUpload;
        $this->xTranslator = $xTranslator;

        if(isset($_POST['jxnupl']))
        {
            $this->sTempFile = trim($_POST['jxnupl']);
        }
        elseif(isset($_GET['jxnupl']))
        {
            $this->sTempFile = trim($_GET['jxnupl']);
        }
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return 'UploadPlugin';
    }

    /**
     * @inheritDoc
     */
    public function checkOptions(string $sCallable, $xOptions): array
    {
        return [];
    }

    /**
     * Set the uploaded file name sanitizer
     *
     * @param Closure $cSanitizer    The closure
     *
     * @return void
     */
    public function sanitizer(Closure $cSanitizer)
    {
        $this->xUpload->setNameSanitizer($cSanitizer);
    }

    /**
     * Get the uploaded files
     *
     * @return array
     */
    public function files(): array
    {
        return $this->aUserFiles;
    }

    /**
     * Inform this plugin that other plugin can process the current request
     *
     * @return void
     */
    public function isHttpUpload()
    {
        $this->bIsAjaxRequest = false;
    }

    /**
     * @inheritDoc
     */
    public function canProcessRequest(): bool
    {
        return (count($_FILES) > 0 || ($this->sTempFile));
    }

    /**
     * Process the uploaded files in the HTTP request
     *
     * @return bool
     * @throws RequestException
     */
    public function processRequest(): bool
    {
        if(!$this->canProcessRequest())
        {
            return false;
        }

        if(($this->sTempFile))
        {
            // Ajax request following a normal HTTP upload.
            // Copy the previously uploaded files' location from the temp file.
            $this->aUserFiles = $this->xUpload->readFromTempFile($this->sTempFile);
            return true;
        }

        // Ajax or Http request with upload; copy the uploaded files.
        $this->aUserFiles = $this->xUpload->readFromHttpData();

        // For Ajax requests, there is nothing else to do here.
        if($this->bIsAjaxRequest)
        {
            return true;
        }
        // For HTTP requests, save the files' location to a temp file,
        // and return a response with a reference to this temp file.
        $xResponse = new UploadResponse();
        try
        {
            $sTempFile = $this->xUpload->saveToTempFile($this->aUserFiles);
            $xResponse->setUploadedFile($sTempFile);
        }
        catch(Exception $e)
        {
            $xResponse->setErrorMessage($e->getMessage());
        }
        $this->xResponseManager->append($xResponse);
        return true;
    }
}
