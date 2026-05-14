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
 * Renders the language select control within the market edit form.
 * This control is only enabled for multilingual stores;
 * for non-multilingual stores, a disabled input with a notice about
 * the multilingual requirement is rendered instead.
 */
const LanguageSelectControl = () => {
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

	// @TODO: replace with real language options and value once the multilingual scenario is implemented.
	return (
		<AppSearchableSelectControl
			label={ __( 'Language', 'google-listings-and-ads' ) }
			options={ [
				{ key: 'en', value: 'en', label: 'English' },
				{ key: 'es', value: 'es', label: 'Spanish' },
				{ key: 'fr', value: 'fr', label: 'French' },
			] }
			selected={ [ { key: 'fr', value: 'fr', label: 'French' } ] }
			inlineTags
			multiple
		/>
	);
};

export default LanguageSelectControl;
