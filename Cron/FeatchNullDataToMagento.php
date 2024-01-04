<?php

namespace DamConsultants\BynderDAM\Cron;

use Exception;
use \Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product\Action;
use DamConsultants\BynderDAM\Model\BynderFactory;
use DamConsultants\BynderDAM\Model\ResourceModel\Collection\MetaPropertyCollectionFactory;

class FeatchNullDataToMagento
{
    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * Featch Null Data To Magento
     * @param LoggerInterface $logger
     * @param ProductRepository $productRepository
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManagerInterface
     * @param \DamConsultants\BynderDAM\Helper\Data $DataHelper
     * @param \DamConsultants\BynderDAM\Model\BynderSycDataFactory $byndersycData
     * @param Action $action
     * @param MetaPropertyCollectionFactory $metaPropertyCollectionFactory
     * @param BynderFactory $bynder
     */
    public function __construct(
        LoggerInterface $logger,
        ProductRepository $productRepository,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManagerInterface,
        \DamConsultants\BynderDAM\Helper\Data $DataHelper,
        \DamConsultants\BynderDAM\Model\BynderSycDataFactory $byndersycData,
        Action $action,
        MetaPropertyCollectionFactory $metaPropertyCollectionFactory,
        BynderFactory $bynder
    ) {

        $this->logger = $logger;
        $this->_productRepository = $productRepository;
        $this->collectionFactory = $collectionFactory;
        $this->datahelper = $DataHelper;
        $this->action = $action;
        $this->_byndersycData = $byndersycData;
        $this->metaPropertyCollectionFactory = $metaPropertyCollectionFactory;
        $this->storeManagerInterface = $storeManagerInterface;
        $this->bynder = $bynder;
    }
    /**
     * Execute
     *
     * @return boolean
     */
    public function execute()
    {
		$enable = $this->datahelper->getFetchCronEnable();
		if (!$enable) {
			return false;
		}
        $product_collection = $this->collectionFactory->create();
        $product_sku_limit = (int)$this->datahelper->getProductSkuLimitConfig();
        if (!empty($product_sku_limit)) {
            $product_collection->getSelect()->limit($product_sku_limit);
        } else {
            $product_collection->getSelect()->limit(50);
        }
        $product_collection->addAttributeToSelect('*')
            ->addAttributeToFilter(
                [
                    ['attribute' => 'bynder_multi_img', 'null' => true]
                ]
            )
            ->addAttributeToFilter(
                [
                    ['attribute' => 'bynder_cron_sync', 'null' => true]
                ]
            )
            ->load();
        $property_id = null;
        $collection = $this->metaPropertyCollectionFactory->create()->getData();
        $meta_properties = $this->getMetaPropertiesCollection($collection);
        $collection_value = $meta_properties['collection_data_value'];
        $collection_slug_val = $meta_properties['collection_data_slug_val'];
        $productSku_array = [];
        foreach ($product_collection->getData() as $product) {
            $productSku_array[] = $product['sku'];
        }
        if (count($productSku_array) > 0) {
            foreach ($productSku_array as $sku) {
                if ($sku != "") {
                    $bd_sku = trim(preg_replace('/[^A-Za-z0-9-]/', '_', $sku));
                    $get_data = $this->datahelper->getImageSyncWithProperties($bd_sku, $property_id, $collection_value);
                    if (!empty($get_data) && $this->getIsJSON($get_data)) {
                        $respon_array = json_decode($get_data, true);
                        if ($respon_array['status'] == 1) {
                            $convert_array = json_decode($respon_array['data'], true);
                            if ($convert_array['status'] == 1) {
                                $current_sku = $sku;
                                try {
                                    $this->getDataItem($convert_array, $collection_slug_val, $current_sku);
                                } catch (Exception $e) {
                                    $insert_data = [
                                        "sku" => $sku,
                                        "message" => $e->getMessage(),
                                        "data_type" => "",
                                        'media_id' => "",
                                        'remove_for_magento' => '',
                                        'added_on_cron_compactview' => '',
                                        "lable" => "0"
                                    ];
                                    $this->getInsertDataTable($insert_data);
                                    $this->updateBynderCronSync($sku);
                                }
                            } else {
                                $insert_data = [
                                    "sku" => $sku,
                                    "message" => $convert_array['data'],
                                    "data_type" => "",
                                    'media_id' => "",
                                    'remove_for_magento' => '',
                                    'added_on_cron_compactview' => '',
                                    "lable" => "0"
                                ];
                                $this->getInsertDataTable($insert_data);
                                $this->updateBynderCronSync($sku);
                            }
                        } else {
                            $insert_data = [
                                "sku" => $sku,
                                "message" => 'Please Select The Metaproperty First.....',
                                "data_type" => "",
                                'media_id' => "",
                                'remove_for_magento' => '',
                                'added_on_cron_compactview' => '',
                                "lable" => "0"
                            ];
                            $this->getInsertDataTable($insert_data);
                        }
                    } else {
                        $insert_data = [
                            "sku" => $sku,
                            "message" => "Something problem in DAM side please contact to developer.",
                            "data_type" => "",
                            'media_id' => "",
                            'remove_for_magento' => '',
                            'added_on_cron_compactview' => '',
                            "lable" => "0"
                        ];
                        $this->getInsertDataTable($insert_data);
                    }
                }
            }
        }
        return true;
    }
    /**
     * Get Meta Properties Collection
     *
     * @param array $collection
     * @return array $response_array
     */
    public function getMetaPropertiesCollection($collection)
    {
        $collection_data_value = [];
        $collection_data_slug_val = [];
        if (count($collection) >= 1) {
            foreach ($collection as $key => $collection_value) {
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
                ];
            }
        }
        $response_array = [
            "collection_data_value" => $collection_data_value,
            "collection_data_slug_val" => $collection_data_slug_val
        ];
        return $response_array;
    }
    /**
     * Is int
     *
     * @return $this
     */
    public function getMyStoreId()
    {
        $storeId = $this->storeManagerInterface->getStore()->getId();
        return $storeId;
    }
    /**
     * Is Json
     *
     * @param string $string
     * @return $this
     */
    public function getIsJSON($string)
    {
        return ((json_decode($string)) === null) ? false : true;
    }
    /**
     * Is Json
     *
     * @param array $insert_data
     * @return $this
     */
    public function getInsertDataTable($insert_data)
    {
        $model = $this->_byndersycData->create();
        $data_image_data = [
            'sku' => $insert_data['sku'],
            'bynder_data' =>$insert_data['message'],
            'bynder_data_type' => $insert_data['data_type'],
            'media_id' => $insert_data['media_id'],
            'remove_for_magento' => $insert_data['remove_for_magento'],
            'added_on_cron_compactview' => $insert_data['added_on_cron_compactview'],
            'lable' => $insert_data['lable']
        ];
        
        $model->setData($data_image_data);
        $model->save();
    }
    /**
     * Get Data Item
     *
     * @param array $convert_array
     * @param array $collection_data_slug_val
     * @param string $current_sku
     */
    public function getDataItem($convert_array, $collection_data_slug_val, $current_sku)
    {
        $data_arr = [];
        $data_val_arr = [];
        if ($convert_array['status'] == 1) {
            foreach ($convert_array['data'] as $data_value) {
                $bynder_media_id = $data_value['id'];
                $image_data = $data_value['thumbnails'];
                $bynder_image_role = $image_data['magento_role_options'];
                $bynder_alt_text = $image_data['img_alt_text'];
                $sku_slug_name = "property_" . $collection_data_slug_val['sku']['bynder_property_slug'];
                $data_sku[0] = $current_sku;
                /*Below code for multiple derivative according to image roll */
                $images_urls_list = [];
                $new_magento_role_list = [];
                $new_bynder_alt_text =[];
                $new_bynder_mediaid_text = [];
                $new_image_role = [];
                if (count($bynder_image_role) > 0) {
                    foreach ($bynder_image_role as $m_bynder_role) {
                        if ($m_bynder_role == 0) {
                            $new_image_role = ['Base', 'Small', 'Thumbnail', 'Swatch'];
                            $alt_text_vl = $data_value["thumbnails"]["img_alt_text"];
                            if (is_array($data_value["thumbnails"]["img_alt_text"])) {
                                $alt_text_vl = implode(" ", $data_value["thumbnails"]["img_alt_text"]);
                            }
                            if(empty($alt_text_vl)){
                                $new_bynder_alt_text[] = "###\n";    
                            }else{
                                $new_bynder_alt_text[] = $alt_text_vl."\n";
                            }
                            $new_bynder_mediaid_text[] = $bynder_media_id;
                        } else {
                            $new_magento_role_list[] = "###"."\n";
                            /* this part added because sometime role not avaiable but alt text will be there*/
                            $alt_text_vl = $data_value["thumbnails"]["img_alt_text"];
                            if (!empty($alt_text_vl)) {
                                $new_bynder_alt_text[] = $alt_text_vl."\n";
                            } else {
                                $new_bynder_alt_text[] = "###\n";
                            }
                            $new_bynder_mediaid_text[] = $bynder_media_id."\n";
                        }
                    }
                } else {
                    $new_magento_role_list[] = "###"."\n";
                    /* this part added because sometime role not avaiable but alt text will be there*/
                    $alt_text_vl = $data_value["thumbnails"]["img_alt_text"];
                    if (!empty($alt_text_vl)) {
                        $new_bynder_alt_text[] = $alt_text_vl."\n";
                    } else {
                        $new_bynder_alt_text[] = "###\n";
                    }
                    $new_bynder_mediaid_text[] = $bynder_media_id."\n";
                }
                if ($data_value['type'] == "image") {
                    $image_link = $data_value['derivatives'][0]['public_url'];
                    array_push($data_arr, $data_sku[0]);
                    $data_p = [
                        "sku" => $data_sku[0],
                        "url" => [$image_link."\n"], /* chagne by kuldip ladola for testing perpose */
                        'magento_image_role' => $new_image_role,
                        'image_alt_text' => $new_bynder_alt_text,
                        'bynder_media_id_new' => $new_bynder_mediaid_text,
                        "type" => "image"
                    ];
                    array_push($data_val_arr, $data_p);
                } else {
                    if ($data_value['type'] == 'video') {
                        $video_link = $image_data["image_link"] . '@@' . $image_data["webimage"];
                        array_push($data_arr, $data_sku[0]);
                        $data_p = [
                            "sku" => $data_sku[0],
                            "url" => [$video_link. "\n"],
                            'magento_image_role' => $new_image_role,
                            'image_alt_text' => $new_bynder_alt_text,
                            'bynder_media_id_new' => $new_bynder_mediaid_text,
                            "type" => "video"
                        ];
                        array_push($data_val_arr, $data_p);

                    } else {
                        $doc_name = $data_value["name"];
                        $doc_name_with_space = preg_replace("/[^a-zA-Z]+/", "-", $doc_name);
                        $doc_link = $image_data["image_link"] . '@@' . $doc_name_with_space;
                        array_push($data_arr, $data_sku[0]);
                        $data_p = ["sku" => $data_sku[0], "url" => $doc_link];
                        array_push($data_val_arr, $data_p);
                    }

                }
            }
        }
        if (count($data_arr) > 0) {
            $this->getProcessItem($data_arr, $data_val_arr);
        }
    }
    /**
     * Get Process Item
     *
     * @param array $data_arr
     * @param array $data_val_arr
     */
    public function getProcessItem($data_arr, $data_val_arr)
    {
        $image_value_details_role = [];
        $temp_arr = [];
        foreach ($data_arr as $key => $skus) {
            $temp_arr[$skus][] =  implode("", $data_val_arr[$key]["url"]);
            $image_value_details_role[$skus][] = $data_val_arr[$key]["magento_image_role"];
            $image_alt_text[$skus][] = implode("", $data_val_arr[$key]["image_alt_text"]);
            $byn_md_id_new[$skus][] = implode("", $data_val_arr[$key]["bynder_media_id_new"]);
            $types = $data_val_arr[$key]['type'];
        }
        foreach ($temp_arr as $product_sku_key => $image_value) {
            $img_json = implode("", $image_value);
            $mg_role = $image_value_details_role[$product_sku_key];
            $image_alt_text_value = implode("", $image_alt_text[$product_sku_key]);
            $this->getUpdateImage(
                $img_json,
                $product_sku_key,
                $mg_role,
                $image_alt_text_value,
                $byn_md_id_new,
                $types
            );
        }
    }
    /**
     * Upate Item
     *
     * @return $this
     * @param string $img_json
     * @param string $product_sku_key
     * @param string $mg_img_role_option
     * @param string $img_alt_text
     * @param string $bynder_media_ids
     * @param string $types
     */
    public function getUpdateImage($img_json, $product_sku_key, $mg_img_role_option, $img_alt_text, $bynder_media_ids, $types)
    {
        $model = $this->_byndersycData->create();
        $image_detail = [];
        $video_detail = [];
        try {
            $storeId = $this->storeManagerInterface->getStore()->getId();
            $_product = $this->_productRepository->get($product_sku_key);
            $product_ids = $_product->getId();
            $image_value = $_product->getBynderMultiImg();
            $doc_value = $_product->getBynderDocument();
            $bynder_media_id = $bynder_media_ids[$product_sku_key];
            if ($types == "image") {
                $new_image_array = explode("\n", $img_json);
                $new_alttext_array = explode("\n", $img_alt_text);
                $new_magento_role_option_array = $mg_img_role_option;
                foreach ($new_image_array as $vv => $image_value) {
                    if (trim($image_value) != "" && $image_value != "no image") {
                        $img_altText_val = "";
                        if (isset($new_alttext_array[$vv])) {
                            if ($new_alttext_array[$vv] != "###" && strlen(trim($new_alttext_array[$vv])) > 0) {
                                $img_altText_val = $new_alttext_array[$vv];
                            }
                        }
                        $curt_img_role = [];
                        if ($new_magento_role_option_array[$vv] != "###") {
                            $curt_img_role = $new_magento_role_option_array[$vv];
                        }
                        $find_video = strpos($image_value, "@@");
                        if (!$find_video) {
                            $image_detail[] = [
                                "item_url" => $image_value,
                                "alt_text" => $img_altText_val,
                                "image_role" => $curt_img_role,
                                "item_type" => 'IMAGE',
                                "thum_url" => $image_value,
                                "bynder_md_id" => $bynder_media_id[$vv],
                                "is_import" => 0
                            ];
                            $data_image_data = [
                                'sku' => $product_sku_key,
                                'message' => $image_value,
                                'data_type' => '1',
                                'media_id' => $bynder_media_id[$vv],
                                'remove_for_magento' => '1',
                                'added_on_cron_compactview' => '1',
                                'lable' => 1
                            ];
                            $this->getInsertDataTable($data_image_data);
                        } else {
                            $item_url = explode("?", $image_value);
                            $thum_url = explode("@@", $image_value);
                            $media_video_explode = explode("/", $item_url[0]);
        
                            $video_detail[] = [
                                "item_url" => $item_url[0],
                                "image_role" => null,
                                "item_type" => 'VIDEO',
                                "thum_url" => $thum_url[1],
                                "bynder_md_id" => $bynder_media_id[$vv]
                            ];
                            $data_video_data = [
                                'sku' => $product_sku_key,
                                'message' => $item_url[0],
                                'data_type' => '3',
                                'media_id' => $media_video_explode[5],
                                'remove_for_magento' => '1',
                                'added_on_cron_compactview' => '1',
                                'lable' => 1
                            ];
                            $this->getInsertDataTable($data_video_data);
        
                        }
                        $total_new_value = count($image_detail);
                        if ($total_new_value > 1) {
                            foreach ($image_detail as $nn => $n_img) {
                                if ($n_img['item_type'] == "IMAGE" && $nn != ($total_new_value - 1)) {
                                    if ($new_magento_role_option_array[$vv] != "###") {
                                        $new_mg_role_array = (array)$new_magento_role_option_array[$vv];
                                        if (count($n_img["image_role"]) > 0 && count($new_mg_role_array) > 0) {
                                            $result_val=array_diff($n_img["image_role"], $new_mg_role_array);
                                            $image_detail[$nn]["image_role"] = $result_val;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                $marge = array_merge($image_detail, $video_detail);
                foreach ($marge as $img) {
                    $type[] = $img['item_type'];
                }
                $flag = 0;
                if (in_array("IMAGE", $type) && in_array("VIDEO", $type)) {
                    $flag = 1;
                } elseif (in_array("IMAGE", $type)) {
                    $flag = 2;
                } elseif (in_array("VIDEO", $type)) {
                    $flag = 3;
                }
                $new_value_array = json_encode($marge, true);
                
                $updated_values = [
                    'bynder_multi_img' => $new_value_array,
                    'bynder_isMain' => $flag,
                    'bynder_cron_sync' => 1
                ];
                $this->action->updateAttributes(
                    [$product_ids],
                    $updated_values,
                    $storeId
                );
            } else {
                $new_doc_array = explode(" \n", $img_json);
                $doc_detail = [];
                foreach ($new_doc_array as $vv => $doc_value) {
                    $item_url = explode("?", $doc_value);
                    $media_doc_explode = explode("/", $item_url[0]);
                    $doc_detail[] = [
                        "item_url" => $item_url[0],
                        "item_type" => 'DOCUMENT',
                        "bynder_md_id" => $bynder_media_id[$vv]
                    ];
                    $data_doc_value = [
                        'sku' => $product_sku_key,
                        'message' => $item_url[0],
                        'data_type' => '2',
                        'media_id' => $bynder_media_id[$vv],
                        'remove_for_magento' => '1',
                        'added_on_cron_compactview' => '1',
                        'lable' => 1
                    ];
                    $this->getInsertDataTable($data_doc_value);
                }
                $new_value_array = json_encode($doc_detail, true);
                $this->action->updateAttributes(
                    [$product_ids],
                    ['bynder_document' => $new_value_array,'bynder_cron_sync' => 1],
                    $storeId
                );
            }
        } catch (Exception $e) {
            $insert_data = [
                "sku" => $product_sku_key,
                "message" => $e->getMessage(),
                "data_type" => "",
                'media_id' => "",
                'remove_for_magento' => '',
                'added_on_cron_compactview' => '',
                "lable" => "0"
            ];
            $this->getInsertDataTable($insert_data);
        }
    }
    /**
     * Update Bynder cron sync status
     *
     * @param string $sku
     */
    public function updateBynderCronSync($sku)
    {
        $updated_values = [
            'bynder_cron_sync' => 2
        ];

        $storeId = $this->getMyStoreId();
        $_product = $this->_productRepository->get($sku);
        $product_ids = $_product->getId();

        $this->action->updateAttributes(
            [$product_ids],
            $updated_values,
            $storeId
        );
    }
}
