<?php

namespace DamConsultants\BynderDAM\Model\ResourceModel\Collection;

class BynderSycDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderSycDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\BynderDAM\Model\BynderSycData::class,
            \DamConsultants\BynderDAM\Model\ResourceModel\BynderSycData::class
        );
    }
}
