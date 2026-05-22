/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import AppSearchableSelectControl from '~/components/app-searchable-select-control';
import AppInputControl from '~/components/app-input-control';
import useMCSupportedCurrencies from '~/hooks/useMCSupportedCurrencies';

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
	const { currencies, hasFinishedResolution } = useMCSupportedCurrencies();

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

	const options = currencies?.map( ( currency ) => ( {
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
				label={ __( 'Currency', 'google-listings-and-ads' ) }
				options={ options }
				disabled={ ! hasFinishedResolution }
				selected={ selectedOptions }
				onChange={ ( changedOptions ) => {
					onChange(
						changedOptions.map( ( option ) => option.value )
					);
				} }
				inlineTags
				multiple
				isSearchable
			/>
			{ renderRequestedValidation( 'currency' ) }
		</div>
	);
};

export default CurrencySelectControl;
