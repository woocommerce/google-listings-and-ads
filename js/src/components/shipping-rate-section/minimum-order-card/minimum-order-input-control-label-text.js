/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */

import CountryNamesPlusMore from '.~/components/country-names-plus-more';

const MinimumOrderInputControlLabelText = ( props ) => {
	const { countries } = props;

	return (
		<div>
			<CountryNamesPlusMore
				countries={ countries }
				textWithMore={
					// translators: 1: list of country names separated by comma, up to 5 countries; 2: the remaining count of countries.
					__(
						`Minimum order for <strong>%1$s</strong> + %2$d more`,
						'google-listings-and-ads'
					)
				}
				textWithoutMore={
					// translators: 1: list of country names separated by comma.
					__(
						`Minimum order for <strong>%1$s</strong>`,
						'google-listings-and-ads'
					)
				}
			/>
		</div>
	);
};

export default MinimumOrderInputControlLabelText;
