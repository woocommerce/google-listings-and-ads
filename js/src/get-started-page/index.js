/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { recordEvent } from '@woocommerce/tracks';

/**
 * Internal dependencies
 */
import './index.scss';

const GetStartedPage = ( { query } ) => {
	const onClick = () => {
		recordEvent( 'woogle_' + 'demo_button_clicked', {} );
	};

	return (
		<div>
			Hello World!
			<br />
			<Button isSecondary onClick={ onClick }>
				{ __( 'Tracks demo button', 'woogle' ) }
			</Button>
		</div>
	);
};

export default GetStartedPage;
