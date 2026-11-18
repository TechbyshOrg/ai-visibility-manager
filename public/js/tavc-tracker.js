/**
 * AI Visibility Control - cache-compatible AI referral tracking.
 *
 * Runs only when "Cache-compatible referral tracking" is enabled. Sends a
 * single request, and only when the visitor arrived from a known AI platform.
 * No cookies are set and nothing is stored in the browser.
 */
( function () {
	'use strict';

	var config = window.tavcTracker;
	if ( ! config || ! config.endpoint || ! window.URL ) {
		return;
	}

	var domains = config.domains || [];
	var utmSources = config.utmSources || [];
	var referrer = document.referrer || '';
	var pageUrl = window.location.href.split( '#' )[ 0 ];
	var matched = false;

	function hostMatches( host, domain ) {
		return host === domain || host.slice( -( domain.length + 1 ) ) === '.' + domain;
	}

	if ( referrer ) {
		try {
			var host = new URL( referrer ).hostname.toLowerCase();
			for ( var i = 0; i < domains.length; i++ ) {
				if ( hostMatches( host, domains[ i ] ) ) {
					matched = true;
					break;
				}
			}
		} catch ( e ) {
			matched = false;
		}
	}

	if ( ! matched && utmSources.length ) {
		try {
			var utm = ( new URL( pageUrl ).searchParams.get( 'utm_source' ) || '' ).toLowerCase().trim();
			matched = utm !== '' && utmSources.indexOf( utm ) !== -1;
		} catch ( e ) {
			matched = false;
		}
	}

	if ( ! matched ) {
		return;
	}

	var data = new FormData();
	data.append( 'url', pageUrl );
	data.append( 'ref', referrer );

	if ( navigator.sendBeacon ) {
		navigator.sendBeacon( config.endpoint, data );
	} else if ( window.fetch ) {
		window.fetch( config.endpoint, {
			method: 'POST',
			body: data,
			credentials: 'omit',
			keepalive: true
		} );
	}
}() );
