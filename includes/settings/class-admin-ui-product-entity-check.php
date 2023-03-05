<?php

namespace Ainsys\Connector\Woocommerce\Settings;

use Ainsys\Connector\Master\Hooked;
use Ainsys\Connector\Master\Settings\Settings;
use Ainsys\Connector\Woocommerce\WP\Process_Products;
use Ainsys\Connector\Master\Settings\Admin_UI_Entities_Checking;

class Admin_Ui_Product_Entity_Check implements Hooked {

	protected $process;

	static public $entity = 'product';

	public function init_hooks() {

		$this->process = new Process_Products();

		/**
		 * Check entity connection for products
		 */
		add_filter( 'ainsys_check_connection_request', [ $this, 'check_product_entity' ], 15, 3 );

	}

	/**
	 * @param $result_entity
	 * @param $entity
	 * @param $make_request
	 *
	 * @return mixed
	 * Check "product" entity filter callback
	 */
	public function check_product_entity( $result_entity, $entity, Admin_UI_Entities_Checking $entities_checking) {

		if ( $entity !== self::$entity ) {
			return $result_entity;
		}

		$entities_checking->make_request = false;
		$result_test   = $this->get_product();
		$result_entity = Settings::get_option( 'check_connection_entity' );

		return $entities_checking->get_result_entity($result_test, $result_entity, $entity);

	}

	/**
	 * @return array|false
	 *
	 * Get product data for AINSYS
	 *
	 */

	private function get_product() {

		$args = array(
			'limit' => 1,
		);

		$products = wc_get_products( $args );

		if ( ! empty( $products ) ) {

			$product    = end( $products );
			$product_id = $product->get_id();

			return $this->process->process_checking( $product_id, $product, true );

		} else {
			return false;
		}

	}

}