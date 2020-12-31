/**
 * Internal dependencies
 */
import './index.scss';

const VerticalGapLayout = ( props ) => {
	const { className = '', ...rest } = props;

	return (
		<div
			className={ `gla-vertical-gap-layout ${ className }` }
			{ ...rest }
		/>
	);
};

export default VerticalGapLayout;
