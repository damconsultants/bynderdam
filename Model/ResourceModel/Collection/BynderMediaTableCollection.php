<?php

namespace DamConsultants\BynderDAM\Model\ResourceModel\Collection;

class BynderMediaTableCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\BynderDAM\Model\BynderMediaTable::class,
            \DamConsultants\BynderDAM\Model\ResourceModel\BynderMediaTable::class
        );
    }
}
