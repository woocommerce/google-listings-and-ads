/**
 * External dependencies
 */
import { createInterpolateElement } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppDocumentationLink from '~/components/app-documentation-link';

const ERRORS_MAP = {
	ads: {
		406: {
			'Not Acceptable': {
				title: __( 'Connection Failed', 'google-listings-and-ads' ),
				description: createInterpolateElement(
					__(
						'The user does not have permission to perform this action on the resource or method because the Google Ads account is suspended. See <link>https://support.google.com/adspolicy/answer/2375414</link> for help.',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								href="https://support.google.com/adspolicy/answer/2375414"
								linkId="ads-connection-failed"
							/>
						),
					}
				),
			},
			FAILED_PRECONDITION: {
				title: __( 'Connection Failed', 'google-listings-and-ads' ),
				description: createInterpolateElement(
					__(
						'The user does not have permission to perform this action on the resource or method because the Google Ads account is suspended. See <link>https://support.google.com/adspolicy/answer/2375414</link> for help.',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								href="https://support.google.com/adspolicy/answer/2375414"
								linkId="ads-connection-failed"
							/>
						),
					}
				),
			},
		},
	},
	merchant_center: {
		400: {
			INVALID_ARGUMENT: {
				title: __( 'Connection Failed', 'google-listings-and-ads' ),
				description: createInterpolateElement(
					__(
						'The user does not have permission to perform this action on the resource or method because the Merchant Center account is suspended. See <link>https://support.google.com/merchants/answer/6150127</link> for help.',
						'google-listings-and-ads'
					),
					{
						link: (
							<AppDocumentationLink
								href="https://support.google.com/merchants/answer/6150127"
								linkId="merchant-center-connection-failed"
							/>
						),
					}
				),
			},
		},
	},
	general: {},
};

export default ERRORS_MAP;
