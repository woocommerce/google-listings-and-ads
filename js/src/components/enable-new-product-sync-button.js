/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { glaData } from '~/constants';
import { useAppDispatch } from '~/data';
import { handleApiError } from '~/utils/handleError';

/**
 * Clicking on the button to start enabling the new product sync (API Pull).
 *
 * @event gla_enable_product_sync_click
 * @property {string} [page] Indicates which page this event happened
 * @property {string} [context] Indicates where or which the button triggered this event
 */

/**
 * Button to initiate auth process for WP Rest API
 *
 * @param {Object} props The component props to be forwarded to AppButton.
 * @return {JSX.Element} The button.
 *
 * @fires gla_enable_product_sync_click with `{ page: 'settings', context: 'banner' | 'mc_card' }`
 */
const EnableNewProductSyncButton = ( props ) => {
	const { fetchWPComAppAuthorizationUrl } = useAppDispatch();
	const [ loading, setLoading ] = useState( false );
	const nextPageName = glaData.mcSetupComplete ? 'settings' : 'setup-mc';

	const handleEnableClick = async () => {
		try {
			setLoading( true );
			const authUrl = await fetchWPComAppAuthorizationUrl( nextPageName );
			window.location.href = authUrl;
		} catch ( error ) {
			setLoading( false );
			handleApiError(
				error,
				__(
					'Unable to enable new product sync.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<AppButton
			isSecondary
			loading={ loading }
			onClick={ handleEnableClick }
			eventName="gla_enable_product_sync_click"
			{ ...props }
		/>
	);
};

export default EnableNewProductSyncButton;
