/**
 * External dependencies
 */
import { __, sprintf } from '@wordpress/i18n';

/**
 * Add an i18n "Account " prefix for account ID
 *
 * @param  {string|number} id The account ID to be decorated
 * @return {string} Decorated account ID text
 */
export default function toAccountText( id ) {
	// translators: %s: user's account ID
	return sprintf( __( 'Account %s', 'google-listings-and-ads' ), id );
}
