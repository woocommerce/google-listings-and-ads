/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '.~/components/app-documentation-link';

const AttributeMappingDescription = () => {
	return (
		<div>
			<p>
				{ __(
					"Automatically populate Google’s required attributes by mapping them to your store's existing product fields. Whenever you make changes to the value of your product fields, it instantly updates where it’s referenced.",
					'google-listings-and-ads'
				) }
			</p>
			<p>
				{ __(
					'You can override default values at specific product (or variant) level to give you the most flexibility.',
					'google-listings-and-ads'
				) }
			</p>
			<p>
				<AppDocumentationLink
					context="attribute-mapping"
					linkId="attribute-mapping-learn-more"
					href="https://support.google.com/"
				>
					{ __(
						'Learn more about attribute mapping',
						'google-listings-and-ads'
					) }
				</AppDocumentationLink>
			</p>
		</div>
	);
};

export default AttributeMappingDescription;
