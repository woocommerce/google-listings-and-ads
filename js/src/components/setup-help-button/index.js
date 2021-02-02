/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import GridiconHelpOutline from 'gridicons/dist/help-outline';
import classnames from 'classnames';

/**
 * Internal dependencies
 */
import AppIconButton from '../app-icon-button';
import './index.scss';

const SetupHelpButton = ( props ) => {
	const { className, ...rest } = props;

	return (
		<AppIconButton
			className={ classnames( 'gla-setup-help-button', className ) }
			icon={ <GridiconHelpOutline /> }
			text={ __( 'Help', 'google-listings-and-ads' ) }
			{ ...rest }
		/>
	);
};

export default SetupHelpButton;
