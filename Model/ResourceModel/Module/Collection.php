<?php

namespace CSSoft\Core\Model\ResourceModel\Module;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    protected $_eventPrefix = 'cssoft_core_module_collection';

    protected $_eventObject = 'module_collection';

    /**
     * @var string
     */
    protected $_idFieldName = 'code';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('CSSoft\Core\Model\Module', 'CSSoft\Core\Model\ResourceModel\Module');
    }
}
