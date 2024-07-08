<?php

namespace DamConsultants\BynderDAM\Model\ResourceModel\Collection;

class BynderDeleteDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\BynderDAM\Model\BynderDeleteData::class,
            \DamConsultants\BynderDAM\Model\ResourceModel\BynderDeleteData::class
        );
    }
}
