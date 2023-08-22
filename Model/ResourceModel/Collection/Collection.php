<?php

namespace DamConsultants\BynderDAM\Model\ResourceModel\Collection;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Collection
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(
            \DamConsultants\BynderDAM\Model\Bynder::class,
            \DamConsultants\BynderDAM\Model\ResourceModel\Bynder::class
        );
    }
}
