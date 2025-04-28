<?php

namespace DamConsultants\BynderDAM\Model\ResourceModel\Collection;

class MagentoSkuCollection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    
    /**
     * MagentoSkuCollection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\BynderDAM\Model\MagentoSku::class,
            \DamConsultants\BynderDAM\Model\ResourceModel\MagentoSku::class
        );
    }
}
