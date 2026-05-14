/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { glaData } from '~/constants';
import AppSearchableSelectControl from '~/components/app-searchable-select-control';
import AppInputControl from '~/components/app-input-control';

/**
 * Renders the currency select control within the market edit form.
 * This control is only enabled for multilingual stores;
 * for non-multilingual stores, a disabled input with a notice about
 * the multilingual requirement is rendered instead.
 */
const CurrencySelectControl = () => {
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

	// @TODO: replace with real currency options and value once the multilingual scenario is implemented.
	return (
		<AppSearchableSelectControl
			label={ __( 'Currency', 'google-listings-and-ads' ) }
			options={ [
				{ key: 'usd', value: 'usd', label: 'USD' },
				{ key: 'eur', value: 'eur', label: 'EUR' },
				{ key: 'gbp', value: 'gbp', label: 'GBP' },
			] }
			selected={ [ { key: 'usd', value: 'usd', label: 'USD' } ] }
			inlineTags
			multiple
		/>
	);
};

export default CurrencySelectControl;
