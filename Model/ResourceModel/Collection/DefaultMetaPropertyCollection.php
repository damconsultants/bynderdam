<?php

namespace DamConsultants\BynderDAM\Model\ResourceModel\Collection;

class DefaultMetaPropertyCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * MetaPropertyCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\BynderDAM\Model\DefaultMetaProperty::class,
            \DamConsultants\BynderDAM\Model\ResourceModel\DefaultMetaProperty::class
        );
    }
}
