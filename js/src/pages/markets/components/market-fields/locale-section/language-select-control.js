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
import useAvailableLanguagesCurrencies from '~/hooks/useAvailableLanguagesCurrencies';
import getValidCurrencyCodes from '../../../utils/getValidCurrencyCodes';

/**
 * Renders the language select control within the market edit form.
 * This control is only enabled for multilingual stores;
 * for non-multilingual stores, a disabled input with a notice about
 * the multilingual requirement is rendered instead.
 */
const LanguageSelectControl = () => {
	const {
		getInputProps,
		setValues,
		adapter: { renderRequestedValidation },
	} = useAdaptiveFormContext();
	const { languages, currencies, hasFinishedResolution } =
		useAvailableLanguagesCurrencies();

	if ( ! glaData.isMultiLingualStore ) {
		return (
			<AppInputControl
				label={ __( 'Language', 'google-listings-and-ads' ) }
				placeholder={ __(
					'Requires multilingual plugin',
					'google-listings-and-ads'
				) }
				disabled
			/>
		);
	}

	const options = languages?.map( ( language ) => ( {
		key: language.code,
		value: language.code,
		label: language.label,
	} ) );

	const { selected } = getInputProps( 'language' );
	const currencyInputProps = getInputProps( 'currency' );

	const selectedOptions =
		options?.filter( ( option ) => selected?.includes( option.value ) ) ??
		[];

	/**
	 * Language change handler
	 * Updates the currency selection to only include currencies that are valid
	 * for the new language selection.
	 *
	 * @param {Array<{value: string}>} changedOptions The new language options.
	 * @return {void}
	 */
	const handleLanguageChange = ( changedOptions ) => {
		const newLanguages = changedOptions.map( ( option ) => option.value );

		const validCurrencyCodes = getValidCurrencyCodes(
			currencies,
			newLanguages
		);
		const selectedCurrencies = currencyInputProps.selected ?? [];

		// Currently-selected currency codes that are still valid for the new language selection.
		const prunedCurrencies = selectedCurrencies.filter( ( code ) =>
			validCurrencyCodes.has( code )
		);

		setValues( {
			language: newLanguages,
			...( prunedCurrencies.length !== selectedCurrencies.length
				? { currency: prunedCurrencies }
				: {} ),
		} );
	};

	return (
		<div>
			<AppSearchableSelectControl
				label={ __( 'Language', 'google-listings-and-ads' ) }
				options={ options }
				disabled={ ! hasFinishedResolution }
				selected={ selectedOptions }
				onChange={ handleLanguageChange }
				helperText={ __(
					"Languages and currencies are populated from your multilingual plugin. You can remove them per market but can't add ones the plugin doesn't provide.",
					'google-listings-and-ads'
				) }
				inlineTags
				multiple
				isSearchable
			/>
			{ renderRequestedValidation( 'language' ) }
		</div>
	);
};

export default LanguageSelectControl;
