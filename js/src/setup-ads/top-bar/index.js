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
	const handleBackButtonClick = () => {
		recordSetupAdsEvent( 'back' );
	};

	const handleHelpButtonClick = () => {
		recordSetupAdsEvent( 'help' );

		// TODO: navigate to where upon clicking help link?
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
