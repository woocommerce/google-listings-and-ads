/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice } from '@wordpress/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useGoogleMCAccount from '.~/hooks/useGoogleMCAccount';
import EnableNewProductSyncButton from '.~/components/enable-new-product-sync-button';

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

	// Do not render if already switch to new product sync.
	if (
		! hasGoogleMCAccountFinishedResolution ||
		googleMCAccount.wpcom_rest_api_status
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
					enableButton: <EnableNewProductSyncButton />,
				}
			) }
		</Notice>
	);
};

export default EnableNewProductSyncNotice;
