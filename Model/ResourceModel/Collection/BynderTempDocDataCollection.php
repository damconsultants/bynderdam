<?php

namespace DamConsultants\BynderDAM\Model\ResourceModel\Collection;

class BynderTempDocDataCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * BynderConfigSyncDataCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\BynderDAM\Model\BynderTempDocData::class,
            \DamConsultants\BynderDAM\Model\ResourceModel\BynderTempDocData::class
        );
    }
}
