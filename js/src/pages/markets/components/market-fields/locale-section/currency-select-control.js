/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import AppSearchableSelectControl from '~/components/app-searchable-select-control';
import AppInputControl from '~/components/app-input-control';
import useAvailableStoreCurrencies from '~/hooks/useAvailableStoreCurrencies';
import getValidCurrencyCodes from '../../../utils/getValidCurrencyCodes';

/**
 * Renders the currency select control within the market edit form.
 * This control is only enabled for multilingual stores;
 * for non-multilingual stores, a disabled input with a notice about
 * the multilingual requirement is rendered instead.
 */
const CurrencySelectControl = () => {
	const {
		getInputProps,
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();
	const { currencies, hasFinishedResolution } = useAvailableStoreCurrencies();

	if ( ! glaData.isMultiLingualStore ) {
		return (
			<AppInputControl
				label={ __( 'Currency', 'google-listings-and-ads' ) }
				placeholder={ __(
					'Requires multilingual plugin',
					'google-listings-and-ads'
				) }
				disabled
			/>
		);
	}

	const { selected: selectedLanguages } = getInputProps( 'language' );
	const validCurrencyCodes = getValidCurrencyCodes(
		currencies,
		selectedLanguages
	);

	// Filter currencies to only include those that are valid for the selected languages
	const options = currencies
		?.filter( ( currency ) => validCurrencyCodes.has( currency.code ) )
		.map( ( currency ) => ( {
			key: currency.code,
			value: currency.code,
			label: currency.code,
		} ) );

	const { onChange, selected } = getInputProps( 'currency' );
	const selectedOptions =
		options?.filter( ( opt ) => selected?.includes( opt.value ) ) ?? [];

	return (
		<div>
			<AppSearchableSelectControl
				disabled={ ! hasFinishedResolution }
				label={ __( 'Currency', 'google-listings-and-ads' ) }
				onChange={ ( changedOptions ) => {
					onChange(
						changedOptions.map( ( option ) => option.value )
					);
				} }
				options={ options }
				selected={ selectedOptions }
				inlineTags
				isSearchable
				multiple
			/>
			{ renderRequestedValidation( 'currency' ) }
		</div>
	);
};

export default CurrencySelectControl;
