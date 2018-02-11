<?php

/*
 * This file is part of the "Composer Project Builder Plugin" package.
 *
 * https://github.com/Letudiant/composer-project-builder-plugin
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VerbruggenAlex\Composer\Installer;

use Composer\Composer;
use Composer\Config;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\DependencyResolver\Operation\UninstallOperation;
use Composer\Downloader\FilesystemException;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use VerbruggenAlex\Composer\Data\Package\PackageDataManagerInterface;
use VerbruggenAlex\Composer\Installer\Config\ProjectBuilderInstallerConfig;
use VerbruggenAlex\Composer\Util\SymlinkFilesystem;

/**
 * 
 */
class ProjectBuilderInstaller extends LibraryInstaller
{
    const PACKAGE_TYPE = 'project-builder';
    const PACKAGE_PRETTY_NAME = 'verbruggenalex/composer-project-builder-plugin';

    /**
     * @var ProjectBuilderInstallerConfig
     */
    protected $config;

    /**
     * @var PackageDataManagerInterface
     */
    protected $packageDataManager;

    /**
     * @var SymlinkFilesystem
     */
    protected $filesystem;


    /**
     * @param IOInterface                  $io
     * @param Composer                     $composer
     * @param SymlinkFilesystem            $filesystem
     * @param PackageDataManagerInterface  $dataManager
     * @param ProjectBuilderInstallerConfig $config
     */
    public function __construct(
        IOInterface $io,
        Composer $composer,
        SymlinkFilesystem $filesystem,
        PackageDataManagerInterface $dataManager,
        ProjectBuilderInstallerConfig $config
    )
    {
        $this->filesystem = $filesystem;

        parent::__construct($io, $composer, 'library', $this->filesystem);

        $this->config = $config;
        $this->composer = $composer;
        $this->vendorDir = $this->config->getVendorDir();
        $this->packageDataManager = $dataManager;
        $this->packageDataManager->setVendorDir($this->vendorDir);
    }

    /**
     * @inheritdoc
     */
    public function getInstallPath(PackageInterface $package)
    {
        $this->initializeVendorDir();
        $extra = $package->getExtra();
        $hasBinary = !empty($package->getBinaries());
        $hasPatches = isset($extra['patches_applied']);
        $notComposer = !in_array($package->getType(), array('composer-plugin', 'composer-installer'));

        if (($hasBinary || $hasPatches) && $notComposer) {
            $basePath = $this->config->getVendorDir() . DIRECTORY_SEPARATOR
              . $package->getPrettyName();
        }
        else {
            $basePath =
              $this->config->getOriginalVendorDir() . DIRECTORY_SEPARATOR
              . $package->getPrettyName() . '-'
              . $package->getPrettyVersion();
        }

        $targetDir = $package->getTargetDir();

        return $basePath . ($targetDir ? '/' . $targetDir : '');
    }

    /**
     * @param PackageInterface $package
     *
     * @return string
     */
    protected function getPackageVendorSymlink(PackageInterface $package)
    {
        return $this->config->getSymlinkDir() . DIRECTORY_SEPARATOR . $package->getPrettyName();
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface             $package
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if (!is_readable($this->getInstallPath($package))) {
            parent::install($repo, $package);
        } elseif (!$repo->hasPackage($package)) {
            $this->binaryInstaller->installBinaries($package, $this->getInstallPath($package));
            $repo->addPackage(clone $package);
        }

        $this->createPackageVendorSymlink($package);
        $this->packageDataManager->addPackageUsage($package);
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface             $package
     *
     * @return bool
     */
    public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        // Just check if the sources folder and the link exist
        return
            $repo->hasPackage($package)
            && is_readable($this->getInstallPath($package))
            && is_link($this->getPackageVendorSymlink($package))
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        $this->packageDataManager->setPackageInstallationSource($initial);
        $this->packageDataManager->setPackageInstallationSource($target);

        // The package need only a code update because the version (branch), only the commit changed
        if ($this->getInstallPath($initial) === $this->getInstallPath($target)) {
            $this->createPackageVendorSymlink($target);

            parent::update($repo, $initial, $target);
        } else {
            // If the initial package sources folder exists, uninstall it
            $this->composer->getInstallationManager()->uninstall($repo, new UninstallOperation($initial));

            // Install the target package
            $this->composer->getInstallationManager()->install($repo, new InstallOperation($target));
        }
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface             $package
     *
     * @throws FilesystemException
     */
    public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if ($this->isSourceDirUnused($package) && $this->io->askConfirmation(
                "The package version <info>" . $package->getPrettyName() . "</info> "
                . "(<fg=yellow>" . $package->getPrettyVersion() . "</fg=yellow>) seems to be unused."
                . PHP_EOL
                . 'Do you want to <fg=red>delete the source folder</fg=red> ? [y/n] (default: no) : ',
                false
            )) {
            $this->packageDataManager->setPackageInstallationSource($package);

            parent::uninstall($repo, $package);
        } else {
            $this->binaryInstaller->removeBinaries($package);
            $repo->removePackage($package);
        }

        $this->packageDataManager->removePackageUsage($package);
        $this->removePackageVendorSymlink($package);
    }

    /**
     * Detect if other project use the dependency by using the "packages.json" file
     *
     * @param PackageInterface $package
     *
     * @return bool
     */
    protected function isSourceDirUnused(PackageInterface $package)
    {
        $usageData = $this->packageDataManager->getPackageUsage($package);

        return sizeof($usageData) <= 1;
    }

    /**
     * @param PackageInterface $package
     */
    protected function createPackageVendorSymlink(PackageInterface $package)
    {
        if (empty($package->getBinaries()) && $this->config->isSymlinkEnabled() && $this->filesystem->ensureSymlinkExists(
                $this->getSymlinkSourcePath($package),
                $this->getPackageVendorSymlink($package)
            )
        ) {
            $this->io->write(
                '  - Creating symlink for <info>' . $package->getPrettyName()
                . '</info> (<fg=yellow>' . $package->getPrettyVersion() . '</fg=yellow>)'
            );
        }
    }

    /**
     * @param PackageInterface $package
     *
     * @return string
     */
    protected function getSymlinkSourcePath(PackageInterface $package)
    {
        if (null != $this->config->getSymlinkBasePath()) {
            $targetDir = $package->getTargetDir();
            $sourcePath =
                $this->config->getSymlinkBasePath()
                . '/' . $package->getPrettyName()
                . '-' . $package->getPrettyVersion()
                . ($targetDir ? '/' . $targetDir : '')
            ;
        } else {
            $sourcePath = $this->getInstallPath($package);
        }

        return $sourcePath;
    }

    /**
     * @param PackageInterface $package
     *
     * @throws FilesystemException
     */
    protected function removePackageVendorSymlink(PackageInterface $package)
    {
        if (
            $this->config->isSymlinkEnabled()
            && $this->filesystem->removeSymlink($this->getPackageVendorSymlink($package))
        ) {
            $this->io->write(array(
                '  - Deleting symlink for <info>' . $package->getPrettyName() . '</info> '
                . '(<fg=yellow>' . $package->getPrettyVersion() . '</fg=yellow>)',
                ''
            ));

            $symlinkParentDirectory = dirname($this->getPackageVendorSymlink($package));
            $this->filesystem->removeEmptyDirectory($symlinkParentDirectory);
        }
    }

    /**
     * @param string $packageType
     *
     * @return bool
     */
    public function supports($packageType)
    {
        return true;
    }
}
