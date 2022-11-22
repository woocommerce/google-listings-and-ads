/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Renders Attribute mapping description
 *
 * @return {JSX.Element} The description markup
 */
const AttributeMappingDescription = () => {
	return (
		<p>
			{ __(
				'Create attribute rules to control what product data gets sent to Google and to manage product attributes in bulk.',
				'google-listings-and-ads'
			) }
		</p>
	);
};

export default AttributeMappingDescription;
