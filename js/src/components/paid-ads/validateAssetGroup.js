/**
 * External dependencies
 */
import { __, _n, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import getCharacterCounter from '.~/utils/getCharacterCounter';
import { ASSET_FORM_KEY } from '.~/constants';
import {
	ASSET_IMAGE_SPECS,
	ASSET_TEXT_SPECS,
	ASSET_DISPLAY_URL_PATH_SPECS,
} from './assetSpecs';

/**
 * @typedef {import('.~/components/types.js').AssetGroupFormValues} AssetGroupFormValues
 */

/**
 * Validate asset group form. Accepts the form values object and returns error object.
 *
 * @param {AssetGroupFormValues} values Form values.
 * @return {Object} The error object.
 */
export default function validateAssetGroup( values ) {
	const error = {};

	ASSET_IMAGE_SPECS.forEach( ( spec ) => {
		const images = values[ spec.key ];

		if ( images.length < spec.min ) {
			error[ spec.key ] = sprintf(
				// translators: 1: The minimal number of this item. 2: Asset field name.
				_n(
					'Add at least %1$d %2$s image',
					'Add at least %1$d %2$s images',
					spec.min,
					'google-listings-and-ads'
				),
				spec.min,
				spec.lowercaseName
			);
		}
	} );

	const countCharacter = getCharacterCounter( 'google-ads' );

	ASSET_TEXT_SPECS.forEach( ( spec ) => {
		const messages = [];
		const texts = values[ spec.key ];
		const filledTexts = texts.filter( Boolean );

		if ( spec.min >= 2 && Array.isArray( spec.maxCharacterCounts ) ) {
			const [ first, second ] = spec.maxCharacterCounts;

			if ( first < second && texts[ 0 ] === '' ) {
				const message = sprintf(
					// translators: Asset field name.
					__(
						'The %s in the first field is required',
						'google-listings-and-ads'
					),
					spec.lowercaseSingularName
				);

				messages.push( message );
			}
		}

		if ( filledTexts.length < spec.min ) {
			const name =
				spec.min === 1
					? spec.lowercaseSingularName
					: spec.lowercasePluralName;

			const message = sprintf(
				// translators: 1: The minimal number of this item. 2: Asset field name.
				__( 'Add at least %1$d %2$s', 'google-listings-and-ads' ),
				spec.min,
				name
			);

			messages.push( message );
		}

		if ( new Set( filledTexts ).size !== filledTexts.length ) {
			const message = sprintf(
				// translators: Asset field name.
				__( '%s are identical', 'google-listings-and-ads' ),
				spec.heading
			);

			messages.push( message );
		}

		const normalizedMaxCharacterCounts = [ spec.maxCharacterCounts ].flat();

		texts.forEach( ( text, index ) => {
			const maxCharacterCount =
				normalizedMaxCharacterCounts[ index ] ??
				normalizedMaxCharacterCounts[ 0 ];

			if ( countCharacter( text ) > maxCharacterCount ) {
				const message = sprintf(
					// translators: 1: Asset field name. 2: The sequential number of the asset field.
					__(
						'%1$s %2$d: Character limit exceeded',
						'google-listings-and-ads'
					),
					spec.capitalizedName,
					index + 1
				);

				messages.push( message );
			}
		} );

		if ( messages.length ) {
			error[ spec.key ] = messages;
		}
	} );

	const displayUrlPaths = values[ ASSET_FORM_KEY.DISPLAY_URL_PATH ];

	if ( displayUrlPaths.length ) {
		const messages = [];
		const [ firstPath, secondPath ] = displayUrlPaths;

		if ( ! firstPath && secondPath ) {
			const message = sprintf(
				// translators: Asset field name.
				__( '%s is incomplete', 'google-listings-and-ads' ),
				ASSET_DISPLAY_URL_PATH_SPECS[ 0 ].capitalizedName
			);
			messages.push( message );
		}

		ASSET_DISPLAY_URL_PATH_SPECS.forEach( ( spec, index ) => {
			const path = displayUrlPaths[ index ] || '';

			if ( countCharacter( path ) > spec.maxCharacterCount ) {
				const message = sprintf(
					// translators: Asset field name.
					__(
						'%s: Character limit exceeded',
						'google-listings-and-ads'
					),
					spec.capitalizedName
				);

				messages.push( message );
			}
		} );

		if ( messages.length ) {
			error[ ASSET_FORM_KEY.DISPLAY_URL_PATH ] = messages;
		}
	}

	return error;
}
