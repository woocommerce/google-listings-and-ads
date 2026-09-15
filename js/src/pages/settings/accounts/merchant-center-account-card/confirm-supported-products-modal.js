/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { API_NAMESPACE } from '~/data/constants';
import AppButton from '~/components/app-button';
import AppDocumentationLink from '~/components/app-documentation-link';
import AppModal from '~/components/app-modal';
import useAdminUrl from '~/hooks/useAdminUrl';
import useApiFetchCallback from '~/hooks/useApiFetchCallback';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { recordGlaEvent } from '~/utils/tracks';
import { getAccountsSettingsUrl } from '~/utils/urls';
import {
	SUPPORTED_PRODUCTS_CONTEXT,
	SUPPORTED_PRODUCTS_POLICY_URL,
} from './constants';

/**
 * Confirming that the merchant sells products supported by Google.
 *
 * @event gla_supported_products_confirmation
 * @property {string} action (`confirm`|`cancel`|`success`|`error`) Confirmation action.
 * @property {string} context Page context. Always `settings-merchant-center-supported-products`.
 */

/**
 * @param {Object} props Component props.
 * @param {Function} props.onRequestClose Close callback.
 * @return {JSX.Element} Confirmation modal.
 */
export default function ConfirmSupportedProductsModal( { onRequestClose } ) {
	const adminUrl = useAdminUrl();
	const { createNotice } = useDispatchCoreNotices();
	const [ confirmSupportedProducts, { loading } ] = useApiFetchCallback( {
		path: `${ API_NAMESPACE }/mc/supported-products`,
		method: 'POST',
		data: { confirmed: true },
	} );
	const [ isConfirmationLocked, setIsConfirmationLocked ] = useState( false );
	const isConfirmationLockedRef = useRef( false );
	const isBusy = loading || isConfirmationLocked;

	const handleCancel = () => {
		if ( loading || isConfirmationLockedRef.current ) {
			return;
		}

		recordGlaEvent( 'gla_supported_products_confirmation', {
			action: 'cancel',
			context: SUPPORTED_PRODUCTS_CONTEXT,
		} );
		onRequestClose();
	};

	const handleConfirm = async () => {
		if ( loading || isConfirmationLockedRef.current ) {
			return;
		}

		isConfirmationLockedRef.current = true;
		setIsConfirmationLocked( true );

		recordGlaEvent( 'gla_supported_products_confirmation', {
			action: 'confirm',
			context: SUPPORTED_PRODUCTS_CONTEXT,
		} );

		try {
			await confirmSupportedProducts();
			recordGlaEvent( 'gla_supported_products_confirmation', {
				action: 'success',
				context: SUPPORTED_PRODUCTS_CONTEXT,
			} );
			window.location.href = adminUrl + getAccountsSettingsUrl();
		} catch ( error ) {
			isConfirmationLockedRef.current = false;
			setIsConfirmationLocked( false );
			recordGlaEvent( 'gla_supported_products_confirmation', {
				action: 'error',
				context: SUPPORTED_PRODUCTS_CONTEXT,
			} );
			createNotice(
				'error',
				__(
					'Unable to enable the Google Merchant Center connection. Please try again.',
					'google-listings-and-ads'
				)
			);
		}
	};

	return (
		<AppModal
			title={ __(
				'Confirm supported product types',
				'google-listings-and-ads'
			) }
			buttons={ [
				<AppButton
					key="cancel"
					isTertiary
					disabled={ isBusy }
					onClick={ handleCancel }
				>
					{ __( 'Cancel', 'google-listings-and-ads' ) }
				</AppButton>,
				<AppButton
					key="confirm"
					isPrimary
					loading={ isBusy }
					onClick={ handleConfirm }
				>
					{ __( 'Confirm', 'google-listings-and-ads' ) }
				</AppButton>,
			] }
			isDismissible={ ! isBusy }
			onRequestClose={ handleCancel }
		>
			<p>
				{ __(
					"Only confirm if this matches what you sell. If products that don't meet Google's requirements are submitted, Google will disapprove them.",
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ createInterpolateElement(
					__(
						'Not sure? <link>See what Google supports</link> before continuing.',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								context={ SUPPORTED_PRODUCTS_CONTEXT }
								linkId="supported-product-types"
								href={ SUPPORTED_PRODUCTS_POLICY_URL }
							/>
						),
					}
				) }
			</p>
		</AppModal>
	);
}
