/**
 * External dependencies
 */
import { NavigableMenu } from '@wordpress/components';
import { Link } from '@woocommerce/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

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

const AppTabNav = ( props ) => {
	const { selectedKey, tabs } = props;

	return (
		<div className="app-tab-nav">
			<NavigableMenu
				role="tablist"
				orientation="horizontal"
				className="app-tab-nav__tabs"
			>
				{ tabs.map( ( tab ) => (
					<TabLink
						className={ classnames(
							'components-button',
							'app-tab-nav__tabs-item',
							{
								'is-active': tab.key === selectedKey,
							}
						) }
						tabId={ `${ tab.key }` }
						aria-controls={ `${ tab.key }-view` }
						selected={ tab.key === selectedKey }
						key={ tab.key }
						path={ tab.path }
					>
						{ tab.title }
					</TabLink>
				) ) }
			</NavigableMenu>
		</div>
	);
};

export default AppTabNav;
