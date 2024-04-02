/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import { glaData } from '.~/constants';
import { API_NAMESPACE } from '.~/data/constants';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';

/**
 * Button to initiate auth process for WP Rest API
 *
 * @param {Object} params The component params
 * @return {JSX.Element} The button.
 */
const EnableNewProductSyncButton = ( params ) => {
	const { createNotice } = useDispatchCoreNotices();

	const nextPageName = glaData.mcSetupComplete ? 'settings' : 'setup-mc';
	const query = { next_page_name: nextPageName };
	const path = addQueryArgs( `${ API_NAMESPACE }/rest-api/authorize`, query );
	const [ fetchRestAPIAuthorize ] = useApiFetchCallback( { path } );
	const handleEnableClick = async () => {
		try {
			const d = await fetchRestAPIAuthorize();
			window.location.href = d.auth_url;
		} catch ( error ) {
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
		<AppButton isSecondary onClick={ handleEnableClick } { ...params } />
	);
};

export default EnableNewProductSyncButton;
