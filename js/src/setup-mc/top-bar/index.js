/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import TopBar from '.~/components/stepper/top-bar';
import { recordSetupMCEvent } from '.~/utils/recordEvent';

const SetupMCTopBar = () => {
	const handleBackButtonClick = () => {
		recordSetupMCEvent( 'back' );
	};

	const handleHelpButtonClick = () => {
		recordSetupMCEvent( 'help' );
	};

	return (
		<TopBar
			title={ __(
				'Get started with Google Listings & Ads',
				'google-listings-and-ads'
			) }
			backHref={ getNewPath( {}, '/google/start' ) }
			onBackButtonClick={ handleBackButtonClick }
			onHelpButtonClick={ handleHelpButtonClick }
		/>
	);
};

export default SetupMCTopBar;
