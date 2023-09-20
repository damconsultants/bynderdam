<?php

namespace DamConsultants\BynderDAM\Model\ResourceModel\Collection;

class BynderAutoReplaceDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\BynderDAM\Model\BynderAutoReplaceData::class,
            \DamConsultants\BynderDAM\Model\ResourceModel\BynderAutoReplaceData::class
        );
    }
}
