<?php
namespace DamConsultants\BynderDAM\Ui\Component\Listing\Column;

use Exception;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class ReSyncData extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * @var urlBuilder
     */
    public $urlBuilder;
    /**
     * Closed constructor.
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \DamConsultants\BynderDAM\Model\BynderSycDataFactory $BynderSycDataFactory
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \DamConsultants\BynderDAM\Model\BynderSycDataFactory $BynderSycDataFactory,
        \Magento\Framework\App\ResourceConnection $resource,
        UrlInterface $urlBuilder,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->_resource = $resource;
        $this->bynderSycDataFactory = $BynderSycDataFactory;
        $this->_productRepository = $productRepository;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }
    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                $sku = $item["sku"];
                try {
                    $_product = $this->_productRepository->get($sku);
                    $product_bynder_cron_val = $_product->getBynderCronSync();
                    if (isset($item['id'])) {
                        $viewUrlPath = $this->getData('config/viewUrlPath');
                        $urlEntityParamName = $this->getData('config/urlEntityParamName');
                        if ($item['media_id'] == null && $product_bynder_cron_val != null) {
                            $item[$this->getData('name')] = [
                                'view' => [
                                    'href' => $this->urlBuilder->getUrl(
                                        $viewUrlPath,
                                        [
                                            $urlEntityParamName => $item['id'],
                                        ]
                                    ),
                                    'label' => __('Re-Sync'),
                                    'class' => 'action-primary',
                                ],
                            ];
                        }
                    }
                } catch (Exception $e) {
                    $connection = $this->_resource->getConnection();
                    $tableName = $connection->getTableName("bynder_cron_data");
                    $connection->delete($tableName, [$connection->quoteInto('sku = ?', $sku)]);
                }
            }
        }
        return $dataSource;
    }
}
