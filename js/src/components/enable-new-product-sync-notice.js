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
					'We will soon transition to a new and improved method for synchronizing product data with Google. <enableButton>Get early access</enableButton>',
					'google-listings-and-ads'
				),
				{
					enableButton: (
						<EnableNewProductSyncButton
							eventName="gla_enable_product_sync_click"
							eventProps={ { context: 'banner' } }
						/>
					),
				}
			) }
		</Notice>
	);
};

export default EnableNewProductSyncNotice;
