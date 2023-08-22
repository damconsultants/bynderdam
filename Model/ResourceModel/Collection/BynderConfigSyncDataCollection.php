<?php

namespace DamConsultants\BynderDAM\Model\ResourceModel\Collection;

class BynderConfigSyncDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\BynderDAM\Model\BynderConfigSyncData::class,
            \DamConsultants\BynderDAM\Model\ResourceModel\BynderConfigSyncData::class
        );
    }
}
