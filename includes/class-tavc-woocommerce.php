<?php
/**
 * WooCommerce AI attribution.
 *
 * @link       https://techbysh.com
 * @since      2.0.0
 *
 * @package    Tavc
 * @subpackage Tavc/includes
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce AI attribution.
 *
 * Reads the visit source that WooCommerce Order Attribution (WooCommerce 8.5+)
 * records at checkout, and flags orders whose source is an AI platform. Works
 * with both the classic and the block checkout, and with HPOS.
 *
 * @since      2.0.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_WooCommerce {

	/**
	 * Order meta key holding the AI platform name.
	 */
	const ORDER_META_KEY = '_tavc_ai_source';

	/**
	 * Maximum number of orders included in one report.
	 */
	const REPORT_ORDER_LIMIT = 2000;

	/**
	 * Rows shown in the top products and top landing pages lists.
	 */
	const REPORT_TOP_ROWS = 5;

	/**
	 * Order meta where WooCommerce Order Attribution stores the first page of the visit.
	 */
	const SESSION_ENTRY_META_KEY = '_wc_order_attribution_session_entry';

	/**
	 * Whether WooCommerce is active and new enough for order reports.
	 *
	 * @since    2.0.0
	 * @return   bool
	 */
	public static function is_available() {
		return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_orders' );
	}

	/**
	 * Flag an order whose attribution data points to an AI platform.
	 *
	 * Hooked to woocommerce_order_save_attribution_data, which WooCommerce fires
	 * from both checkouts with the unprefixed attribution fields.
	 *
	 * @since    2.0.0
	 * @param    WC_Order    $order    Order.
	 * @param    array       $data     Attribution data (source_type, referrer, utm_source, ...).
	 */
	public function save_order_ai_source( $order, $data ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'update_meta_data' ) || ! is_array( $data ) ) {
			return;
		}

		$source = self::detect_source( $data );
		if ( ! $source ) {
			return;
		}

		$order->update_meta_data( self::ORDER_META_KEY, $source );
		$order->save();

		self::clear_report_cache();
	}

	/**
	 * AI platform from attribution data.
	 *
	 * @since    2.0.0
	 * @param    array    $data    Attribution data.
	 * @return   string|false
	 */
	public static function detect_source( $data ) {
		$referrer = isset( $data['referrer'] ) && is_string( $data['referrer'] ) ? esc_url_raw( $data['referrer'] ) : '';
		$source   = TAVC_AI_Sources::detect_from_referrer( $referrer );

		if ( ! $source && isset( $data['utm_source'] ) && is_string( $data['utm_source'] ) ) {
			$source = TAVC_AI_Sources::detect_from_utm( sanitize_text_field( $data['utm_source'] ) );
		}

		/**
		 * Filters the AI platform detected for a WooCommerce order.
		 *
		 * @since 2.0.0
		 *
		 * @param string|false $source AI platform name, or false.
		 * @param array        $data   WooCommerce attribution data.
		 */
		return apply_filters( 'tavc_woocommerce_order_ai_source', $source, $data );
	}

	/**
	 * Show the AI source on the order edit screen.
	 *
	 * @since    2.0.0
	 * @param    WC_Order    $order    Order.
	 */
	public function render_order_ai_source( $order ) {
		if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
			return;
		}

		$source = $order->get_meta( self::ORDER_META_KEY );
		if ( empty( $source ) ) {
			return;
		}

		printf(
			'<p class="form-field form-field-wide"><strong>%1$s</strong> %2$s</p>',
			esc_html__( 'AI referral source:', 'tbsh-ai-visibility-control' ),
			esc_html( $source )
		);
	}

	/**
	 * Paid orders from AI platforms.
	 *
	 * @since    2.0.0
	 * @param    int    $since_timestamp    Unix timestamp (0 = all time).
	 * @return   array {
	 *     @type array $rows          Per platform and currency: 'source', 'currency', 'orders',
	 *                                'revenue' (after refunds) and 'average' (revenue per order).
	 *     @type array $products      Top products: 'name', 'url', 'quantity', 'orders'.
	 *     @type array $landing_pages Top first pages of the visit: 'path', 'orders'.
	 *     @type bool  $limited       Whether the order limit was reached.
	 * }
	 */
	public static function get_report( $since_timestamp ) {
		$empty = array(
			'rows'          => array(),
			'products'      => array(),
			'landing_pages' => array(),
			'limited'       => false,
		);

		if ( ! self::is_available() ) {
			return $empty;
		}

		$since_timestamp = absint( $since_timestamp );
		$cache_key       = 'tavc_wc_report_' . ( $since_timestamp ? gmdate( 'Ymd', $since_timestamp ) : 'all' );
		$cached          = get_transient( $cache_key );

		// Reports cached in an older format lack these keys: build them again.
		if ( is_array( $cached ) && isset( $cached['rows'], $cached['products'], $cached['landing_pages'] ) ) {
			return $cached;
		}

		$args = array(
			'limit'      => self::REPORT_ORDER_LIMIT,
			'status'     => wc_get_is_paid_statuses(),
			'type'       => 'shop_order',
			'orderby'    => 'date',
			'order'      => 'DESC',
			'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_query
				array(
					'key'     => self::ORDER_META_KEY,
					'compare' => 'EXISTS',
				),
			),
		);
		if ( $since_timestamp ) {
			$args['date_created'] = '>=' . $since_timestamp;
		}

		$orders   = wc_get_orders( $args );
		$rows     = array();
		$products = array();
		$landing  = array();

		foreach ( (array) $orders as $order ) {
			if ( ! is_object( $order ) || ! method_exists( $order, 'get_meta' ) ) {
				continue;
			}

			self::add_order_products( $order, $products );

			$entry_path = self::get_entry_path( (string) $order->get_meta( self::SESSION_ENTRY_META_KEY ) );
			if ( '' !== $entry_path ) {
				$landing[ $entry_path ] = isset( $landing[ $entry_path ] ) ? $landing[ $entry_path ] + 1 : 1;
			}

			$source   = (string) $order->get_meta( self::ORDER_META_KEY );
			$currency = (string) $order->get_currency();
			$key      = $source . '|' . $currency;

			if ( ! isset( $rows[ $key ] ) ) {
				$rows[ $key ] = array(
					'source'   => $source,
					'currency' => $currency,
					'orders'   => 0,
					'revenue'  => 0.0,
				);
			}

			$rows[ $key ]['orders']++;
			$rows[ $key ]['revenue'] += (float) $order->get_total() - (float) $order->get_total_refunded();
		}

		usort(
			$rows,
			function ( $a, $b ) {
				return $b['revenue'] <=> $a['revenue'];
			}
		);

		foreach ( $rows as $key => $row ) {
			$rows[ $key ]['average'] = $row['orders'] > 0 ? $row['revenue'] / $row['orders'] : 0.0;
		}

		uasort(
			$products,
			function ( $a, $b ) {
				return array( $b['quantity'], $b['orders'] ) <=> array( $a['quantity'], $a['orders'] );
			}
		);

		arsort( $landing );
		$landing_pages = array();
		foreach ( array_slice( $landing, 0, self::REPORT_TOP_ROWS, true ) as $path => $count ) {
			$landing_pages[] = array(
				'path'   => (string) $path,
				'orders' => $count,
			);
		}

		$report = array(
			'rows'          => array_values( $rows ),
			'products'      => array_values( array_slice( $products, 0, self::REPORT_TOP_ROWS, true ) ),
			'landing_pages' => $landing_pages,
			'limited'       => count( (array) $orders ) >= self::REPORT_ORDER_LIMIT,
		);

		set_transient( $cache_key, $report, 15 * MINUTE_IN_SECONDS );

		return $report;
	}

	/**
	 * Count the products of one order. Variations are counted under their
	 * parent product.
	 *
	 * @since    2.0.0
	 * @param    WC_Order    $order       Order.
	 * @param    array       $products    Product ID => totals, by reference.
	 */
	private static function add_order_products( $order, &$products ) {
		if ( ! method_exists( $order, 'get_items' ) ) {
			return;
		}

		$seen = array();
		foreach ( $order->get_items() as $item ) {
			if ( ! is_object( $item ) || ! method_exists( $item, 'get_product_id' ) ) {
				continue;
			}

			$product_id = (int) $item->get_product_id();
			if ( $product_id <= 0 ) {
				continue;
			}

			if ( ! isset( $products[ $product_id ] ) ) {
				$title = get_the_title( $product_id );

				$products[ $product_id ] = array(
					'name'     => '' !== $title ? $title : (string) $item->get_name(),
					'url'      => 'publish' === get_post_status( $product_id ) ? (string) get_permalink( $product_id ) : '',
					'quantity' => 0,
					'orders'   => 0,
				);
			}

			$products[ $product_id ]['quantity'] += max( 0, (int) $item->get_quantity() );
			if ( ! isset( $seen[ $product_id ] ) ) {
				$products[ $product_id ]['orders']++;
				$seen[ $product_id ] = true;
			}
		}
	}

	/**
	 * Path of the first page of the visit, relative to the site, without query string.
	 *
	 * @since    2.0.0
	 * @param    string    $url    Entry URL recorded by WooCommerce.
	 * @return   string            For example "/blog/best-hoodies/", or '' when unknown.
	 */
	public static function get_entry_path( $url ) {
		$url = trim( $url );
		if ( '' === $url || '(none)' === $url ) {
			return '';
		}

		$path = wp_parse_url( $url, PHP_URL_PATH );
		if ( ! is_string( $path ) || '' === $path ) {
			$path = '/';
		}

		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_path = is_string( $home_path ) ? untrailingslashit( $home_path ) : '';
		if ( '' !== $home_path && 0 === strpos( $path, $home_path . '/' ) ) {
			$path = substr( $path, strlen( $home_path ) );
		}

		return substr( $path, 0, 255 );
	}

	/**
	 * Remove cached reports after a new AI order.
	 *
	 * @since    2.0.0
	 */
	public static function clear_report_cache() {
		foreach ( self::get_report_cache_keys() as $key ) {
			delete_transient( $key );
		}
	}

	/**
	 * Cache keys used by the referral report ranges.
	 *
	 * @since    2.0.0
	 * @return   array
	 */
	public static function get_report_cache_keys() {
		$keys = array( 'tavc_wc_report_all' );
		foreach ( array( 7, 30, 90 ) as $days ) {
			$keys[] = 'tavc_wc_report_' . gmdate( 'Ymd', self::get_range_start_timestamp( $days ) );
		}

		return $keys;
	}

	/**
	 * Start of a report range: midnight (site time) N-1 days ago.
	 *
	 * @since    2.0.0
	 * @param    int    $days    Range length in days.
	 * @return   int             Unix timestamp.
	 */
	public static function get_range_start_timestamp( $days ) {
		$start = new DateTimeImmutable( 'today', wp_timezone() );

		return $start->modify( '-' . ( absint( $days ) - 1 ) . ' days' )->getTimestamp();
	}
}
