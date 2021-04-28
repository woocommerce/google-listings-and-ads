/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import AppSpinner from '.~/components/app-spinner';

/**
 * Component to conditionally render a section.
 * If `show` is set to
 *  - `true` returns given children
 *  - `false` returns null
 *  - `null` - unspecified, render section preloader
 *
 * @param {Object} props React props
 * @param {boolean | null} props.show Flag to indicate whether the element should be shown.
 * @param {Array<JSX.Element>} props.children Content to be rendered.
 * @return {Array<JSX.Element> | null} Children, preloader section, or null.
 */
const ConditionalSection = ( { show, children } ) => {
	switch ( show ) {
		case true:
			return children;
		case false:
			return null;
		default:
			return (
				<Section>
					<AppSpinner />
				</Section>
			);
	}
};

export default ConditionalSection;
