/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import TopBar from '.~/components/stepper/top-bar';
import HelpIconButton from '.~/components/help-icon-button';
import { recordGlaEvent } from '.~/utils/tracks';

/**
 * @fires gla_setup_ads with given `{ triggered_by: 'back-button', action: 'leave' }` when back button is clicked.
 */
const SetupAdsTopBar = () => {
	// We record the intent to go back or to help - clicking buttons.
	// Those events are fired before the actual navigation happens.
	// The navigation itself may or maynot be blocked, for example to avoid leaving unsaved chanes.
	const handleBackButtonClick = () => {
		recordGlaEvent( 'gla_setup_ads', {
			triggered_by: 'back-button',
			action: 'leave',
		} );
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
