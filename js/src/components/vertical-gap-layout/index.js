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
