<?php

/*
 *
 */

namespace VerbruggenAlex\Composer;

use Composer\Composer;
use Composer\DependencyResolver\Operation\InstallOperation;
use Composer\Installer\LibraryInstaller;
use Composer\IO\IOInterface;
use Composer\EventDispatcher\Event as BaseEvent;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Plugin\PluginInterface;
use Composer\Script\Event as ScriptEvent;
use Composer\Script\ScriptEvents;
use VerbruggenAlex\Composer\Data\Package\ProjectBuilderDataManager;
use VerbruggenAlex\Composer\Installer\Config\ProjectBuilderInstallerConfig;
use VerbruggenAlex\Composer\Installer\Solver\ProjectBuilderSolver;
use VerbruggenAlex\Composer\Installer\ProjectBuilderInstaller;
use VerbruggenAlex\Composer\Installer\Solver\ProjectBuilderInstallerSolver;
use VerbruggenAlex\Composer\Util\SymlinkFilesystem;
use Composer\Installers\Installer;

/**
 * 
 */
class ProjectBuilderPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * Priority that plugin uses to register callbacks.
     */
    const CALLBACK_PRIORITY = 5;

    /**
     * @var Installer $composerInstallers
     */
    protected $composerInstallers;

    /**
     * @var SymlinkFilesystem
     */
    protected $filesystem;

    /**
     * @var ProjectBuilderInstallerConfig
     */
    protected $config;

    /**
     * @var ProjectBuilderInstaller $installer
     */
    protected $installer;

    /**
     * @param Composer    $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io)
    {

        $this->composerInstallers = new Installer($io, $composer);
        $this->config = $this->setConfig($composer);
        $this->filesystem = new SymlinkFilesystem();
        $this->installer = new ProjectBuilderInstaller(
          $io,
          $composer,
          $this->filesystem,
          new ProjectBuilderDataManager($composer),
          $this->config
        );

        $composer->getInstallationManager()->addInstaller(new ProjectBuilderInstallerSolver(
            new ProjectBuilderSolver($this->config),
            $this->installer,
            new LibraryInstaller($io, $composer)
        ));
    }

    /**
     * @param Composer $composer
     *
     * @return ProjectBuilderInstallerConfig $config
     */
    protected function setConfig(Composer $composer)
    {
        $config =  new ProjectBuilderInstallerConfig(
            $composer->getConfig(),
            $composer->getPackage()->getExtra()
        );

        $composer->getConfig()->merge([
          'config' => [
            'vendor-dir' => $config->getBuildDirectory('relative', 'vendorDir'),
            'bin-dir' => $config->getBuildDirectory('relative', 'binDir'),
          ]
        ]);

        return $config;
    }

    /**
     * Todo: describe.
     *
     * @param PackageEvent $event
     */
    public function onPostPackageInstall(PackageEvent $event)
    {
        $op = $event->getOperation();
        if ($op instanceof InstallOperation) {
            $package = $op->getPackage();
            $type = $package->getType();

            if (!in_array($type, array('composer-plugin', 'composer-installer')) && $this->composerInstallers->supports($type)) {
                $baseDir = $this->config->getBuildDirectory('absolute', 'baseDir');
                $from = $this->installer->getInstallPath($package);
                $to = rtrim($baseDir . $this->composerInstallers->getInstallPath($package), '/');

                if ($package->getPrettyName() == $this->config->getBuildRoot()) {
                    $this->filesystem->copy($from, $baseDir);
                }
                else {
                    $this->filesystem->ensureSymlinkExists($from, $to);
                }
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
          PackageEvents::POST_PACKAGE_INSTALL =>
            array('onPostPackageInstall', self::CALLBACK_PRIORITY),
//          ScriptEvents::POST_INSTALL_CMD =>
//            array('onPostInstallOrUpdate', self::CALLBACK_PRIORITY),
//          ScriptEvents::POST_UPDATE_CMD =>
//            array('onPostInstallOrUpdate', self::CALLBACK_PRIORITY),
        );
    }
}
