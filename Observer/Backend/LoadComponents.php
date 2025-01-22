<?php

namespace CSSoft\Core\Observer\Backend;

class LoadComponents implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \CSSoft\Core\Model\ComponentList\Loader
     */
    protected $loader;

    /**
     * @var \CSSoft\Core\Model\ModuleFactory
     */
    protected $moduleFactory;

    /**
     * @param \CSSoft\Core\Model\ComponentList\Loader  $loader
     * @param \CSSoft\Core\Model\ModuleFactory         $moduleFactory
     */
    public function __construct(
        \CSSoft\Core\Model\ComponentList\Loader $loader,
        \CSSoft\Core\Model\ModuleFactory $moduleFactory
    ) {
        $this->loader = $loader;
        $this->moduleFactory = $moduleFactory;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        foreach ($this->loader->getItems() as $component) {
            $module = $this->moduleFactory->create()->load($component['code']);
            $module->addData($component);
            $module->save();
        }
    }
}
