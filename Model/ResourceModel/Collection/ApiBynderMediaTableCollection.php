<?php

namespace DamConsultants\BynderDAM\Model\ResourceModel\Collection;

class ApiBynderMediaTableCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\BynderDAM\Model\ApiBynderMediaTable::class,
            \DamConsultants\BynderDAM\Model\ResourceModel\ApiBynderMediaTable::class
        );
    }
}
