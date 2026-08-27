/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Section from '~/components/section';
import AppSpinner from '~/components/app-spinner';
import AdaptiveForm from '~/components/adaptive-form';
import TaxRate from './tax-rate';
import useSettings from '~/hooks/useSettings';
import useDisplayTaxRate from './useDisplayTaxRate';
import useTargetAudienceFinalCountryCodes from '~/hooks/useTargetAudienceFinalCountryCodes';
import useDispatchCoreNotices from '~/hooks/useDispatchCoreNotices';
import { handleApiError } from '~/utils/handleError';

const validTaxRateSet = new Set( [ 'destination', 'manual' ] );

/**
 * Renders the tax rate setup if the current target audience requires it.
 *
 * This component won't display the validation error message on UI,
 * because it should be obvious to the user that they have to select
 * one of the radio options to continue the submission.
 */
export default function SetupTaxRate() {
	const { settings, saveSettings, syncSettings } = useSettings();
	const { data: audienceCountries } = useTargetAudienceFinalCountryCodes();
	const shouldDisplayTaxRate = useDisplayTaxRate( audienceCountries );
	const { createNotice } = useDispatchCoreNotices();

	if ( ! shouldDisplayTaxRate || ! settings?.hasOwnProperty( 'tax_rate' ) ) {
		if ( shouldDisplayTaxRate === false ) {
			return null;
		}

		return (
			<Section>
				<AppSpinner />
			</Section>
		);
	}

	const handleValidate = ( values ) => {
		const errors = {};

		if ( ! validTaxRateSet.has( values.tax_rate ) ) {
			errors.tax_rate = __(
				'Please specify tax rate option.',
				'google-listings-and-ads'
			);
		}

		return errors;
	};

	const handleSubmit = async ( values ) => {
		const nextSettings = {
			...settings,
			tax_rate: values.tax_rate,
		};

		return saveSettings( nextSettings )
			.then( syncSettings, ( error ) => {
				handleApiError(
					error,
					__(
						'There was an error saving tax rate.',
						'google-listings-and-ads'
					)
				);
			} )
			.catch( ( error ) => {
				handleApiError(
					error,
					__(
						'There was an error synchronizing tax rate to Google Merchant Center.',
						'google-listings-and-ads'
					)
				);
			} )
			.then( () => {
				createNotice(
					'success',
					__(
						'Your change to tax rate has been saved and will be synced to your Google Merchant Center.',
						'google-listings-and-ads'
					)
				);
			} );
	};

	return (
		<AdaptiveForm
			initialValues={ { tax_rate: settings.tax_rate } }
			onSubmit={ handleSubmit }
			validate={ handleValidate }
		>
			{ ( formContext ) => {
				return <TaxRate handleSubmit={ formContext.handleSubmit } />;
			} }
		</AdaptiveForm>
	);
}
