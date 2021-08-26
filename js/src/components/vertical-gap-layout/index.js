/**
 * External dependencies
 */
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import './index.scss';

// The `normal` gap is the default style, and no need to append any additional class name.
const sizeClassName = {
	normal: false,
	large: 'gla-vertical-gap-layout__large',
	overlap: 'gla-vertical-gap-layout__overlap',
};

/**
 * Renders a wrapper layout to make its children to be aligned vertically with a specified gap.
 *
 * @param {Object} props React props.
 * @param {string} [props.className] Additional CSS class name to be appended.
 * @param {'normal'|'large'|'overlap'} [props.size='normal'] Indicate the gap between children.
 *     'normal': A normal gap.
 *     'large': A large gap.
 *     'overlap': Overlap the back child on the front child with -1 pixel spacing.
 */
const VerticalGapLayout = ( props ) => {
	const { className, size = 'normal', ...rest } = props;

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
