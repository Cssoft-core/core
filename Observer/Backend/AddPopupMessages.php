<?php

namespace CSSoft\Core\Observer\Backend;

class AddPopupMessages implements \Magento\Framework\Event\ObserverInterface
{
    protected $popupMessageManager;

    public function __construct(\CSSoft\Core\Helper\PopupMessageManager $popupMessageManager)
    {
        $this->popupMessageManager = $popupMessageManager;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->popupMessageManager->getPopups()) {
            $block = $observer->getLayout()->addBlock(
                'Magento\Framework\View\Element\Template',
                'cssoft_popup_messages',
                'before.body.end'
            );
            $block->setTemplate('CSSoft_Core::popup_messages.phtml')
                ->setData('popup_messenger', $this->popupMessageManager);
        }
    }
}
