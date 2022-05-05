/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import TopBar from '.~/components/stepper/top-bar';
import HelpIconButton from '.~/components/help-icon-button';

/**
 * Triggered on events during ads setup and editing
 *
 * @event gla_setup_ads
 * @property {string} target Button ID
 * @property {string} trigger Action (e.g. `click`)
 */

/**
 * @fires gla_setup_ads with given `{ target: 'back', trigger: 'click' }` when back button is clicked.
 */
const SetupAdsTopBar = () => {
	// We record the intent to go back or to help - clicking buttons.
	// Those events are fired before the actual navigation happens.
	// The navigation itself may or maynot be blocked, for example to avoid leaving unsaved chanes.
	const handleBackButtonClick = () => {
		recordEvent( 'gla_setup_ads', { target: 'back', trigger: 'click' } );
	};

	return (
		<TopBar
			title={ __( 'Set up paid campaign', 'google-listings-and-ads' ) }
			helpButton={ <HelpIconButton eventContext="setup-ads" /> }
			backHref={ getNewPath( {}, '/google/dashboard' ) }
			onBackButtonClick={ handleBackButtonClick }
		/>
	);
};

export default SetupAdsTopBar;
