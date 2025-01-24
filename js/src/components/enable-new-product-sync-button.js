/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AppButton from '~/components/app-button';
import { glaData } from '~/constants';
import { API_NAMESPACE } from '~/data/constants';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';

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
 * @fires gla_enable_product_sync_click with `{ page: 'setup-mc' | 'settings', context: 'banner' | 'mc_card' }`
 */
const EnableNewProductSyncButton = ( props ) => {
	const { createNotice } = useDispatchCoreNotices();
	const [ loading, setLoading ] = useState( false );
	const nextPageName = glaData.mcSetupComplete ? 'settings' : 'setup-mc';
	const query = { next_page_name: nextPageName };
	const path = addQueryArgs( `${ API_NAMESPACE }/rest-api/authorize`, query );
	const [ fetchRestAPIAuthorize ] = useApiFetchCallback( { path } );
	const handleEnableClick = async () => {
		try {
			setLoading( true );
			const d = await fetchRestAPIAuthorize();
			window.location.href = d.auth_url;
		} catch ( error ) {
			setLoading( false );
			createNotice(
				'error',
				__(
					'Unable to enable new product sync. Please try again later.',
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
