/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import Subsection from '.~/wcdl/subsection';

export default function AccountSubsection( {
	title,
	info,
	children = __( 'Required', 'google-listings-and-ads' ),
} ) {
	return (
		<Subsection>
			<Subsection.Title>{ title }</Subsection.Title>
			<Subsection.Body>{ info }</Subsection.Body>
			<Subsection.HelperText>{ children }</Subsection.HelperText>
		</Subsection>
	);
}
