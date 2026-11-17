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
import useMainTabs from './useMainTabs';
import useDocumentTitle from './useDocumentTitle';
import './index.scss';

// Woo's `Link` pushes wc-admin links onto the history, so moving between sections
// does not reload the page.
const PAGE_COMPONENTS = { link: Link };

/**
 * Page header from the WordPress Design System, with the plugin name, the links to
 * its main sections and the page actions. Render it first on each main page.
 *
 * Only the header of the `@wordpress/admin-ui` `Page` is used. The page content stays
 * outside of it, inside the wc-admin layout. The pages that render it turn off the
 * wc-admin header (see `js/src/index.js`), so this is the only page header.
 *
 * @param {Object} props React props.
 * @param {JSX.Element} [props.actions] Page actions, shown at the end of the header.
 */
const MainPageHeader = ( { actions } ) => {
	useMenuEffect();

	const { tabs, selectedKey } = useMainTabs();
	const selectedTab = tabs.find( ( { key } ) => key === selectedKey );

	useDocumentTitle( selectedTab?.title );

	const navigation = {
		ariaLabel: __(
			'Google for WooCommerce sections',
			'google-listings-and-ads'
		),
		items: tabs.map( ( { title, href } ) => ( { label: title, href } ) ),
		currentHref: selectedTab?.href,
	};

	return (
		<Page
			className="gla-main-page-header"
			title={ __( 'Google for WooCommerce', 'google-listings-and-ads' ) }
			navigation={ navigation }
			actions={ actions }
			components={ PAGE_COMPONENTS }
			showSidebarToggle={ false }
		/>
	);
};

export default MainPageHeader;
