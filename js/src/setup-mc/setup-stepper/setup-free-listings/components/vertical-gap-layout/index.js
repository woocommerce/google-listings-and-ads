/**
 * Internal dependencies
 */
import './index.scss';

const VerticalGapLayout = ( props ) => {
	const { className = '', size = '', ...rest } = props;

	return (
		<div
			className={ `gla-vertical-gap-layout ${
				size === 'large' ? 'gla-vertical-gap-layout__large' : ''
			} ${ className }` }
			{ ...rest }
		/>
	);
};

export default VerticalGapLayout;
