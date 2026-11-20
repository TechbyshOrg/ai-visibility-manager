<?php
/**
 * Weekly or monthly AI visibility summary email.
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
 * Weekly or monthly AI visibility summary email.
 *
 * @since      2.0.0
 * @package    Tavc
 * @subpackage Tavc/includes
 * @author     Techbysh
 */
class TAVC_Digest {

	/**
	 * Send the summary of the last week or month, as configured.
	 *
	 * @since    2.0.0
	 * @param    string    $recipient    Optional address; defaults to the configured recipient.
	 * @return   bool                    Whether wp_mail() accepted the message.
	 */
	public static function send( $recipient = '' ) {
		$recipient = is_email( $recipient ) ? $recipient : TAVC_Settings::get_digest_recipient();
		if ( ! is_email( $recipient ) ) {
			return false;
		}

		$settings = TAVC_Settings::get_tracking_settings();
		$message  = self::build_message( $settings['digest_frequency'] );

		/**
		 * Filters the summary email before it is sent.
		 *
		 * @since 2.0.0
		 *
		 * @param array  $message   array( 'subject' => string, 'body' => string ).
		 * @param string $recipient Recipient address.
		 */
		$message = apply_filters( 'tavc_digest_email', $message, $recipient );

		if ( ! is_array( $message ) || empty( $message['subject'] ) || ! isset( $message['body'] ) ) {
			return false;
		}

		return (bool) wp_mail( $recipient, $message['subject'], $message['body'] );
	}

	/**
	 * Build the plain-text summary: the 7 full days before today (weekly), or
	 * the previous calendar month (monthly), compared with the period before.
	 *
	 * @since    2.0.0
	 * @param    string    $frequency    'weekly' or 'monthly'.
	 * @return   array                   array( 'subject' => string, 'body' => string ).
	 */
	public static function build_message( $frequency = 'weekly' ) {
		$monthly  = 'monthly' === $frequency;
		$timezone = wp_timezone();

		if ( $monthly ) {
			$today    = new DateTimeImmutable( 'first day of this month 00:00', $timezone );
			$start    = $today->modify( 'first day of last month' );
			$previous = $start->modify( 'first day of last month' );
		} else {
			$today    = new DateTimeImmutable( 'today', $timezone );
			$start    = $today->modify( '-7 days' );
			$previous = $today->modify( '-14 days' );
		}

		$start_sql    = $start->format( 'Y-m-d H:i:s' );
		$end_sql      = $today->format( 'Y-m-d H:i:s' );
		$previous_sql = $previous->format( 'Y-m-d H:i:s' );

		$visits          = TAVC_DB::get_referral_count_between( $start_sql, $end_sql );
		$previous_visits = TAVC_DB::get_referral_count_between( $previous_sql, $start_sql );
		$sources         = TAVC_DB::get_top_referral_sources( 10, $start_sql, $end_sql );
		$pages           = TAVC_DB::get_top_target_pages( 5, $start_sql, $end_sql );
		$crawler_hits    = TAVC_DB::get_crawler_hits_between( $start->format( 'Y-m-d' ), $today->format( 'Y-m-d' ) );
		$crawlers        = TAVC_DB::get_crawler_summary( $start->format( 'Y-m-d' ), $today->format( 'Y-m-d' ) );
		$definitions     = TAVC_Bots::get_definitions();

		$site_name  = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
		$date_range = sprintf(
			/* translators: 1: start date, 2: end date. */
			__( '%1$s to %2$s', 'tbsh-ai-visibility-control' ),
			wp_date( get_option( 'date_format' ), $start->getTimestamp() ),
			wp_date( get_option( 'date_format' ), $today->modify( '-1 day' )->getTimestamp() )
		);

		$lines   = array();
		$lines[] = sprintf(
			/* translators: 1: site name, 2: date range. */
			__( 'AI visibility summary for %1$s, %2$s', 'tbsh-ai-visibility-control' ),
			$site_name,
			$date_range
		);
		$lines[] = '';
		$lines[] = sprintf(
			/* translators: 1: number of visits, 2: change compared with the week before. */
			__( 'AI referral visits: %1$s (%2$s)', 'tbsh-ai-visibility-control' ),
			number_format_i18n( $visits ),
			self::describe_change( $visits, $previous_visits, $monthly )
		);

		if ( ! empty( $sources ) ) {
			$lines[] = '';
			$lines[] = __( 'Visits by AI platform:', 'tbsh-ai-visibility-control' );
			foreach ( $sources as $source ) {
				$lines[] = '  - ' . $source->referrer . ': ' . number_format_i18n( $source->count );
			}
		}

		if ( ! empty( $pages ) ) {
			$lines[] = '';
			$lines[] = __( 'Most visited pages from AI:', 'tbsh-ai-visibility-control' );
			foreach ( $pages as $page ) {
				$lines[] = '  - ' . $page->target_url . ': ' . number_format_i18n( $page->count );
			}
		}

		$lines[] = '';
		$lines[] = sprintf(
			/* translators: %s: number of requests. */
			__( 'AI crawler requests: %s', 'tbsh-ai-visibility-control' ),
			number_format_i18n( $crawler_hits )
		);

		if ( ! empty( $crawlers ) ) {
			foreach ( $crawlers as $slug => $crawler ) {
				$name    = isset( $definitions[ $slug ] ) ? $definitions[ $slug ]['name'] : $slug;
				$line    = '  - ' . $name . ': ' . number_format_i18n( $crawler->hits );
				$refused = $crawler->blocked + $crawler->limited;
				if ( $refused > 0 ) {
					$line .= ' ' . sprintf(
						/* translators: %s: number of refused requests. */
						__( '(%s refused)', 'tbsh-ai-visibility-control' ),
						number_format_i18n( $refused )
					);
				}
				$lines[] = $line;
			}
		}

		$lines[] = '';
		$lines[] = __( 'Full report:', 'tbsh-ai-visibility-control' ) . ' ' . admin_url( 'admin.php?page=tbsh-ai-visibility-control&tab=referrals' );
		$lines[] = __( 'To stop these emails, turn off the summary email under AI Visibility > Settings & Tools.', 'tbsh-ai-visibility-control' );

		return array(
			'subject' => sprintf(
				$monthly
					/* translators: %s: site name. */
					? __( '[%s] Monthly AI visibility summary', 'tbsh-ai-visibility-control' )
					/* translators: %s: site name. */
					: __( '[%s] Weekly AI visibility summary', 'tbsh-ai-visibility-control' ),
				$site_name
			),
			'body'    => implode( "\n", $lines ),
		);
	}

	/**
	 * Describe the change between two counts.
	 *
	 * @since    2.0.0
	 * @param    int     $current     This period.
	 * @param    int     $previous    Previous period.
	 * @param    bool    $monthly     Whether the periods are months.
	 * @return   string
	 */
	private static function describe_change( $current, $previous, $monthly = false ) {
		if ( $previous <= 0 ) {
			if ( $current <= 0 ) {
				return __( 'no change', 'tbsh-ai-visibility-control' );
			}

			return $monthly ? __( 'no visits the month before', 'tbsh-ai-visibility-control' ) : __( 'no visits the week before', 'tbsh-ai-visibility-control' );
		}

		$percent = round( ( ( $current - $previous ) / $previous ) * 100 );

		if ( 0.0 === (float) $percent ) {
			return $monthly ? __( 'no change from the month before', 'tbsh-ai-visibility-control' ) : __( 'no change from the week before', 'tbsh-ai-visibility-control' );
		}

		$signed = ( $percent > 0 ? '+' : '' ) . number_format_i18n( $percent ) . '%';

		return $monthly
			/* translators: %s: signed percentage, e.g. +12% or -5%. */
			? sprintf( __( '%s compared with the month before', 'tbsh-ai-visibility-control' ), $signed )
			/* translators: %s: signed percentage, e.g. +12% or -5%. */
			: sprintf( __( '%s compared with the week before', 'tbsh-ai-visibility-control' ), $signed );
	}
}
