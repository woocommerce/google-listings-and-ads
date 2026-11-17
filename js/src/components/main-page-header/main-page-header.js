/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Page } from '@wordpress/admin-ui';
import { Link } from '@woocommerce/components';

/**
 * Internal dependencies
 */
import useMenuEffect from '~/hooks/useMenuEffect';
import useMainTabs from '~/components/main-tab-nav/useMainTabs';
import './index.scss';

// Woo's `Link` pushes wc-admin links onto the history, so moving between sections
// does not reload the page, as with `MainTabNav`.
const PAGE_COMPONENTS = { link: Link };

/**
 * Page header from the WordPress Design System, with the plugin name and the links to
 * its main sections. It shows the same sections as `MainTabNav`.
 *
 * Only the header of the `@wordpress/admin-ui` `Page` is used. The page content stays
 * outside of it, inside the wc-admin layout.
 */
const MainPageHeader = () => {
	useMenuEffect();

	const { tabs, selectedKey } = useMainTabs();

	const navigation = {
		ariaLabel: __(
			'Google for WooCommerce sections',
			'google-listings-and-ads'
		),
		items: tabs.map( ( { title, href } ) => ( { label: title, href } ) ),
		currentHref: tabs.find( ( { key } ) => key === selectedKey )?.href,
	};

	return (
		<Page
			className="gla-main-page-header"
			title={ __( 'Google for WooCommerce', 'google-listings-and-ads' ) }
			navigation={ navigation }
			components={ PAGE_COMPONENTS }
			showSidebarToggle={ false }
		/>
	);
};

export default MainPageHeader;
