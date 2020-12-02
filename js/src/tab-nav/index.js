/**
 * External dependencies
 */
import { NavigableMenu } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const tabs = [
	{
		name: 'dashboard',
		title: __( 'Dashboard', 'google-listings-and-ads' ),
		className: 'tab-dashboard',
		path: '%2Fgoogle%2Fdashboard',
	},
	{
		name: 'analytics',
		title: __( 'Analytics', 'google-listings-and-ads' ),
		className: 'tab-analytics',
		path: '%2Fgoogle%2Fanalytics',
	},
	{
		name: 'product-feed',
		title: __( 'Product Feed', 'google-listings-and-ads' ),
		className: 'tab-product-fee',
		path: '%2Fgoogle%2Fproduct-feed',
	},
	{
		name: 'settings',
		title: __( 'Settings', 'google-listings-and-ads' ),
		className: 'tab-settings',
		path: '%2Fgoogle%2Fsettings',
	},
];

const TabLink = ( { tabId, path, children, selected, ...rest } ) => {
	return (
		<Link
			role="tab"
			tabIndex={ selected ? null : -1 }
			aria-selected={ selected }
			id={ tabId }
			href={ 'admin.php?page=wc-admin&path=' + path }
			{ ...rest }
		>
			{ children }
		</Link>
	);
};

const marketingMenu = document.querySelector(
	'#toplevel_page_woocommerce-marketing'
);
const dashboardMenu = marketingMenu.querySelector(
	"a[href='admin.php?page=wc-admin&path=/google/dashboard']"
).parentElement;

const TabNav = ( props ) => {
	const { initialName } = props;
	const activeClass = 'is-active';

	useEffect( () => {
		// Highlight the wp-admin dashboard menu
		marketingMenu.classList.add( 'current', 'wp-has-current-submenu' );
		dashboardMenu.classList.add( 'current' );
	} );

	return (
		<div className="gla-tab-nav">
			<NavigableMenu
				role="tablist"
				orientation="horizontal"
				className="gla-tab-nav__tabs"
			>
				{ tabs.map( ( tab ) => (
					<TabLink
						className={ classnames(
							'components-button',
							'gla-tab-nav__tabs-item',
							tab.className,
							{
								[ activeClass ]: tab.name === initialName,
							}
						) }
						tabId={ `${ tab.name }` }
						aria-controls={ `${ tab.name }-view` }
						selected={ tab.name === initialName }
						key={ tab.name }
						path={ tab.path }
					>
						{ tab.title }
					</TabLink>
				) ) }
			</NavigableMenu>
		</div>
	);
};

export default TabNav;
