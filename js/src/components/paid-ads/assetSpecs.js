/**
 * External dependencies
 */
import { __, _x, sprintf } from '@wordpress/i18n';
import { createInterpolateElement } from '@wordpress/element';

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

const ASSET_IMAGE_SPECS = [
	{
		key: 'marketing_image',
		min: 1,
		max: 20,
		imageConfig: {
			minWidth: 600,
			minHeight: 314,
			suggestedWidth: 1200,
			suggestedHeight: 628,
		},
		heading: _x(
			'Rectangular images',
			'Plural asset field name as the heading',
			'google-listings-and-ads'
		),
		helpSubheading: _x(
			'Landscape image (1.91:1)',
			'Asset field name with its aspect ratio as the subheading within a help tip',
			'google-listings-and-ads'
		),
		lowercaseName: _x(
			'rectangular',
			'Lowercase asset field name',
			'google-listings-and-ads'
		),
	},
	{
		key: 'square_marketing_image',
		min: 1,
		max: 20,
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
		key: 'logo',
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
			'Square logo (1:1)',
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

const ASSET_TEXT_SPECS = [
	{
		key: 'headline',
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
		key: 'long_headline',
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
				<p>
					{ __(
						'The long headline is the first line of your ad, and appears instead of your short headline in larger ads. Long headlines can be up to 90 characters, and may appear with or without your description.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					{ __(
						'The length of the rendered headline will depend on the site it appears on. If shortened, it will end with an ellipsis(…).',
						'google-listings-and-ads'
					) }
				</p>
			</>
		),
	},

	{
		key: 'description',
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
				<p>
					{ __(
						'The description adds to the headline and provides additional context or details. It can be up to 90 characters, and may appear after the headline.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					{ __(
						`The length of the rendered description will depend on the site it appears on. If it's shortened, it will end with an ellipsis(…). The description doesn't show in all sizes and formats.`,
						'google-listings-and-ads'
					) }
				</p>
			</>
		),
	},
];

// This scoped block is to signal that dynamic initialization is handled here, and that the
// functions inside are for initialization purposes only and are not expected to be exposed
// outside this module.
{
	function getSubheading( min, max ) {
		return sprintf(
			// translators: 1: The minimal number of this item. 2: The maximum number of this item.
			__(
				'At least %1$d required. Add up to %2$d.',
				'google-listings-and-ads'
			),
			min,
			max
		);
	}

	function getImageHelpContent( subheading, imageConfig ) {
		const size = sprintf(
			// translators: 1: Recommended width. 2: Recommended height. 3: Minimal width. 4: Minimal height.
			__(
				'Recommended size: %1$d x %2$d<newline />Min. size: %3$d x %4$d',
				'google-listings-and-ads'
			),
			imageConfig.suggestedWidth,
			imageConfig.suggestedHeight,
			imageConfig.minWidth,
			imageConfig.minHeight
		);
		return (
			<>
				<p>
					{ __(
						'Add images that meet or can be cropped to the recommended sizes. Note: The maximum file size for any image is 5120 KB.',
						'google-listings-and-ads'
					) }
				</p>
				<p>
					<strong>{ subheading }</strong>
				</p>
				<p>{ createInterpolateElement( size, { newline: <br /> } ) }</p>
			</>
		);
	}

	ASSET_IMAGE_SPECS.forEach( ( spec ) => {
		spec.subheading = getSubheading( spec.min, spec.max );
		spec.help = getImageHelpContent(
			spec.helpSubheading,
			spec.imageConfig
		);
	} );

	ASSET_TEXT_SPECS.forEach( ( spec ) => {
		spec.subheading = getSubheading( spec.min, spec.max );
	} );
}

export { ASSET_IMAGE_SPECS, ASSET_TEXT_SPECS, ASSET_DISPLAY_URL_PATH_SPECS };
