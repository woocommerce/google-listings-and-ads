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
import useMCSupportedLanguages from '~/hooks/useMCSupportedLanguages';

/**
 * Renders the language select control within the market edit form.
 * This control is only enabled for multilingual stores;
 * for non-multilingual stores, a disabled input with a notice about
 * the multilingual requirement is rendered instead.
 */
const LanguageSelectControl = () => {
	const { getInputProps } = useAdaptiveFormContext();
	const { languages, hasFinishedResolution } = useMCSupportedLanguages();

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

	const inputProps = getInputProps( 'language' );

	return (
		<AppSearchableSelectControl
			label={ __( 'Language', 'google-listings-and-ads' ) }
			options={ options }
			disabled={ ! hasFinishedResolution }
			helperText={ __(
				"Languages and currencies are populated from your multilingual plugin. You can remove them per market but can't add ones the plugin doesn't provide.",
				'google-listings-and-ads'
			) }
			inlineTags
			multiple
			{ ...inputProps }
		/>
	);
};

export default LanguageSelectControl;
