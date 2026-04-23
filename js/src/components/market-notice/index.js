/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import AppNotice from '~/components/app-notice';

const VARIANT_MAP = {
	shippingManaged: {
		title: __( 'Shipping managed by Google', 'google-listings-and-ads' ),
		description: __(
			'Your shipping settings are managed through Google Merchant Center.',
			'google-listings-and-ads'
		),
	},
	automaticShipping: {
		title: __( 'Automatic shipping', 'google-listings-and-ads' ),
		description: __(
			'Shipping rates and times are automatically synced from your store settings.',
			'google-listings-and-ads'
		),
	},
};

/**
 * Renders an info notice whose title and description are resolved from a
 * built-in variant map, so all copy lives in one place.
 *
 * @param {Object} props
 * @param {string} props.variant One of the keys defined in VARIANT_MAP.
 * @return {JSX.Element|null} The notice, or null for an unrecognised variant.
 */
const MarketNotice = ( { variant } ) => {
	const notice = VARIANT_MAP[ variant ];

	if ( ! notice ) {
		return null;
	}

	const { title, description } = notice;

	return (
		<AppNotice status="info" isDismissible={ false }>
			<strong>{ title }</strong>
			<p>{ description }</p>
		</AppNotice>
	);
};

export default MarketNotice;
