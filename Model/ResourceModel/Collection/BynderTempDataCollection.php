<?php

namespace DamConsultants\BynderDAM\Model\ResourceModel\Collection;

class BynderTempDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\BynderDAM\Model\BynderTempData::class,
            \DamConsultants\BynderDAM\Model\ResourceModel\BynderTempData::class
        );
    }
}
