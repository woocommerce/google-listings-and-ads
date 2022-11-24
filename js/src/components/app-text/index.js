/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

/**
 * Renders a paragraph with some predefined styles based on the variant
 *
 *
 * ```jsx
 * <AppText variant="title-small">
 * 		My text
 * </AppText>
 * ```
 *
 * @param {Object} props Component props
 * @param {string} props.variant The variant to use for the text
 * @param {string} props.className Custom classname for this component
 * @param {JSX.Element} props.children The content for this component
 * @return {JSX.Element} The component
 */
const AppText = ( { variant, className, children } ) => {
	return (
		<p
			className={ classnames(
				'gla-app-text',
				`gla-app-text--${ variant }`,
				className
			) }
		>
			{ children }
		</p>
	);
};

export default AppText;
