/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { getNewPath } from '@woocommerce/navigation';

/**
 * Internal dependencies
 */
import TopBar from '~/components/stepper/top-bar';
import HelpIconButton from '~/components/help-icon-button';
import { recordGlaEvent } from '~/utils/tracks';

/**
 * @fires gla_setup_mc with `{ triggered_by: 'back-button', action: 'leave' }`.
 */
const SetupTopBar = () => {
	const handleBackButtonClick = () => {
		recordGlaEvent( 'gla_setup_mc', {
			triggered_by: 'back-button',
			action: 'leave',
		} );
	};

	return (
		<TopBar
			backHref={ getNewPath( {}, '/google/start' ) }
			helpButton={ <HelpIconButton eventContext="setup-mc" /> }
			onBackButtonClick={ handleBackButtonClick }
			title={ __(
				'Get started with Google for WooCommerce',
				'google-listings-and-ads'
			) }
		/>
	);
};

export default SetupTopBar;
