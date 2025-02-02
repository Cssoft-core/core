<?php

namespace CSSoft\Core\Model\Module;

class Installer
{
    /**
     * @var \CSSoft\Core\Model\Module
     */
    protected $module;

    /**
     * @var \CSSoft\Core\Model\ModuleFactory
     */
    protected $moduleFactory;

    /**
     * @var \CSSoft\Core\Model\Module\MessageLogger
     */
    protected $messageLogger;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Constructor
     *
     * @param \CSSoft\Core\Model\Module $module
     * @param \CSSoft\Core\Model\ModuleFactory $moduleFactory
     * @param \CSSoft\Core\Model\Module\MessageLogger $messageLogger
     */
    public function __construct(
        \CSSoft\Core\Model\Module $module,
        \CSSoft\Core\Model\ModuleFactory $moduleFactory,
        \CSSoft\Core\Model\Module\MessageLogger $messageLogger,
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->module = $module;
        $this->moduleFactory = $moduleFactory;
        $this->messageLogger = $messageLogger;
        $this->objectManager = $objectManager;
    }

    /**
     * 1. Run dependent modules upgrades
     * 2. Run module upgrades on installed stores
     * 3. Run module upgrades on new stores
     *
     * @return void
     */
    public function up()
    {
        $oldStores = $this->module->getOldStores();
        $newStores = $this->module->getNewStores();
        if (!count($oldStores) && !count($newStores)) {
            return;
        }

        foreach ($this->module->getDepends() as $moduleCode) {
            if (0 !== strpos($moduleCode, 'CSSoft')) {
                continue;
            }
            $this->getModuleObject($moduleCode)->up();
        }

        $this->module->save();
    }

    /**
     * Retrieve singleton instance of error logger, used in upgrade file
     * to write errors and module controller to read them.
     *
     * @return \CSSoft\Core\Model\Module\MessageLogger
     */
    public function getMessageLogger()
    {
        return $this->messageLogger;
    }

    /**
     * Checks is the upgrades directory is exists in the module
     *
     * @return boolean
     * @deprecated
     */
    public function hasUpgradesDir()
    {
        return false;
    }

    /**
     * @param string $from
     * @return array
     * @deprecated
     */
    public function getUpgradesToRun($from = null)
    {
        return [];
    }

    /**
     * @return array
     * @deprecated
     */
    public function getUpgradeFiles()
    {
        return [];
    }

    /**
     * @return null
     * @deprecated
     */
    public function getUpgradesDir()
    {
        return null;
    }

    /**
     * Returns loded module object with copied new_store_ids and skip_upgrade
     * instructions into it
     *
     * @return CSSoft\Core\Model\Module
     */
    protected function getModuleObject($code)
    {
        $module = $this->moduleFactory->create()
            ->load($code)
            ->setNewStores($this->module->getNewStores());

        if (!$module->getIdentityKey()) {
            $module->setIdentityKey($this->module->getIdentityKey());
        }

        return $module;
    }
}
