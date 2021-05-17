/**
 * Internal dependencies
 */
import AppButton from '.~/components/app-button';
import './index.scss';

const AppIconButton = ( props ) => {
	const { icon, text, className = '', ...rest } = props;

	return (
		<AppButton className={ `app-icon-button ${ className }` } { ...rest }>
			<div>{ icon }</div>
			{ text }
		</AppButton>
	);
};

export default AppIconButton;
