/**
 * Internal dependencies
 */
import './index.scss';

const StepContentFooter = ( props ) => {
	const { className = '', ...rest } = props;

	return (
		<div
			className={ `gla-step-content-footer ${ className }` }
			{ ...rest }
		/>
	);
};

export default StepContentFooter;
