/**
 * External dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { ASSET_FORM_KEY } from '.~/constants';

const ASSET_DISPLAY_URL_PATH_SPECS = [
	{
		maxCharacterCount: 15,
		capitalizedName: _x(
			'The first display URL path',
			'Capitalized asset field name as the start of an error message',
			'google-listings-and-ads'
		),
	},
	{
		maxCharacterCount: 15,
		capitalizedName: _x(
			'The second display URL path',
			'Capitalized asset field name as the start of an error message',
			'google-listings-and-ads'
		),
	},
];

const ASSET_MARKETING_IMAGE_SPECS = [
	{
		key: ASSET_FORM_KEY.MARKETING_IMAGE,
		min: 1,
		max: 8,
		imageConfig: {
			minWidth: 600,
			minHeight: 314,
			suggestedWidth: 1200,
			suggestedHeight: 628,
		},
		heading: _x(
			'Landscape images',
			'Plural asset field name as the heading',
			'google-listings-and-ads'
		),
		helpSubheading: _x(
			'Landscape image (1.91:1)',
			'Asset field name with its aspect ratio as the subheading within a help tip',
			'google-listings-and-ads'
		),
		lowercaseName: _x(
			'landscape',
			'Lowercase asset field name',
			'google-listings-and-ads'
		),
	},
	{
		key: ASSET_FORM_KEY.SQUARE_MARKETING_IMAGE,
		min: 1,
		max: 8,
		imageConfig: {
			minWidth: 300,
			minHeight: 300,
			suggestedWidth: 1200,
			suggestedHeight: 1200,
		},
		heading: _x(
			'Square images',
			'Plural asset field name as the heading',
			'google-listings-and-ads'
		),
		helpSubheading: _x(
			'Square image (1:1)',
			'Asset field name with its aspect ratio as the subheading within a help tip',
			'google-listings-and-ads'
		),
		lowercaseName: _x(
			'square',
			'Lowercase asset field name',
			'google-listings-and-ads'
		),
	},
	{
		key: ASSET_FORM_KEY.PORTRAIT_MARKETING_IMAGE,
		min: 0,
		max: 4,
		imageConfig: {
			minWidth: 480,
			minHeight: 600,
			suggestedWidth: 960,
			suggestedHeight: 1200,
		},
		heading: _x(
			'Portrait images',
			'Plural asset field name as the heading',
			'google-listings-and-ads'
		),
		helpSubheading: _x(
			'Portrait image (4:5)',
			'Asset field name with its aspect ratio as the subheading within a help tip',
			'google-listings-and-ads'
		),
		lowercaseName: _x(
			'portrait',
			'Lowercase asset field name',
			'google-listings-and-ads'
		),
	},
];

const ASSET_LOGO_SPECS = [
	{
		key: ASSET_FORM_KEY.LOGO,
		min: 1,
		max: 5,
		imageConfig: {
			minWidth: 128,
			minHeight: 128,
			suggestedWidth: 1200,
			suggestedHeight: 1200,
		},
		heading: _x(
			'Logo',
			'Plural asset field name as the heading',
			'google-listings-and-ads'
		),
		helpSubheading: _x(
			'Logo (1:1)',
			'Asset field name with its aspect ratio as the subheading within a help tip',
			'google-listings-and-ads'
		),
		lowercaseName: _x(
			'logo',
			'Lowercase asset field name',
			'google-listings-and-ads'
		),
	},
];

// The max number of asset images is shared across different types of asset images.
const ASSET_IMAGE_SPECS_GROUPS = [
	ASSET_MARKETING_IMAGE_SPECS,
	ASSET_LOGO_SPECS,
];

const ASSET_IMAGE_SPECS = ASSET_IMAGE_SPECS_GROUPS.flat();

const ASSET_TEXT_SPECS = [
	{
		key: ASSET_FORM_KEY.HEADLINE,
		min: 3,
		max: 5,
		maxCharacterCounts: [ 15, 30, 30, 30, 30 ],
		heading: _x(
			'Headlines',
			'Plural asset field name as the heading',
			'google-listings-and-ads'
		),
		addButtonText: __( 'Add headline', 'google-listings-and-ads' ),
		capitalizedName: _x(
			'Headline',
			'Capitalized asset field name as the placeholder or the start of an error message',
			'google-listings-and-ads'
		),
		lowercaseSingularName: _x(
			'headline',
			'Singular and lowercase asset field name',
			'google-listings-and-ads'
		),
		lowercasePluralName: _x(
			'headlines',
			'Plural and lowercase asset field name',
			'google-listings-and-ads'
		),
		help: __(
			'The headline is the first line of your ad and is most likely the first thing people notice, so consider including words that people may have entered in their Google search.',
			'google-listings-and-ads'
		),
	},
	{
		key: ASSET_FORM_KEY.LONG_HEADLINE,
		min: 1,
		max: 5,
		maxCharacterCounts: 90,
		heading: _x(
			'Long headlines',
			'Plural asset field name as the heading',
			'google-listings-and-ads'
		),
		addButtonText: __( 'Add long headline', 'google-listings-and-ads' ),
		capitalizedName: _x(
			'Long headline',
			'Capitalized asset field name as the placeholder or the start of an error message',
			'google-listings-and-ads'
		),
		lowercaseSingularName: _x(
			'long headline',
			'Singular and lowercase asset field name',
			'google-listings-and-ads'
		),
		lowercasePluralName: _x(
			'long headlines',
			'Plural and lowercase asset field name',
			'google-listings-and-ads'
		),
		help: (
			<>
				<div>
					{ __(
						'The long headline is the first line of your ad, and appears instead of your short headline in larger ads. Long headlines can be up to 90 characters, and may appear with or without your description.',
						'google-listings-and-ads'
					) }
				</div>
				<div>
					{ __(
						'The length of the rendered headline will depend on the site it appears on. If shortened, it will end with an ellipsis(…).',
						'google-listings-and-ads'
					) }
				</div>
			</>
		),
	},

	{
		key: ASSET_FORM_KEY.DESCRIPTION,
		min: 2,
		max: 5,
		maxCharacterCounts: [ 60, 90, 90, 90, 90 ],
		heading: _x(
			'Descriptions',
			'Plural asset field name as the heading',
			'google-listings-and-ads'
		),
		addButtonText: __( 'Add description', 'google-listings-and-ads' ),
		capitalizedName: _x(
			'Description',
			'Capitalized asset field name as the placeholder or the start of an error message',
			'google-listings-and-ads'
		),
		lowercaseSingularName: _x(
			'description',
			'Singular and lowercase asset field name',
			'google-listings-and-ads'
		),
		lowercasePluralName: _x(
			'descriptions',
			'Plural and lowercase asset field name',
			'google-listings-and-ads'
		),
		help: (
			<>
				<div>
					{ __(
						'The description adds to the headline and provides additional context or details. It can be up to 90 characters, and may appear after the headline.',
						'google-listings-and-ads'
					) }
				</div>
				<div>
					{ __(
						`The length of the rendered description will depend on the site it appears on. If it's shortened, it will end with an ellipsis(…). The description doesn't show in all sizes and formats.`,
						'google-listings-and-ads'
					) }
				</div>
			</>
		),
	},
];

// This scoped block is to signal that dynamic initialization is handled here, and that the
// functions inside are for initialization purposes only and are not expected to be exposed
// outside this module.
{
	function getSubheading( spec, shownAsSharedMax ) {
		if ( shownAsSharedMax ) {
			if ( spec.min === 0 ) {
				return;
			}

			return sprintf(
				// translators: 1: The minimal number of this item.
				__( 'At least %d required', 'google-listings-and-ads' ),
				spec.min
			);
		}

		return sprintf(
			// translators: 1: The minimal number of this item. 2: The maximum number of this item.
			__(
				'At least %1$d required. Add up to %2$d.',
				'google-listings-and-ads'
			),
			spec.min,
			spec.max
		);
	}

	function getImageHelpContent( subheading, imageConfig ) {
		const size = sprintf(
			// translators: 1: Recommended width. 2: Recommended height. 3: Minimal width. 4: Minimal height.
			__(
				'<listItem>Recommended size: %1$d x %2$d</listItem><listItem>Min. size: %3$d x %4$d</listItem>',
				'google-listings-and-ads'
			),
			imageConfig.suggestedWidth,
			imageConfig.suggestedHeight,
			imageConfig.minWidth,
			imageConfig.minHeight
		);
		return (
			<>
				<div>
					{ __(
						'Add images that meet or can be cropped to the recommended sizes. Note: The maximum file size for any image is 5120 KB.',
						'google-listings-and-ads'
					) }
				</div>
				<div>
					<strong>{ subheading }</strong>
				</div>
				<ul>
					{ createInterpolateElement( size, { listItem: <li /> } ) }
				</ul>
			</>
		);
	}

	ASSET_IMAGE_SPECS_GROUPS.forEach( ( specs ) => {
		// Currently, the PMax Assets feature in this extension doesn't offer managing the landscape_logo
		// asset but only the logo asset. So the logo asset shares the total number of images by itself.
		// To avoid confusing extension users, the UI and wording are shown the shared max concept when
		// the number of manageable images in the same group is > 1.
		const shownAsSharedMax = specs.length > 1;

		specs.forEach( ( spec ) => {
			spec.subheading = getSubheading( spec, shownAsSharedMax );
			spec.help = getImageHelpContent(
				spec.helpSubheading,
				spec.imageConfig
			);
		} );
	} );

	ASSET_TEXT_SPECS.forEach( ( spec ) => {
		spec.subheading = getSubheading( spec );
	} );
}

export { ASSET_IMAGE_SPECS, ASSET_TEXT_SPECS, ASSET_DISPLAY_URL_PATH_SPECS };
