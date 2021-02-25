/**
 * External dependencies
 */
import { NavigableMenu } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { Link } from '@woocommerce/components';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './sub-nav.scss';

/**
 * Array of links, with `{ name, title, path }`.
 */
const tabs = [
	{
		name: 'programs',
		title: __( 'Programs', 'google-listings-and-ads' ),
		path: '%2Fgoogle%2Freports%2Fprograms',
	},
	{
		name: 'products',
		title: __( 'Products', 'google-listings-and-ads' ),
		path: '%2Fgoogle%2Freports%2Fproducts',
	},
];

/**
 * Navigation component that mimics the existing 3rd level navigation component written with jQuery.
 * Pre-configured to be used at "Reports" tab. Consists of "Programs" and "Products" links.
 * Relies on globa `.subsubsub` class.
 *
 * @param {Object} props
 * @param {string} props.initialName Name of the current tab.
 */
const SubNav = ( props ) => {
	const { initialName } = props;

	// Add bunch of spaces `' '` here and there to match jQuery implementation.
	return (
		<NavigableMenu
			role="tablist"
			orientation="horizontal"
			className="subsubsub gla-sub-nav"
		>
			{ tabs.map( ( tab, index ) => {
				const isCurrent = tab.name === initialName;

				return (
					<>
						<Link
							key={ tab.name }
							className={ classnames( { current: isCurrent } ) }
							tabIndex={ isCurrent ? null : -1 }
							id={ `${ tab.name }` }
							href={ 'admin.php?page=wc-admin&path=' + tab.path }
							role="tab"
							aria-selected={ isCurrent }
							aria-controls={ `${ tab.name }-view` }
							aria-current={ isCurrent ? 'page' : false }
						>
							{ tab.title + ' ' }
						</Link>
						{ index < tabs.length - 1 ? ' | ' : ' ' }
					</>
				);
			} ) }
		</NavigableMenu>
	);
};

export default SubNav;
