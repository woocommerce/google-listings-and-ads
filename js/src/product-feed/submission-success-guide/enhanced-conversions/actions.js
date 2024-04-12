/**
 * External dependencies
 */
import { noop } from 'lodash';
import { Fragment } from '@wordpress/element';

/**
 * Internal dependencies
 */
import CTA from '.~/components/enhanced-conversion-tracking-settings/cta';

const Actions = ( { onModalClose = noop } ) => {
	return (
		<Fragment>
			<div className="gla-submission-success-guide__space_holder" />

			<CTA onConfirm={ onModalClose } />
		</Fragment>
	);
};

export default Actions;
