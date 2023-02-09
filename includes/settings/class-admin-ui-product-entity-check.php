<?php

namespace Ainsys\Connector\Woocommerce\Settings;

use Ainsys\Connector\Master\Hooked;
use Ainsys\Connector\Master\Logger;
use Ainsys\Connector\Master\Settings\Settings;
use Ainsys\Connector\Woocommerce\WP\Process_Products;
use Ainsys\Connector\Master\Settings\Admin_UI_Entities_Checking;

class Admin_Ui_Product_Entity_Check implements Hooked {

	protected $process;
	protected $entities_checking;

	static public $entity = 'product';

	public function init_hooks() {

		$this->process = new Process_Products();
		$this->entities_checking = new Admin_UI_Entities_Checking();

		/**
		 * Check entity connection for products
		 */
		add_filter( 'ainsys_before_check_connection_make_request', function () {
			return true;
		} );
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

		$this->entities_checking->make_request = false;
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

	/**
	 * @param array $result_test
	 * @param $result_entity
	 * @param $entity
	 *
	 * @return mixed
	 */
	protected function get_result_entity( array $result_test, $result_entity, $entity ) {

		if ( ! empty( $result_test['request'] ) ) {
			$result_request = $result_test['request'];
		} else {
			$result_request = 'Error: Data transfer is disabled. Check the Entities export settings tab';
		}

		if ( ! empty( $result_test['response'] ) ) {
			$result_response = $result_test['response'];
		} else {
			$result_response = __( 'Error: Data transfer is disabled. Check the Entities export settings tab', AINSYS_CONNECTOR_TEXTDOMAIN );
		}

		$result_entity[ $entity ] = [
			'request'        => $result_request,
			'response'       => $result_response,
			'short_request'  => mb_substr( Logger::convert_response( $result_request ), 0, 40 ) . ' ... ',
			'full_request'   => Logger::convert_response( $result_request ),
			'short_response' => mb_substr( Logger::convert_response( $result_response ), 0, 40 ) . ' ... ',
			'full_response'  => Logger::convert_response( $result_response ),
			'time'           => current_time( 'mysql' ),
			'status'         => false === strpos( $result_response, 'Error:' ),
		];

		Settings::set_option( 'check_connection_entity', $result_entity );

		return $result_entity;
	}

}