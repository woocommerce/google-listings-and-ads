/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import AdaptiveForm from '~/components/adaptive-form';
import AppSpinner from '~/components/app-spinner';
import Section from '~/components/section';
import ShippingRateMethodSection from '~/components/shipping-rate-section/shipping-rate-method-section';
import { SHIPPING_RATE_METHOD, SHIPPING_TIME_METHOD } from '~/constants';
import useSettings from '~/hooks/useSettings';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { handleApiError } from '~/utils/handleError';

/**
 * Renders the shipping rate method selector on the Settings page with auto-save.
 *
 * Changing the shipping rate method immediately triggers a save and sync to
 * Google Merchant Center — no submit button is required.
 *
 * @return {JSX.Element|null} The shipping rate settings component.
 */
const ShippingRateSettings = () => {
	const { settings, saveSettings, syncSettings } = useSettings();
	const { createNotice } = useDispatchCoreNotices();
	const [ isSaving, setIsSaving ] = useState( false );

	const handleChange = useCallback(
		async ( change ) => {
			if ( change.name !== 'shipping_rate' ) {
				return;
			}

			const newShippingRate = change.value;
			const coupledShippingTime =
				newShippingRate === SHIPPING_RATE_METHOD.MANUAL
					? SHIPPING_TIME_METHOD.MANUAL
					: SHIPPING_TIME_METHOD.FLAT;

			setIsSaving( true );

			try {
				await saveSettings( {
					...settings,
					shipping_rate: newShippingRate,
					shipping_time: coupledShippingTime,
				} );
			} catch ( error ) {
				handleApiError(
					error,
					__(
						'There was an error saving the shipping rate method.',
						'google-listings-and-ads'
					)
				);
				setIsSaving( false );
				return;
			}

			try {
				await syncSettings();
			} catch ( error ) {
				handleApiError(
					error,
					__(
						'There was an error synchronizing the shipping rate method to Google Merchant Center.',
						'google-listings-and-ads'
					)
				);
				setIsSaving( false );
				return;
			}

			createNotice(
				'success',
				__(
					'Your shipping rate method has been saved and synced to your Google Merchant Center.',
					'google-listings-and-ads'
				)
			);
			setIsSaving( false );
		},
		[ settings, saveSettings, syncSettings, createNotice ]
	);

	if ( settings === undefined ) {
		return (
			<Section>
				<AppSpinner />
			</Section>
		);
	}

	if ( ! settings?.hasOwnProperty( 'shipping_rate' ) ) {
		return null;
	}

	return (
		<AdaptiveForm
			initialValues={ { shipping_rate: settings.shipping_rate } }
			onChange={ handleChange }
		>
			<ShippingRateMethodSection disabled={ isSaving } />
		</AdaptiveForm>
	);
};

export default ShippingRateSettings;
