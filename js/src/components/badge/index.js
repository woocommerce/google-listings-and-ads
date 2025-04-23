/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Badge component.
 *
 * A reusable badge component that displays content with optional styling based on intent.
 * This is a temporary port of the Badge component from the Gutenberg repository:
 * https://github.com/WordPress/gutenberg/tree/trunk/packages/components/src/badge
 * until that package is made public.
 *
 * @param {Object} props - The component props.
 * @param {string} [props.className] - Additional CSS classes to apply to the badge.
 * @param {string} [props.intent='default'] - The intent of the badge, which determines its styling.
 *                                            Possible values include 'default', 'success', 'info', 'warning ,'error', etc.
 * @param {JSX.Element} props.children - The content to display inside the badge.
 * @param {Object} [props.props] - Additional props to spread onto the badge element.
 * @return {JSX.Element} The rendered badge component.
 */
function Badge( { className, intent = 'default', children, ...props } ) {
	return (
		<span
			className={ classnames( 'gla-badge', className, {
				[ `is-${ intent }` ]: intent,
			} ) }
			{ ...props }
		>
			<span className="gla-badge__content">{ children }</span>
		</span>
	);
}

export default Badge;
