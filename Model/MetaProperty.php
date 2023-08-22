<?php

namespace DamConsultants\BynderDAM\Model;

class MetaProperty extends \Magento\Framework\Model\AbstractModel
{
    protected const CACHE_TAG = 'DamConsultants_BynderDAM';

    /**
     * @var $_cacheTag
     */
    protected $_cacheTag = 'DamConsultants_BynderDAM';

    /**
     * @var $_eventPrefix
     */
    protected $_eventPrefix = 'DamConsultants_BynderDAM';

    /**
     * Meta Property
     *
     * @return $this
     */
    protected function _construct()
    {
        $this->_init(\DamConsultants\BynderDAM\Model\ResourceModel\MetaProperty::class);
    }
}
