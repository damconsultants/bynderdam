<?php
namespace DamConsultants\BynderDAM\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;

class DeleteCronSyncAutoData extends Action
{
    public $BynderConfigSyncDataFactory;
    /**
     * Closed constructor.
     *
     * @param Context $context
     * @param DamConsultants\BynderDAM\Model\BynderAutoReplaceDataFactory $BynderSycDataFactory
     */
    public function __construct(
        Context $context,
        \DamConsultants\BynderDAM\Model\BynderAutoReplaceDataFactory $BynderSycDataFactory
    ) {
        $this->bynderSycDataFactory = $BynderSycDataFactory;
        parent::__construct($context);
    }
    /**
     * Execute
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $id = $this->getRequest()->getParam('id');
        try {
            $syncModel = $this->bynderSycDataFactory->create();
            $syncModel->load($id);
            $syncModel->delete();
            $this->messageManager->addSuccessMessage(__('You deleted the sync data.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }
        return $resultRedirect->setPath('bynder/index/replacecrongrid');
    }

    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('DamConsultants_BynderDAM::delete');
    }
}
