<?php

namespace DamConsultants\BynderDAM\Block\System\Config;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use \Magento\Store\Model\StoreManagerInterface;

class Button extends Field
{
    /**
     * Block template.
     *
     * @var string
     */
    protected $_template = 'DamConsultants_BynderDAM::system/config/button.phtml';
    /**
     * Block template.
     *
     * @var string
     */
    protected $_storeManager;
    /**
     * Block template.
     *
     * @var string
     */
    protected $HelperBackend;
    /**
     * Block template.
     *
     * @var string
     */
    protected $_datahelper;

    /**
     * Button
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Backend\Helper\Data $HelperBackend
     * @param \DamConsultants\BynderDAM\Helper\Data $datahelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        \Magento\Backend\Helper\Data $HelperBackend,
        \DamConsultants\BynderDAM\Helper\Data $datahelper,
        array $data = []
    ) {
        $this->_storeManager = $storeManager;
        $this->HelperBackend = $HelperBackend;
        $this->_datahelper = $datahelper;
        parent::__construct($context, $data);
    }

    /**
     * Render
     *
     * @return $this
     * @param object $element
     */
    public function render(AbstractElement $element)
    {
        $element->unsScope()->unsCanUseWebsiteValue()->unsCanUseDefaultValue();
        return parent::render($element);
    }

    /**
     * Return get Elemrent Html
     *
     * @return string
     * @param object $element
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $originalData = $element->getOriginalData();
        $path = explode('/', $originalData['path']);
        $url = $this->_storeManager->getStore()->getBaseUrl();
        $this->addData(
            [
                'mp_active_url'      => $url . 'bynder/index/activate',
                'mp_module_html_id'  => implode('_', $path)
            ]
        );
        return $this->_toHtml();
    }

    /**
     * Get Custom Url
     *
     * @return string
     */
    public function getCustomUrl()
    {
        return $this->getUrl();
    }

    /**
     * Get Iframe Url
     *
     * @return string
     */
    public function getIframeurl()
    {
        return $this->_datahelper->getIframeUrl();
    }

    /**
     * Get Button Html
     *
     * @return string
     */
    public function getButtonHtml()
    {
        $activeButton = $this->getLayout()
            ->createBlock(\Magento\Backend\Block\Widget\Button::class)
            ->setData([
                'id'      => 'bynder_module_active',
                'label'   => __('Get License Key'),
                'onclick' => 'javascript:mageplazaModuleActive(); return false;',
            ]);
        return $activeButton->toHtml();
    }
}
