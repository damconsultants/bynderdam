<?php

namespace DamConsultants\BynderDAM\Model\ResourceModel\Collection;

class MetaPropertyCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * MetaPropertyCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\BynderDAM\Model\MetaProperty::class,
            \DamConsultants\BynderDAM\Model\ResourceModel\MetaProperty::class
        );
    }
}
