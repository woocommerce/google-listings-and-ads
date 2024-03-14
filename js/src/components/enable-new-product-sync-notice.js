/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';
import { addQueryArgs } from '@wordpress/url';

/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import { glaData } from '.~/constants';
import { API_NAMESPACE } from '.~/data/constants';
import useApiFetchCallback from '.~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '.~/hooks/useDispatchCoreNotices';
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';

/**
 * Shows info {@link Notice}
 * about enabling new product sync when the feature flag is turned on and hasn't switched to new product sync.
 *
 * @return {JSX.Element} {@link Notice} element with the info message and the button to enable new product sync.
 */
const EnableNewProductSyncNotice = () => {
	const {
		hasFinishedResolution: hasGoogleMCAccountFinishedResolution,
		googleMCAccount,
	} = useGoogleMCAccount();

	const { createNotice } = useDispatchCoreNotices();

	const nextPageName = glaData.mcSetupComplete ? 'reconnect' : 'setup-mc';
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

	// Do not render if already switch to new product sync.
	// TODO: also check if the new product sync feature flag is enabled.
	if (
		! hasGoogleMCAccountFinishedResolution ||
		googleMCAccount.wpcom_rest_api_status === 'approved'
	) {
		return null;
	}

	return (
		<Notice status="info" isDismissible={ false }>
			{ createInterpolateElement(
				__(
					'Enable new product sync. <enableButton>Enable</enableButton>',
					'google-listings-and-ads'
				),
				{
					enableButton: (
						<AppButton isTertiary onClick={ handleEnableClick } />
					),
				}
			) }
		</Notice>
	);
};

export default EnableNewProductSyncNotice;
