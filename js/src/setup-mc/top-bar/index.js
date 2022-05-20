/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import TopBar from '.~/components/stepper/top-bar';
import HelpIconButton from '.~/components/help-icon-button';

/**
 * @fires gla_setup_mc with `{ target: 'back', trigger: 'click' }`.
 */
const SetupMCTopBar = () => {
	const handleBackButtonClick = () => {
		recordEvent( 'gla_setup_mc', { target: 'back', trigger: 'click' } );
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
