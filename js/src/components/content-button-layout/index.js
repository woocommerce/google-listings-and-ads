/**
 * Internal dependencies
 */
import './index.scss';

const ContentButtonLayout = ( props ) => {
	const { className, ...rest } = props;

	return (
		<div
			className={ `gla-content-button-layout ${ className }` }
			{ ...rest }
		/>
	);
};

export default ContentButtonLayout;
