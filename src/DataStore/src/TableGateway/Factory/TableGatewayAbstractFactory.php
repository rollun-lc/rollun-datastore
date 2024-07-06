<?php
/**
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license LICENSE.md New BSD License
 */

namespace rollun\datastore\TableGateway\Factory;

use Interop\Container\ContainerInterface;
use rollun\datastore\AbstractFactoryAbstract;
use Zend\Db\Adapter\Adapter;
use Zend\Db\Metadata\Metadata;
use Zend\Db\TableGateway\TableGateway;

/**
 * Create and return an instance of the TableGateway
 * This Factory depends on Container (which should return an 'config' as array)
 *
 * The configuration can contain:
 * <code>
 *  'tableGateway' => [
 *      'sql' => 'Zend\Db\Sql\Sql', // optional
 *      'adapter' => 'db' // optional,
 *  ],
 * </code>
 *
 * Class TableGatewayAbstractFactory
 * @package rollun\datastore\TableGateway\Factory
 */
class TableGatewayAbstractFactory extends AbstractFactoryAbstract
{
    const KEY_SQL = 'sql';

    const KEY_TABLE_GATEWAY = 'tableGateway';

    const KEY_ADAPTER = 'adapter';

    /**
     * @var null|array
     */
    protected $tableNames = null;

    /**
     * @var Adapter
     */
    protected $db;

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @return bool
     */
    public function canCreate(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config');

        if (!isset($config[self::KEY_TABLE_GATEWAY][$requestedName])) {
            return false;
        }

        if ($this->setDbAdapter($container, $requestedName)) {
            $this->tableNames = array (
                0 => 'air_stop_words',
                1 => 'amazon_catalog_composition',
                2 => 'amazon_inventory_cache',
                3 => 'amazon_msin_rid_check',
                4 => 'amazon_msin_rid_check_data',
                5 => 'amazon_products_cross_match',
                6 => 'autodist_all_products_update_ftp_cache',
                7 => 'autodist_cache',
                8 => 'autodist_inventory_ftp_cache',
                9 => 'autodist_mpn_list',
                10 => 'autodist_price_update_ftp_cache',
                11 => 'brand_aliases_statistic',
                12 => 'brands_analysis',
                13 => 'catalog_brands_mapping',
                14 => 'catalog_brands_mapping_history',
                15 => 'compatible_vehicles_development',
                16 => 'compatibles_from_ebay',
                17 => 'config_provider_delegate',
                18 => 'constants',
                19 => 'create_catalog_items_by_supplier_csn',
                20 => 'damaged_inventory',
                21 => 'ebay_candidate',
                22 => 'ebay_candidate_mpn_list',
                23 => 'ebay_catalog_mpn_list',
                24 => 'ebay_compatible_vehicles',
                25 => 'ebay_compatible_vehicles_tmp',
                26 => 'ebay_standard_vehicles',
                27 => 'featured_images',
                28 => 'history_provider_cache_dimension_store',
                29 => 'history_provider_cache_dimension_store_tmp',
                30 => 'htl_comparison',
                31 => 'items_dynamic_plaisir',
                32 => 'items_dynamic_rollun',
                33 => 'loader_marker_info',
                34 => 'logs',
                35 => 'map_price_all_balls_mpn_list',
                36 => 'map_rules',
                37 => 'merging_brands_action_history',
                38 => 'merging_brands_black_list',
                39 => 'parts_unlimited_cache',
                40 => 'parts_unlimited_compatible_vehicles',
                41 => 'parts_unlimited_mpn_list',
                42 => 'parts_unlimited_web_base_price_file_cache',
                43 => 'parts_unlimited_web_rol045_price_file_cache',
                44 => 'provider_cache_dimension_store',
                45 => 'provider_cache_ebay_compatibles',
                46 => 'provider_cache_ebay_dev_plaisir_compatibles',
                47 => 'provider_cache_ebay_inventory',
                48 => 'provider_cache_ebay_plaisir_inventory',
                49 => 'provider_cache_ebay_rollun_inventory',
                50 => 'provider_cache_items_description_store',
                51 => 'provider_cache_map_price_store',
                52 => 'provider_cache_tft_amazon',
                53 => 'purchase_statistics',
                54 => 'queue_ImagesUploadSystemQueue_5_5',
                55 => 'queue_autodistAllProductsUpdateCUSys_Queue_5_1000',
                56 => 'queue_changes_queue_5_5',
                57 => 'queue_images_queue_5_5',
                58 => 'queue_images_queue_600_5',
                59 => 'queue_images_queue_60_5',
                60 => 'queue_rollunOfficeInfoUpdateCUSys_Queue_5_1000',
                61 => 'queue_rollunOfficeInvUpdateCUSys_Queue_5_1000',
                62 => 'queue_rollunOfficeStorageUpdateCUSys_Queue_5_1000',
                63 => 'queue_rollunOfficeUpdateCUSys_Queue_5_1000',
                64 => 'queue_sltUpdateCUSys_Queue_5_1000',
                65 => 'queue_turnAllDataCUSys_Queue_5_1000',
                66 => 'queue_turnInventoryCUSys_Queue_5_1000',
                67 => 'queue_turnItemsCUSys_Queue_5_1000',
                68 => 'queue_turnPricingCUSys_Queue_5_1000',
                69 => 'queue_wpsInvCUSys_Queue_5_1000',
                70 => 'queue_wpsItemsCUSys_Queue_5_1000',
                71 => 'queue_wpsMasterCUSys_Queue_5_1000',
                72 => 'queue_wpsUpdateCUSys_Queue_5_1000',
                73 => 'restricted_brands',
                74 => 'rocky_kits_info',
                75 => 'rocky_mountain_cache',
                76 => 'rocky_mountain_compatible_vehicles',
                77 => 'rocky_mountain_dropship_fee_ftp_cache',
                78 => 'rocky_mountain_ftp_cache',
                79 => 'rocky_mountain_mpn_list',
                80 => 'rocky_mountain_usps_priority_mail_available',
                81 => 'rollun_office_cache',
                82 => 'rollun_office_ftp_cache',
                83 => 'rollun_office_info_cache',
                84 => 'rollun_office_inventory_ftp_cache',
                85 => 'rollun_office_storage_cache',
                86 => 'rollun_product_catalog',
                87 => 'rollun_product_catalog_analogue',
                88 => 'rollun_product_catalog_history',
                89 => 'sales_tax',
                90 => 'slots',
                91 => 'slt_cache',
                92 => 'slt_ftp_cache',
                93 => 'slt_mpn_list',
                94 => 'sr_warehouses',
                95 => 'stop_words',
                96 => 'supplier_mapping_black_list',
                97 => 'supplier_mapping_problem',
                98 => 'supplier_rollun_product_mapping',
                99 => 'supplier_rollun_product_mapping_history',
                100 => 'suppliers',
                101 => 'suppliers_brands',
                102 => 'suppliers_brands_duplicates',
                103 => 'tucker_cache',
                104 => 'tucker_discount_filter_rules',
                105 => 'tucker_ftp_cache',
                106 => 'tucker_item_master_ftp_cache',
                107 => 'tucker_mpn_list',
                108 => 'tucker_mpn_list_temp',
                109 => 'tucker_price_list_ftp_cache',
                110 => 'tucker_rocky_compatible_vehicles',
                111 => 'tucker_rocky_dealer_products',
                112 => 'turn_all_data_ftp_cache',
                113 => 'turn_cache',
                114 => 'turn_inventory_ftp_cache',
                115 => 'turn_items_ftp_cache',
                116 => 'turn_mpn_list',
                117 => 'turn_pricing_ftp_cache',
                118 => 'unknown_brands',
                119 => 'walmart_inventory_cache',
                120 => 'wps_cache',
                121 => 'wps_cache_old',
                122 => 'wps_dropship_fee',
                123 => 'wps_inventory_ftp_cache',
                124 => 'wps_inventory_ftp_cache_old',
                125 => 'wps_items_ftp_cache',
                126 => 'wps_master_item_ftp_cache',
                127 => 'wps_mpn_list',
                128 => 'wps_price_ftp_cache_old',
                129 => 'amazon_products_cross_match_user_info_view',
                130 => 'autodist_cache_update_statistic_info_view',
                131 => 'autodist_cache_update_statistic_view',
                132 => 'autodist_inventory_ftp_cache_all_view',
                133 => 'compatible_vehicles_logs',
                134 => 'ebay_compatible_vehicles_all_view',
                135 => 'item_info_with_supplier_info_view',
                136 => 'marketplaces_inventory_view',
                137 => 'parts_unlimited_cache_update_statistic_info_view',
                138 => 'parts_unlimited_cache_update_statistic_view',
                139 => 'parts_unlimited_web_base_price_file_cache_all_view',
                140 => 'provider_cache_dimension_store_view',
                141 => 'provider_cache_ebay_compatibles_inventory_view',
                142 => 'provider_cache_items_description_store_view',
                143 => 'rocky_mountain_cache_update_statistic_info_view',
                144 => 'rocky_mountain_cache_update_statistic_view',
                145 => 'rocky_mountain_ftp_cache_all_view',
                146 => 'rollun_id_with_csn',
                147 => 'sr_mapping_not_deleted',
                148 => 'sr_warehouses_by_speed',
                149 => 'tucker_cache_update_statistic_info_view',
                150 => 'tucker_cache_update_statistic_view',
                151 => 'tucker_ftp_cache_all_view',
                152 => 'turn_cache_update_statistic_view',
                153 => 'view_amazon_catalog_composition_with_rollun_id_without_mapping',
                154 => 'view_barcode_catalog',
                155 => 'view_map_price_all_balls_mpn_list_with_rid',
                156 => 'view_rollun_office_mpn_list',
                157 => 'view_rollun_office_mpn_list_v2',
                158 => 'view_rollun_product_catalog_without_mapping',
                159 => 'view_rollun_product_dimensions',
                160 => 'view_rollun_union_supplier_info',
                161 => 'view_rollun_union_supplier_info_new',
                162 => 'wps_cache_update_statistic_view',
            );
        }

        return is_array($this->tableNames) && in_array($requestedName, $this->tableNames, true);
    }

    /**
     *
     * @param ContainerInterface $container
     * @param $requestedName
     * @return bool
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function setDbAdapter(ContainerInterface $container, $requestedName)
    {
        $config = $container->get('config')[self::KEY_TABLE_GATEWAY];

        if (isset($config[$requestedName]) && isset($config[$requestedName][static::KEY_ADAPTER])) {
            $this->db = $container->has($config[$requestedName][static::KEY_ADAPTER])
                ? $container->get($config[$requestedName][static::KEY_ADAPTER])
                : false;
        } else {
            $this->db = $container->has('db') ? $container->get('db') : false;
        }

        return (bool)$this->db;
    }

    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param array|null $options
     * @return \rollun\datastore\DataStore\Interfaces\DataStoresInterface|TableGateway
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('config')[self::KEY_TABLE_GATEWAY][$requestedName];

        if (isset($config[self::KEY_SQL]) && is_a($config[self::KEY_SQL], 'Zend\Db\Sql\Sql', true)) {
            $sql = new $config[self::KEY_SQL]($this->db, $requestedName);

            return new TableGateway($requestedName, $this->db, null, null, $sql);
        }

        return new TableGateway($requestedName, $this->db);
    }
}
