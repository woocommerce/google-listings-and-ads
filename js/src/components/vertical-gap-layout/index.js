/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

const sizeClassName = {
	large: 'gla-vertical-gap-layout__large',
	overlap: 'gla-vertical-gap-layout__overlap',
};

/**
 * Renders a wrapper layout to make its children to be aligned vertically with a specified gap.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {''|'large'|'overlap'} [props.size=''] Indicate the gap between children. The empty string as default value is a normal gap.
 *     'large': A larger gap.
 *     'overlap': Overlap the back child on the front child with -1 pixel spacing.
 */
const VerticalGapLayout = ( props ) => {
	const { className, size = '', ...rest } = props;

	return (
		<div
			className={ classnames(
				'gla-vertical-gap-layout',
				sizeClassName[ size ],
				className
			) }
			{ ...rest }
		/>
	);
};

export default VerticalGapLayout;
