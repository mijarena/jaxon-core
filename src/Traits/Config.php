<?php

/**
 * Config.php - Config Trait
 *
 * The Jaxon class uses a modular plug-in system to facilitate the processing
 * of special Ajax requests made by a PHP page.
 * It generates Javascript that the page must include in order to make requests.
 * It handles the output of response commands (see <Jaxon\Response\Response>).
 * Many flags and settings can be adjusted to effect the behavior of the Jaxon class
 * as well as the client-side javascript.
 *
 * @package jaxon-core
 * @author Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @copyright 2017 Thierry Feuzeu <thierry.feuzeu@gmail.com>
 * @license https://opensource.org/licenses/BSD-3-Clause BSD 3-Clause License
 * @link https://github.com/jaxon-php/jaxon-core
 */

namespace Jaxon\Traits;

use Jaxon\Config\Php;
use Jaxon\Config\Yaml;
use Jaxon\Config\Json;

trait Config
{
    /**
     * Read and set Jaxon options from a PHP config file
     *
     * @param string        $sConfigFile        The full path to the config file
     * @param string        $sLibKey            The key of the library options in the file
     * @param string|null   $sAppKey            The key of the application options in the file
     *
     * @return array
     */
    public function readPhpConfigFile($sConfigFile, $sLibKey = '', $sAppKey = null)
    {
        return Php::read($sConfigFile, $sLibKey, $sAppKey);
    }

    /**
     * Read and set Jaxon options from a YAML config file
     *
     * @param string        $sConfigFile        The full path to the config file
     * @param string        $sLibKey            The key of the library options in the file
     * @param string|null   $sAppKey            The key of the application options in the file
     *
     * @return array
     */
    public function readYamlConfigFile($sConfigFile, $sLibKey = '', $sAppKey = null)
    {
        return Yaml::read($sConfigFile, $sLibKey, $sAppKey);
    }

    /**
     * Read and set Jaxon options from a JSON config file
     *
     * @param string        $sConfigFile        The full path to the config file
     * @param string        $sLibKey            The key of the library options in the file
     * @param string|null   $sAppKey            The key of the application options in the file
     *
     * @return array
     */
    public function readJsonConfigFile($sConfigFile, $sLibKey = '', $sAppKey = null)
    {
        return Json::read($sConfigFile, $sLibKey, $sAppKey);
    }

    /**
     * Read and set Jaxon options from a config file
     *
     * @param string        $sConfigFile        The full path to the config file
     * @param string        $sLibKey            The key of the library options in the file
     * @param string|null   $sAppKey            The key of the application options in the file
     *
     * @return array
     */
    public function readConfigFile($sConfigFile, $sLibKey = '', $sAppKey = null)
    {
        $sExt = pathinfo($sConfigFile, PATHINFO_EXTENSION);
        switch($sExt)
        {
        case 'php':
            return $this->readPhpConfigFile($sConfigFile, $sLibKey, $sAppKey);
        case 'yaml':
        case 'yml':
            return $this->readYamlConfigFile($sConfigFile, $sLibKey, $sAppKey);
        case 'json':
            return $this->readJsonConfigFile($sConfigFile, $sLibKey, $sAppKey);
        default:
            $sErrorMsg = jaxon_trans('config.errors.file.extension', array('path' => $sConfigFile));
            throw new \Jaxon\Exception\Config\File($sErrorMsg);
        }
    }
}
