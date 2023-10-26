<?php
namespace DamConsultants\BynderDAM\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Ui\Component\MassAction\Filter;
use DamConsultants\BynderDAM\Model\ResourceModel\Collection\BynderSycDataCollectionFactory;

class MassResyncData extends Action
{
    /**
     * @var $collectionFactory
     */
    public $collectionFactory;
    /**
     * @var $filter
     */
    public $filter;
    /**
     * Closed constructor.
     *
     * @param Context $context
     * @param Filter $filter
     * @param BynderSycDataCollectionFactory $collectionFactory
     * @param \DamConsultants\BynderDAM\Model\BynderSycDataFactory $bynderFactory
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Catalog\Model\Product\Action $action
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        Context $context,
        Filter $filter,
        BynderSycDataCollectionFactory $collectionFactory,
        \DamConsultants\BynderDAM\Model\BynderSycDataFactory $bynderFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Model\Product\Action $action,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->bynderFactory = $bynderFactory;
        $this->_productRepository = $productRepository;
        $this->action = $action;
        $this->storeManagerInterface = $storeManagerInterface;
        parent::__construct($context);
    }
    /**
     * Execute
     *
     */
    public function execute()
    {
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $storeId = $this->storeManagerInterface->getStore()->getId();
            $count = 0;
            foreach ($collection as $model) {
                if ($model->getLable() == 0) {
                    $_product = $this->_productRepository->get($model->getSku());
                    $product_ids[] = $_product->getId();
                    $model = $this->bynderFactory->create()->load($model->getId());
                    $model->setLable('2');
                    $model->save();
                    $count++;
                }
            }
            $updated_values = [
                'bynder_cron_sync' => null
            ];
            $this->action->updateAttributes(
                $product_ids,
                $updated_values,
                $storeId
            );
            $this->messageManager->addSuccess(__('A total of %1 data(s) have been Re-Sync.', $count));
        } catch (\Exception $e) {
            $this->messageManager->addError(__($e->getMessage()));
        }
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('bynder/index/grid');
    }
    /**
     * Is Allowed
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('DamConsultants_BynderDAM::resync');
    }
}
