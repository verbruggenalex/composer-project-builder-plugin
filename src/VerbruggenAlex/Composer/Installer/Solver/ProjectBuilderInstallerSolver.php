<?php

/*
 * This file is part of the "Composer Project Builder Plugin" package.
 *
 * https://github.com/Letudiant/composer-project-builder-plugin
 *
 * For the full license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace VerbruggenAlex\Composer\Installer\Solver;

use Composer\Config;
use Composer\Downloader\FilesystemException;
use Composer\Installer\InstallerInterface;
use Composer\Installer\LibraryInstaller;
use Composer\Package\PackageInterface;
use Composer\Repository\InstalledRepositoryInterface;
use VerbruggenAlex\Composer\Installer\ProjectBuilderInstaller;
use VerbruggenAlex\Composer\Util\SymlinkFilesystem;

/**
 * 
 */
class ProjectBuilderInstallerSolver implements InstallerInterface
{
    /**
     * @var SymlinkFilesystem
     */
    protected $filesystem;

    /**
     * @var ProjectBuilderSolver
     */
    protected $solver;

    /**
     * @var ProjectBuilderInstaller
     */
    protected $symlinkInstaller;

    /**
     * @var LibraryInstaller
     */
    protected $defaultInstaller;


    /**
     * @param ProjectBuilderSolver    $solver
     * @param ProjectBuilderInstaller $symlinkInstaller
     * @param LibraryInstaller       $defaultInstaller
     */
    public function __construct(
        ProjectBuilderSolver $solver,
        ProjectBuilderInstaller $symlinkInstaller,
        LibraryInstaller $defaultInstaller
    )
    {
        $this->solver           = $solver;
        $this->symlinkInstaller = $symlinkInstaller;
        $this->defaultInstaller = $defaultInstaller;
    }

    /**
     * Returns the installation path of a package
     *
     * @param  PackageInterface $package
     *
     * @return string
     */
    public function getInstallPath(PackageInterface $package)
    {
        if ($this->solver->isProjectBuilder($package)) {
            return $this->symlinkInstaller->getInstallPath($package);
        }

        return $this->defaultInstaller->getInstallPath($package);
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface             $package
     */
    public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if ($this->solver->isProjectBuilder($package)) {
            $this->symlinkInstaller->install($repo, $package);
        } else {
            $this->defaultInstaller->install($repo, $package);
        }
    }

    /**
     * @param InstalledRepositoryInterface $repo
     * @param PackageInterface             $package
     *
     * @return bool
     */
    public function isInstalled(InstalledRepositoryInterface $repo, PackageInterface $package)
    {
        if ($this->solver->isProjectBuilder($package)) {
            return $this->symlinkInstaller->isInstalled($repo, $package);
        }

        return $this->defaultInstaller->isInstalled($repo, $package);
    }

    /**
     * {@inheritdoc}
     */
    public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
    {
        // If both packages are not shared
        if (!$this->solver->isProjectBuilder($initial) && !$this->solver->isProjectBuilder($target)) {
            $this->defaultInstaller->update($repo, $initial, $target);
        } else {
            if (!$repo->hasPackage($initial)) {
                throw new \InvalidArgumentException('Package is not installed : ' . $initial->getPrettyName());
            }

            $this->symlinkInstaller->update($repo, $initial, $target);
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
        if ($this->solver->isProjectBuilder($package)) {
            if (!$repo->hasPackage($package)) {
                throw new \InvalidArgumentException('Package is not installed : ' . $package->getPrettyName());
            }

            $this->symlinkInstaller->uninstall($repo, $package);
        } else {
            $this->defaultInstaller->uninstall($repo, $package);
        }
    }

    /**
     * @param string $packageType
     *
     * @return bool
     */
    public function supports($packageType)
    {
        // The solving process is in ProjectBuilderSolver::isProjectBuilder() method

        return true;
    }
}
