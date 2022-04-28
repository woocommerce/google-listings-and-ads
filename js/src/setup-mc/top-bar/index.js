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
import HelpIconButton from '.~/components/help-icon-button';

/**
 * @fires gla_setup_mc with `{ target: 'back', trigger: 'click' }`.
 */
const SetupMCTopBar = () => {
	const handleBackButtonClick = () => {
		recordSetupMCEvent( 'back' );
	};

	return (
		<TopBar
			title={ __(
				'Get started with Google Listings & Ads',
				'google-listings-and-ads'
			) }
			helpButton={ <HelpIconButton eventContext="setup-mc" /> }
			backHref={ getNewPath( {}, '/google/start' ) }
			onBackButtonClick={ handleBackButtonClick }
		/>
	);
};

export default SetupMCTopBar;
