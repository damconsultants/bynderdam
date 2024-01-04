<?php

namespace DamConsultants\BynderDAM\Observer;

use Magento\Framework\Event\ObserverInterface;
use DamConsultants\BynderDAM\Model\ResourceModel\Collection\MetaPropertyCollectionFactory;

class ProductDataSaveAfter implements ObserverInterface
{
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $_resource;

    /**
     * @var \Magento\Catalog\Model\Product\Action
     */
    protected $productActionObject;

    /**
     * Product save after
     * @param \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager
     * @param \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory
     * @param \Magento\Catalog\Model\Product\Action $productActionObject
     * @param \DamConsultants\BynderDAM\Model\BynderSycDataFactory $byndersycData
     * @param \DamConsultants\BynderDAM\Model\ResourceModel\Collection\BynderSycDataCollectionFactory $collection
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \DamConsultants\BynderDAM\Helper\Data $DataHelper
     * @param MetaPropertyCollectionFactory $metaPropertyCollectionFactory
     * @param \Magento\Store\Model\StoreManagerInterface $storeManagerInterface
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Backend\Model\View\Result\Redirect $resultRedirect
     */

    public function __construct(
        \Magento\Framework\Stdlib\CookieManagerInterface $cookieManager,
        \Magento\Framework\Stdlib\Cookie\CookieMetadataFactory $cookieMetadataFactory,
        \Magento\Catalog\Model\Product\Action $productActionObject,
        \DamConsultants\BynderDAM\Model\BynderSycDataFactory $byndersycData,
        \DamConsultants\BynderDAM\Model\ResourceModel\Collection\BynderSycDataCollectionFactory $collection,
        \Magento\Framework\App\ResourceConnection $resource,
        \DamConsultants\BynderDAM\Helper\Data $DataHelper,
        MetaPropertyCollectionFactory $metaPropertyCollectionFactory,
        \Magento\Store\Model\StoreManagerInterface $storeManagerInterface,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\View\Result\Redirect $resultRedirect
    ) {
        $this->cookieManager = $cookieManager;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->productActionObject = $productActionObject;
        $this->_byndersycData = $byndersycData;
        $this->datahelper = $DataHelper;
        $this->metaPropertyCollectionFactory = $metaPropertyCollectionFactory;
        $this->_collection = $collection;
        $this->_resource = $resource;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->messageManager = $messageManager;
        $this->resultRedirectFactory = $resultRedirect;
    }
    /**
     * Execute
     *
     * @return $this
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $product = $observer->getProduct();
        $productId = $observer->getProduct()->getId();
        $product_sku_key = $product->getData('sku');
        $bynder_multi_img = $product->getData('bynder_multi_img');
        /**Doing new code and new requirements for theines */
        $bynder_document = $product->getData('bynder_document');
        $storeId = $this->storeManagerInterface->getStore()->getId();
        $connection = $this->_resource->getConnection();
        $tableName = $connection->getTableName("bynder_cron_data");
        $image_coockie_id = $this->cookieManager->getCookie('image_coockie_id');
        $doc_coockie_id = $this->cookieManager->getCookie('doc_coockie_id');
        $table_name_image = $connection->getTableName('bynder_temp_data');
        $table_name_doc = $connection->getTableName('bynder_temp_doc_data');
        $all_meta_properties = $metaProperty_collection = $this->metaPropertyCollectionFactory->create()->getData();
        $collection_data_value = [];
        $collection_data_slug_val = [];
        if ($image_coockie_id != 0) {
            $selectimg = $connection->select()
            ->from(
                ['c' => $table_name_image],
            )
            ->where("c.id = ?", $image_coockie_id);
            $recordsimg = $connection->fetchAll($selectimg);
            if (isset($recordsimg)) {
                foreach ($recordsimg as $record) {
                    $image = $record['value'];
                }
            }
        } else {
            $image = $bynder_multi_img;
        }
        $new_bynder_array = $image;
        $old_bynder_array = $bynder_multi_img;
        $image_details[] = [
            "old" => $bynder_multi_img,
            "new" => $image
        ];
        if ($doc_coockie_id != 0) {
            $selectdoc = $connection->select()
            ->from(
                ['c' => $table_name_doc],
            )
            ->where("c.id = ?", $doc_coockie_id);
            $recordsdoc = $connection->fetchAll($selectdoc);
            if (isset($recordsdoc)) {
                foreach ($recordsdoc as $recorddoc) {
                    $document = $recorddoc['value'];
                }
            }
        }
        if (count($metaProperty_collection) >= 1) {
            foreach ($metaProperty_collection as $key => $collection_value) {
                $collection_data_value[] = [
                    'id' => $collection_value['id'],
                    'property_name' => $collection_value['property_name'],
                    'property_id' => $collection_value['property_id'],
                    'magento_attribute' => $collection_value['magento_attribute'],
                    'attribute_id' => $collection_value['attribute_id'],
                    'bynder_property_slug' => $collection_value['bynder_property_slug'],
                    'system_slug' => $collection_value['system_slug'],
                    'system_name' => $collection_value['system_name']
                ];
                $collection_data_slug_val[$collection_value['system_slug']] = [
                    'bynder_property_slug' => $collection_value['bynder_property_slug'],
                    'property_id' => $collection_value['property_id']
                ];
            }
        }
        if (isset($collection_data_slug_val["sku"]["property_id"])) {
            $metaProperty_Collections = $collection_data_slug_val["sku"]["property_id"];
            /******************************Document Section******************************************************************************** */
            if (isset($document)) {
                $this->productActionObject->updateAttributes([$productId], ['bynder_document' => $document], $storeId);
                $where = ["id = ?" => $doc_coockie_id];
                $connection->delete($table_name_doc, $where);
                $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
                $publicCookieMetadata->setDurationOneYear();
                $publicCookieMetadata->setPath('/');
                $publicCookieMetadata->setHttpOnly(false);

                $this->cookieManager->setPublicCookie(
                    'doc_coockie_id',
                    0,
                    $publicCookieMetadata
                );
            }
            /***************************Video and Image Section ***************************************************************** */
            $video = "";
            $flag = 0;
            $type = [];
			try {
				if (!empty($image)) {
					$img_array = json_decode($image, true);
					foreach ($img_array as $img) {
						$type[] = $img['item_type'];
					}
					/*  IMAGE & VIDEO == 1
					IMAGE == 2
					VIDEO == 3 */
					if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
						$flag = 1;
					} elseif (in_array("IMAGE", $type)) {
						$flag = 2;
					} elseif (in_array("VIDEO", $type)) {
						$flag = 3;
					}
					$this->productActionObject->updateAttributes([$productId], ['bynder_isMain' => $flag], $storeId);
					$this->productActionObject->updateAttributes([$productId], ['bynder_multi_img' => $image], $storeId);
					$where = ["id = ?" => $image_coockie_id];
					$connection->delete($table_name_image, $where);
					$publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
					$publicCookieMetadata->setDurationOneYear();
					$publicCookieMetadata->setPath('/');
					$publicCookieMetadata->setHttpOnly(false);
					$this->cookieManager->setPublicCookie(
						'image_coockie_id',
						0,
						$publicCookieMetadata
					);
				} else {
					$this->productActionObject->updateAttributes([$productId], ['bynder_isMain' => ""], $storeId);
					$this->productActionObject->updateAttributes([$productId], ['bynder_multi_img' => $image], $storeId);
					$this->productActionObject->updateAttributes([$productId], ['bynder_cron_sync' => ""], $storeId);
					$this->productActionObject->updateAttributes([$productId], ['bynder_auto_replace' => ""], $storeId);
					$where = ["id = ?" => $image_coockie_id];
					$connection->delete($table_name_image, $where);
					$publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
					$publicCookieMetadata->setDurationOneYear();
					$publicCookieMetadata->setPath('/');
					$publicCookieMetadata->setHttpOnly(false);
					$this->cookieManager->setPublicCookie(
						'image_coockie_id',
						0,
						$publicCookieMetadata
					);
				}
			} catch (\Exception $e) {
				$this->productActionObject->updateAttributes([$productId], ['bynder_multi_img' => $bynder_multi_img], $storeId);
				$where = ["id = ?" => $image_coockie_id];
				$connection->delete($table_name_image, $where);
				$publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
				$publicCookieMetadata->setDurationOneYear();
				$publicCookieMetadata->setPath('/');
				$publicCookieMetadata->setHttpOnly(false);
				$this->cookieManager->setPublicCookie(
					'image_coockie_id',
					0,
					$publicCookieMetadata
				);
			}
            
        }
    }
}
