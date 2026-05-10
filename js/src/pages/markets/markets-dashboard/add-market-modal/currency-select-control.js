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

const CurrencySelectControl = () => {
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
