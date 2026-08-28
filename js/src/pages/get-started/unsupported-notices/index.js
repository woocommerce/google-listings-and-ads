/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Notice, Icon } from '@wordpress/components';
import { external as externalIcon } from '@wordpress/icons';
import { Link } from '@woocommerce/components';
import { createInterpolateElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import useTargetAudience from '~/hooks/useTargetAudience';
import useStoreCountry from '~/hooks/useStoreCountry';
import AppDocumentationLink from '~/components/app-documentation-link';
import { glaData } from '~/constants';
import './index.scss';

const ExternalIcon = () => (
	<Icon
		className="gla-get-started-notice__icon"
		icon={ externalIcon }
		size={ 18 }
	/>
);

/**
 * @fires gla_documentation_link_click with `{ context: 'get-started', link_id: 'supported-languages', href: 'https://support.google.com/merchants/answer/160637' }`
 */
const UnsupportedLanguage = () => {
	const { data } = useTargetAudience();

	if ( ! data ) {
		return null;
	}

	return (
		<Notice
			className="gla-get-started-notice"
			isDismissible={ false }
			status="error"
		>
			{ createInterpolateElement(
				__(
					'Your site language is <language />. This language is currently not supported by Google for WooCommerce. <settingsLink>You can change your site language here</settingsLink>. <supportedLanguagesLink>Read more about supported languages</supportedLanguagesLink>',
					'google-listings-and-ads'
				),
				{
					language: <strong>{ data.language }</strong>,
					settingsLink: (
						<Link
							href="/wp-admin/options-general.php"
							type="wp-admin"
						/>
					),
					supportedLanguagesLink: (
						<AppDocumentationLink
							context="get-started"
							href="https://support.google.com/merchants/answer/160637"
							linkId="supported-languages"
						/>
					),
				}
			) }
			<ExternalIcon />
		</Notice>
	);
};

/**
 * @fires gla_documentation_link_click with `{ context: "get-started", link_id: "supported-countries" }`
 */
const UnsupportedCountry = () => {
	const { name: countryName } = useStoreCountry();

	if ( ! countryName ) {
		return null;
	}

	return (
		<Notice
			className="gla-get-started-notice"
			isDismissible={ false }
			status="warning"
		>
			{ createInterpolateElement(
				__(
					'Your store’s country is <country />. This country is currently not supported by Google for WooCommerce. However, you can still choose to list your products in another supported country, if you are able to sell your products to customers there. <settingsLink>Change your store’s country here</settingsLink>. <supportedCountriesLink>Read more about supported countries</supportedCountriesLink>',
					'google-listings-and-ads'
				),
				{
					country: <strong>{ countryName }</strong>,
					settingsLink: (
						<Link
							href="/wp-admin/admin.php?page=wc-settings"
							type="wp-admin"
						/>
					),
					supportedCountriesLink: (
						<AppDocumentationLink
							context="get-started"
							href="https://support.google.com/merchants/answer/160637"
							linkId="supported-countries"
						/>
					),
				}
			) }
			<ExternalIcon />
		</Notice>
	);
};

export default function UnsupportedNotices() {
	const { mcSupportedLanguage, mcSupportedCountry } = glaData;

	return (
		<>
			{ ! mcSupportedLanguage && <UnsupportedLanguage /> }
			{ ! mcSupportedCountry && <UnsupportedCountry /> }
		</>
	);
}
