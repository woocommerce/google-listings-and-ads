/**
 * External dependencies
 */
import { Button } from '@wordpress/components';
import { Link } from '@woocommerce/components';

/**
 * Renders a <Button> wrapped by <Link> with enhanced disabled behavior.
 * Use this component when you need a navigation <Link> with <Button> style and the disabled function.
 *
 * ## Usage
 *
 * ```jsx
 * <ButtonLink
 * 	isPrimary
 * 	disabled={ disabled }
 * 	href={ getNewPath( {}, '/google/dashboard' ) }
 * >
 * ```
 *
 * @see module:@wordpress/components#Button
 * @see module:@woocommerce/components#Link
 *
 * @param {Object} props Props to be forwarded to Button or Link. The rest props that do not belong to Link will be forwarded to Button.
 * @param {string} props.href `href` props to be forwarded to Link.
 * @param {boolean} props.disabled Indicate both Button and Link are disabled or not.
 * @param {string} [props.type=wc-admin] `type` props to be forwarded to Link.
 */
export default function ButtonLink( props ) {
	const { href, disabled, type, ...restProps } = props;
	const linkHref = disabled ? null : href;
	return (
		<Link
			href={ linkHref }
			type={ type }
			tabIndex={ -1 }
			aria-disabled={ disabled }
		>
			<Button
				disabled={ disabled }
				aria-disabled={ disabled }
				{ ...restProps }
			/>
		</Link>
	);
}
