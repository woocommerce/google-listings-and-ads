/**
 * External dependencies
 */
import { getNewPath } from '@woocommerce/navigation';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { recordSetupAdsEvent } from '.~/utils/recordEvent';
import TopBar from '.~/components/stepper/top-bar';

const SetupAdsTopBar = () => {
	// We record the intent to go back or to help - clicking buttons.
	// Those events are fired before the actual navigation happens.
	// The navigation itself may or maynot be blocked, for example to avoid leaving unsaved chanes.
	const handleBackButtonClick = () => {
		recordSetupAdsEvent( 'back' );
	};

	const handleHelpButtonClick = () => {
		recordSetupAdsEvent( 'help' );
	};

	return (
		<TopBar
			title={ __( 'Set up paid campaign', 'google-listings-and-ads' ) }
			backHref={ getNewPath( {}, '/google/dashboard' ) }
			onBackButtonClick={ handleBackButtonClick }
			onHelpButtonClick={ handleHelpButtonClick }
		/>
	);
};

export default SetupAdsTopBar;
