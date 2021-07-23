/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * @typedef {import('.~/data/selectors').JetpackAccount} JetpackAccount
 */
/**
 * Get a connected jetpack information for display on UI.
 * This function should always be called after the jetpack is connected.
 *
 * @param {JetpackAccount} jetpack Connected jetpack account data.
 * @return {string} Return email if current user is jetpack owner.
 *                  Otherwise, return a successfully connected info only.
 */
export default function getConnectedJetpackInfo( jetpack ) {
	// Error-proofing for calling with not yet connect jetpack.
	if ( jetpack.active !== 'yes' ) {
		return '';
	}

	if ( jetpack.owner === 'yes' ) {
		return jetpack.email;
	}

	// Show a connected text instead of owner email to non jetpack owner users.
	return __(
		'Successfully connected through Jetpack',
		'google-listings-and-ads'
	);
}
