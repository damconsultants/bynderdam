<?php

namespace DamConsultants\BynderDAM\Cron;

use Exception;
use \Psr\Log\LoggerInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\Product\Action;
use DamConsultants\BynderDAM\Model\BynderFactory;
use DamConsultants\BynderDAM\Model\ResourceModel\Collection\MetaPropertyCollectionFactory;

class AutoAddFromMagento
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
     * @param \DamConsultants\BynderDAM\Model\BynderAutoReplaceDataFactory $bynderAutoReplaceData,
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
        \DamConsultants\BynderDAM\Model\BynderAutoReplaceDataFactory $bynderAutoReplaceData,
        Action $action,
        MetaPropertyCollectionFactory $metaPropertyCollectionFactory,
        BynderFactory $bynder
    ) {

        $this->logger = $logger;
        $this->_productRepository = $productRepository;
        $this->collectionFactory = $collectionFactory;
        $this->datahelper = $DataHelper;
        $this->action = $action;
        $this->_bynderAutoReplaceData = $bynderAutoReplaceData;
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
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/AutoAddFromMagento.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("Auto Add Image Value");
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
                    ['attribute' => 'bynder_multi_img', 'notnull' => true]
                ]
            )
            ->addAttributeToFilter(
                [
                    ['attribute' => 'bynder_auto_replace', 'null' => true]
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
        $logger->info("Sku => ". json_encode($productSku_array, true));
        if (count($productSku_array) > 0) {
            foreach ($productSku_array as $sku) {
                if ($sku != "") {
                    $bd_sku = trim(preg_replace('/[^A-Za-z0-9]/', '_', $sku));
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
                                        'media_id' => "",
                                        "data_type" => ""
                                    ];
                                    $this->getInsertDataTable($insert_data);
                                }
                                
                            } else {
                                $insert_data = [
                                    "sku" => $sku,
                                    "message" => $e->getMessage(),
                                    'media_id' => "",
                                    "data_type" => ""
                                ];
                                $this->getInsertDataTable($insert_data);
                            }
                        } else {
                            $insert_data = [
                                "sku" => $sku,
                                "message" => 'Please Select The Metaproperty First.....',
                                'media_id' => "",
                                "data_type" => ""
                            ];
                            $this->getInsertDataTable($insert_data);
                        }
                    } else {
                        $insert_data = [
                            "sku" => $sku,
                            "message" => "Something problem in DAM side please contact to developer.",
                            'media_id' => "",
                            "data_type" => ""
                        ];
                        $this->getInsertDataTable($insert_data);
                    }
                }
            }
        } else {
            $product_collection = $this->collectionFactory->create()
            ->addAttributeToSelect('*')
            ->addAttributeToFilter('visibility', \Magento\Catalog\Model\Product\Visibility::VISIBILITY_BOTH)
            ->addAttributeToFilter('status', \Magento\Catalog\Model\Product\Attribute\Source\Status::STATUS_ENABLED)
            ->addAttributeToFilter(
                [
                    ['attribute' => 'bynder_auto_replace', 'notnull' => true]
                ]
            )
            ->load();
            $id = [];
            foreach ($product_collection as $product) {
                $id[] = $product->getId();
            }
            $storeId = $this->storeManagerInterface->getStore()->getId();
            $this->action->updateAttributes(
                $id,
                ['bynder_auto_replace' => ""],
                $storeId
            );
        }
        $logger->info("Bynder Auto Replace Attribute Null");
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
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/AutoAddFromMagento.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("getMetaPropertiesCollection");
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
        $model = $this->_bynderAutoReplaceData->create();
        $data_image_data = [
            'sku' => $insert_data['sku'],
            'bynder_data' =>$insert_data['message'],
            'media_id' => $insert_data['media_id'],
            'bynder_data_type' => $insert_data['data_type']
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
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/AutoAddFromMagento.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("getDataItem");
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
                            /*$new_bynder_alt_text[] = (strlen($alt_text_vl) > 0)?$alt_text_vl."\n":"###\n";*/
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
                        'bynder_media_id_new' => $new_bynder_mediaid_text
                    ];
                    $logger->info("data_p => ". json_encode($data_p, true));
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
                        $logger->info("data_p => ". json_encode($data_p, true));
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
        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/AutoAddFromMagento.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("getProcessItem");

        $image_value_details_role = [];
        $temp_arr = [];
        foreach ($data_arr as $key => $skus) {
            $temp_arr[$skus][] =  implode("", $data_val_arr[$key]["url"]);
            $image_value_details_role[$skus][] = $data_val_arr[$key]["magento_image_role"];
            $image_alt_text[$skus][] = implode("", $data_val_arr[$key]["image_alt_text"]);
            $byn_md_id_new[$skus][] = implode("", $data_val_arr[$key]["bynder_media_id_new"]);
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
                $byn_md_id_new
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
     * @param string $bynder_media_id
     */
    public function getUpdateImage($img_json, $product_sku_key, $mg_img_role_option, $img_alt_text, $bynder_media_id)
    {

        $writer = new \Zend_Log_Writer_Stream(BP . '/var/log/AutoAddFromMagento.log');
        $logger = new \Zend_Log();
        $logger->addWriter($writer);
        $logger->info("getUpdateImage");
        $diff_image_detail = [];
        $new_image_detail = [];
        $diff_video_detail = [];
        $new_video_detail = [];
        $image = [];
        $video = [];
        $select_attribute = "image";
        $image_detail = [];
        $video_detail = [];
        try {
            
            $storeId = $this->storeManagerInterface->getStore()->getId();
            $_product = $this->_productRepository->get($product_sku_key);
            $product_ids = $_product->getId();
            $image_value = $_product->getBynderMultiImg();
            $doc_value = $_product->getBynderDocument();
            $auto_replace = $_product->getBynderAutoReplace();
            $bynder_media_ids = $bynder_media_id[$product_sku_key];
            if (!empty($image_value) && $auto_replace == null) {
                $new_image_array = explode("\n", $img_json);
                
                $new_alttext_array = explode("\n", $img_alt_text);
                $new_magento_role_option_array = $mg_img_role_option;
                $all_item_url = [];
                $item_old_value = json_decode($image_value, true);
                if (count($item_old_value) > 0) {
                    foreach ($item_old_value as $img) {
                        $all_item_url[] = $img['item_url'];
                    }
                }
                $logger->info("all_item_url => ". json_encode($all_item_url));
                foreach ($new_image_array as $vv => $new_image_value) {
                    if (trim($new_image_value) != "" && $new_image_value != "no image") {
                        $item_url = explode("?", $new_image_value);
                        $media_image_explode = explode("/", $item_url[0]);
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
                        $find_video = strpos($new_image_value, "@@");
                        
                        if (!$find_video) {
                            $logger->info("image_detail => ". $new_image_value);
                            $image_detail[] = [
                                "item_url" => $new_image_value,
                                "alt_text" => $img_altText_val,
                                "image_role" => $curt_img_role,
                                "item_type" => 'IMAGE',
                                "thum_url" => $item_url[0],
                                "bynder_md_id" => $bynder_media_ids[$vv],
                                "is_import" => 0
                            ];
                            if (!in_array($item_url[0], $all_item_url)) {
                                $logger->info("diff_image_detail => ". $new_image_value);
                                $diff_image_detail[] = [
                                    "item_url" => $new_image_value,
                                    "alt_text" => $img_altText_val,
                                    "image_role" => $curt_img_role,
                                    "item_type" => 'IMAGE',
                                    "thum_url" => $new_image_value,
                                    "bynder_md_id" => $bynder_media_ids[$vv],
                                    "is_import" => 0
                                ];
                                $data_image_data = [
                                    'sku' => $product_sku_key,
                                    'message' => $new_image_value,
                                    'media_id' => $bynder_media_ids[$vv],
                                    'data_type' => '1'
                                ];
                                $this->getInsertDataTable($data_image_data);
                                if (count($item_old_value) > 0) {
                                    foreach ($item_old_value as $kv => $img) {
                                        if ($img['item_type'] == "IMAGE") {
                                            /* here changes by me but not tested */
                                            if ($new_magento_role_option_array[$vv] != "###") {
                                                $new_mg_role_array = (array)$new_magento_role_option_array[$vv];
                                                if (count($img["image_role"])>0 && count($new_mg_role_array)>0) {
                                                    $result_val=array_diff($img["image_role"], $new_mg_role_array);
                                                    $item_old_value[$kv]["image_role"] = $result_val;
                                                }
                                            }
                                        }
                                    }
                                }
                                $total_new_value = count($diff_image_detail);
                                if ($total_new_value > 1) {
                                    foreach ($diff_image_detail as $nn => $n_img) {
                                        if ($n_img['item_type'] == "IMAGE" && $nn != ($total_new_value - 1)) {
                                            if ($new_magento_role_option_array[$vv] != "###") {
                                                $new_mg_role_array = (array)$new_magento_role_option_array[$vv];
                                                if (count($n_img["image_role"]) > 0 && count($new_mg_role_array) > 0) {
                                                    $result_val=array_diff($n_img["image_role"], $new_mg_role_array);
                                                    $diff_image_detail[$nn]["image_role"] = $result_val;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            $item_url = explode("?", $new_image_value);
                            $thum_url = explode("@@", $new_image_value);
                            $media_video_explode = explode("/", $item_url[0]);
                            $logger->info("video_detail => ". $item_url[0]);
                            $video_detail[] = [
                                "item_url" => $item_url[0],
                                "image_role" => null,
                                "item_type" => 'VIDEO',
                                "thum_url" => $thum_url[1],
                                "bynder_md_id" => $bynder_media_ids[$vv]
                            ];
                            if (!in_array($item_url[0], $all_item_url)) {
                                $logger->info("diff_video_detail => ". $item_url[0]);
                                $diff_video_detail[] = [
                                    "item_url" => $item_url[0],
                                    "image_role" => null,
                                    "item_type" => 'VIDEO',
                                    "thum_url" => $thum_url[1],
                                    "bynder_md_id" => $bynder_media_ids[$vv]
                                ];
                                $data_image_data = [
                                    'sku' => $product_sku_key,
                                    'message' => $item_url[0],
                                    'media_id' => $bynder_media_ids[$vv],
                                    'data_type' => '3'
                                ];
                                $this->getInsertDataTable($data_image_data);
                            }
                        }
                    }
                }
                $d_img_roll = "";
                if (count($diff_image_detail) > 0) {
                    foreach ($diff_image_detail as $d_img) {
                        $d_img_roll = $d_img['image_role'];
                    }
                }
                $logger->info("d_img_roll -> ". json_encode($d_img_roll));
                $i_img_roll = "";
                $image_link = "";
                if (count($image_detail) > 0) {
                    foreach ($image_detail as $img) {
                        $image[] = $img['item_url'];
                        if(!empty($img['image_role'])) {
                            $image_link = $img['item_url'];
                            $i_img_roll = $img['image_role'];
                        }
                    }
                }
                $logger->info("i_img_roll -> ". json_encode($i_img_roll));
                $logger->info("image_link -> ". $image_link);
                if (count($video_detail) > 0) {
                    foreach ($video_detail as $video) {
                        $video[] = $video['item_url'];
                    }
                }
                foreach ($item_old_value as $key1 => $img) {
                    if ($img['item_type'] == 'IMAGE') {
                        if (in_array($img['item_url'], $image)) {
                            if (!empty($d_img_roll)) {
                                $roll = [];
                            } else {
                                $roll = $image_detail[$key1]['image_role'];
                            }
                            $new_image_detail[] = [
                                "item_url" => $img['item_url'],
                                "alt_text" => $image_detail[$key1]['alt_text'],
                                "image_role" => $roll,
                                "item_type" => $img['item_type'],
                                "thum_url" => $img['thum_url'],
                                "bynder_md_id" => $img['bynder_md_id'],
                                "is_import" => $img['is_import']
                            ];
                        }
                        $total_new_value = count($new_image_detail);
                        if ($total_new_value > 1) {
                            foreach ($new_image_detail as $nn => $n_img) {
                                if ($n_img['item_type'] == "IMAGE" && $nn != ($total_new_value - 1)) {
                                    if ($new_magento_role_option_array[$key1] != "###") {
                                        $new_mg_role_array = (array)$new_magento_role_option_array[$key1];
                                        if (count($n_img["image_role"]) > 0 && count($new_mg_role_array) > 0) {
                                            $result_val=array_diff($n_img["image_role"], $new_mg_role_array);
                                            $new_image_detail[$nn]["image_role"] = $result_val;
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        if (count($video) > 0) {
                            if (in_array($img['item_url'], $video)) {
                                $new_video_detail[] = [
                                    "item_url" => $img['item_url'],
                                    "image_role" => null,
                                    "item_type" => 'VIDEO',
                                    "thum_url" => $img['thum_url'],
                                    "bynder_md_id" => $img['bynder_md_id']
                                ];
                            }
                        }
                    }
                }
                $logger->info("diff_image_detail => ". json_encode($diff_image_detail));
                $logger->info("new_image_detail => ". json_encode($new_image_detail));
                $merge_img_video = array_merge($new_image_detail, $new_video_detail);
                $merge_diff_img_video = array_merge($diff_video_detail, $diff_image_detail);
                $array_merge = array_merge($merge_img_video, $merge_diff_img_video);
                $logger->info("array_merge => ". json_encode($array_merge));
                foreach ($array_merge as $img) {
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
                $new_value_array = json_encode($array_merge, true);
                $updated_values = [
                    'bynder_multi_img' => $new_value_array,
                    'bynder_isMain' => $flag,
                    'bynder_auto_replace' => 1
                ];
                
                $this->action->updateAttributes(
                    [$product_ids],
                    $updated_values,
                    $storeId
                );
            }
        } catch (Exception $e) {
            $logger->info("error => ". $e->getMessage());
            $data_image_data = [
                'sku' => $product_sku_key,
                'message' => $e->getMessage(),
                'media_id' => '',
                'data_type' => ''
            ];
            $this->getInsertDataTable($data_image_data);
        }
    }
}
