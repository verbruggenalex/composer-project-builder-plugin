<?php

/*
 * This file is part of the "Composer Project Builder Plugin" package.
 *
 * https://github.com/Letudiant/composer-project-builder-plugin
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VerbruggenAlex\Composer\Installer\Config;

use VerbruggenAlex\Composer\Installer\ProjectBuilderInstaller;

/**
 * 
 *
 * @see https://github.com/Letudiant/composer-project-builder-plugin/blob/master/docs/all-available-configurations.md
 */
class ProjectBuilderInstallerConfig
{
    const ENV_PARAMETER_VENDOR_DIR        = 'COMPOSER_SPP_VENDOR_DIR';
    const ENV_PARAMETER_SYMLINK_BASE_PATH = 'COMPOSER_SPP_SYMLINK_BASE_PATH';

    /**
     * @var string
     */
    protected $originalDirectories;

    /**
     * @var string
     */
    protected $buildDirectories;

    /**
     * @var string
     */
    protected $buildRoot;

    /**
     * @var string
     */
    protected $originalVendorDir;

    /**
     * @var string
     */
    protected $symlinkDir;

    /**
     * @var string
     */
    protected $vendorDir;

    /**
     * @var string
     */
    protected $binDir;

    /**
     * @var string|null
     */
    protected $symlinkBasePath;

    /**
     * @var bool
     */
    protected $isSymlinkEnabled = true;

    /**
     * @var array
     */
    protected $packageList = array();


    /**
     * @param Config        $composerConfig
     * @param array|null    $extraConfigs
     */
    public function __construct($composerConfig, $extraConfigs)
    {
        $this->originalVendorDir = $composerConfig->get('vendor-dir');

        $baseDir = substr($composerConfig->get('vendor-dir', 1), 0, -strlen($this->originalVendorDir));

        $this->setOriginalDirectories($composerConfig, $extraConfigs);
        $this->setBuildPrefix($extraConfigs);
        $this->setBuildDirectories();

        $this->setVendorDir($baseDir, $extraConfigs);
        $this->setBinDir($baseDir, $extraConfigs);
        $this->setSymlinkDirectory($baseDir, $extraConfigs);
        $this->setSymlinkBasePath($extraConfigs);
        $this->setIsSymlinkEnabled($extraConfigs);
        $this->setPackageList($extraConfigs);
    }

    /**
     * @param string $baseDir
     * @param array  $extraConfigs
     */
    protected function setSymlinkDirectory($baseDir, array $extraConfigs)
    {
        $this->symlinkDir = $this->getBuildDirectory('absolute', 'vendorDir');

//        if (isset($extraConfigs[ProjectBuilderInstaller::PACKAGE_TYPE]['symlink-dir'])) {
//            $this->symlinkDir = $extraConfigs[ProjectBuilderInstaller::PACKAGE_TYPE]['symlink-dir'];
//
//            if ('/' != $this->symlinkDir[0]) {
//                $this->symlinkDir = $baseDir . $this->symlinkDir;
//            }
//        }
    }

    /**
     * @param string $baseDir
     * @param array  $extraConfigs
     *
     * @throws \InvalidArgumentException
     */
    protected function setVendorDir($baseDir, array $extraConfigs)
    {
        $this->vendorDir = $this->getBuildDirectory('absolute', 'vendorDir');

        if (false !== getenv(static::ENV_PARAMETER_VENDOR_DIR)) {
            $this->vendorDir = getenv(static::ENV_PARAMETER_VENDOR_DIR);
        }

        if ('/' != $this->vendorDir[0]) {
            $this->vendorDir = $baseDir . $this->vendorDir;
        }
    }

    /**
     * @param string $baseDir
     * @param array  $extraConfigs
     *
     * @throws \InvalidArgumentException
     */
    protected function setBinDir($baseDir, array $extraConfigs)
    {
        $this->binDir = $this->getBuildDirectory('absolute', 'binDir');
    }

    /**
     * Allow to override symlinks base path.
     * This is useful for a Virtual Machine environment, where directories can be different
     * on the host machine and the guest machine.
     *
     * @param array $extraConfigs
     */
    protected function setSymlinkBasePath(array $extraConfigs)
    {
        if (isset($extraConfigs[ProjectBuilderInstaller::PACKAGE_TYPE]['symlink-base-path'])) {
            $this->symlinkBasePath = $extraConfigs[ProjectBuilderInstaller::PACKAGE_TYPE]['symlink-base-path'];

            if (false !== getenv(static::ENV_PARAMETER_SYMLINK_BASE_PATH)) {
                $this->symlinkBasePath = getenv(static::ENV_PARAMETER_SYMLINK_BASE_PATH);
            }

            // Remove the ending slash if exists
            if ('/' === $this->symlinkBasePath[strlen($this->symlinkBasePath) - 1]) {
                $this->symlinkBasePath = substr($this->symlinkBasePath, 0, -1);
            }
        }
//        elseif (0 < strpos($extraConfigs[ProjectBuilderInstaller::PACKAGE_TYPE]['vendor-dir'], '/')) {
//            $this->symlinkBasePath = $extraConfigs[ProjectBuilderInstaller::PACKAGE_TYPE]['vendor-dir'];
//        }

        // Up to the project root directory
        if (0 < strpos($this->symlinkBasePath, '/')) {
            $this->symlinkBasePath = '../../' . $this->symlinkBasePath;
        }
    }

    /**
     * The symlink directory creation process can be disabled.
     * This may mean that you work directly with the sources directory so the symlink directory is useless.
     *
     * @param array $extraConfigs
     */
    protected function setIsSymlinkEnabled(array $extraConfigs)
    {
        if (isset($extraConfigs[ProjectBuilderInstaller::PACKAGE_TYPE]['symlink-enabled'])) {
            if (!is_bool($extraConfigs[ProjectBuilderInstaller::PACKAGE_TYPE]['symlink-enabled'])) {
                throw new \UnexpectedValueException('The configuration "symlink-enabled" should be a boolean');
            }

            $this->isSymlinkEnabled = $extraConfigs[ProjectBuilderInstaller::PACKAGE_TYPE]['symlink-enabled'];
        }
    }

    /**
     * @return array
     */
    public function getPackageList()
    {
        return $this->packageList;
    }

    /**
     * @param array $extraConfigs
     */
    public function setPackageList(array $extraConfigs)
    {
        if (isset($extraConfigs[ProjectBuilderInstaller::PACKAGE_TYPE]['package-list'])) {
            $packageList = $extraConfigs[ProjectBuilderInstaller::PACKAGE_TYPE]['package-list'];

            if (!is_array($packageList)) {
                throw new \UnexpectedValueException('The configuration "package-list" should be a JSON object');
            }

            $this->packageList = $packageList;
        }
    }

    /**
     * @return bool
     */
    public function isSymlinkEnabled()
    {
        return $this->isSymlinkEnabled;
    }

    /**
     * @return string
     */
    public function getVendorDir()
    {
        return $this->vendorDir;
    }

    /**
     * @return string
     */
    public function getSymlinkDir()
    {
        return $this->symlinkDir;
    }

    /**
     * @param bool $endingSlash
     *
     * @return string
     */
    public function getOriginalVendorDir($endingSlash = false)
    {
        if ($endingSlash && null != $this->originalVendorDir) {
            return $this->originalVendorDir . '/';
        }

        return $this->originalVendorDir;
    }

    /**
     * @return string|null
     */
    public function getSymlinkBasePath()
    {
        return $this->symlinkBasePath;
    }

    /**
     * @return string|bool
     */
    public function getBuildRoot()
    {
        return isset($this->buildRoot) ? $this->buildRoot : false;
    }


    /**
     * @param Composer $composer
     */
    protected function setOriginalDirectories($composerConfig, $composerExtra) {
        // Get original directories.
        $vendorDirRelative = $composerConfig->get('vendor-dir', 1);
        $vendorDirAbsolute = $composerConfig->get('vendor-dir');
        $binDirRelative = $composerConfig->get('bin-dir', 1);
        $binDirAbsolute = $composerConfig->get('bin-dir');
        $baseDirAbsolute = substr($vendorDirAbsolute, 0, -strlen($vendorDirRelative));
        // Set original directories.
        $this->originalDirectories =  array(
          'absolute' => array(
            'baseDir' =>$baseDirAbsolute,
            'vendorDir' => $vendorDirAbsolute,
            'binDir' => $binDirAbsolute,
          ),
          'relative' => array(
            'vendorDir' => $vendorDirRelative,
            'binDir' => $binDirRelative,
          ),
        );
    }

    /**
     * @param string $pathType
     * @param string $dirType
     */
    protected function setBuildDirectories() {
        // Get required paths.
        $baseDirAbsolute = $this->originalDirectories['absolute']['baseDir'];
        $buildPrefix = $this->buildPrefix . DIRECTORY_SEPARATOR;
        $buildPrefixAbsolute = $baseDirAbsolute . $buildPrefix;
        $vendorDirRelative = $this->originalDirectories{'relative'}['vendorDir'];
        $binDirRelative = $this->originalDirectories{'relative'}['binDir'];
        // Set original directories.
        $this->buildDirectories = array(
          'absolute' => array(
            'baseDir' => $buildPrefixAbsolute,
            'vendorDir' => $buildPrefixAbsolute . $vendorDirRelative,
            'binDir' => $buildPrefixAbsolute . $binDirRelative,
          ),
          'relative' => array(
            'baseDir' => $buildPrefix,
            'vendorDir' => $buildPrefix . $vendorDirRelative,
            'binDir' => $buildPrefix  . $binDirRelative,
          ),
        );
    }

    /**
     * @param array $extraConfigs
     *
     * @throws \InvalidArgumentException
     */
    protected function setBuildPrefix(array $extraConfigs)
    {
        $buildPrefix = '';
        $branch = trim(str_replace('* ', '', exec("git branch | grep '\*'")));
        if (isset($extraConfigs['project-builder']['build-dir'])) {
            $buildPrefix .= (isset($GLOBALS['argv']) && in_array('--no-dev', $GLOBALS['argv']))
              ? $extraConfigs['project-builder']['build-dir']['--no-dev']
              : $extraConfigs['project-builder']['build-dir']['--dev'];
        }

        if (isset($extraConfigs['project-builder']['root-dir'])) {
            $this->buildRoot = (isset($GLOBALS['argv']) && in_array('--no-dev', $GLOBALS['argv']))
              ? $extraConfigs['project-builder']['root-dir']['--no-dev']
              : $extraConfigs['project-builder']['root-dir']['--dev'];
        }

        // Replace branch variable.
        // @todo: Also allow tag replacement.
        $availableVars = $this->inflectPackageVars(compact('branch', 'tag'));
        $this->buildPrefix = rtrim($this->templatePath($buildPrefix, $availableVars), '/');
    }

    /**
     * @param string $pathType
     * @param string $dirType
     */
    public function getOriginalDirectory($pathType = 'absolute', $dirType = 'baseDir') {
        if (isset($this->originalDirectories[$pathType][$dirType])) {
            return $this->originalDirectories[$pathType][$dirType];
        }
    }

    /**
     * @param string $pathType
     * @param string $dirType
     *
     * @todo: Incorporate framework directory to allow multiple frameworks to be built.
     */
    public function getBuildDirectory($pathType = 'absolute', $dirType = 'baseDir') {
        if (isset($this->buildDirectories[$pathType][$dirType])) {
            return $this->buildDirectories[$pathType][$dirType];
        }
    }

    /**
     * Replace vars in a path
     *
     * @param  string $path
     * @param  array  $vars
     * @return string
     */
    protected function templatePath($path, array $vars = array())
    {
        if (strpos($path, '{') !== false) {
            extract($vars);
            preg_match_all('@\{\$([A-Za-z0-9_]*)\}@i', $path, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $var) {
                    $path = str_replace('{$' . $var . '}', $$var, $path);
                }
            }
        }
        return $path;
    }

    /**
     * Search through a passed paths array for a custom install path.
     *
     * @param  array  $paths
     * @param  string $name
     * @param  string $type
     * @param  string $vendor = NULL
     * @return string
     */
    protected function mapCustomInstallPaths(array $paths, $name, $type, $vendor = NULL)
    {
        foreach ($paths as $path => $names) {
            if (in_array($name, $names) || in_array('type:' . $type, $names) || in_array('vendor:' . $vendor, $names)) {
                return $path;
            }
        }
        return false;
    }

    /**
     * For an installer to override to modify the vars per installer.
     *
     * @param  array $vars
     * @return array
     */
    public function inflectPackageVars($vars)
    {
        return $vars;
    }
}
