/**
 * Internal dependencies
 */
import Subsection from '.~/wcdl/subsection';

export default function AccountSubsection( { title, info, helperContent } ) {
	return (
		<Subsection>
			<Subsection.Title>{ title }</Subsection.Title>
			<Subsection.Body>{ info }</Subsection.Body>
			<Subsection.HelperText>{ helperContent }</Subsection.HelperText>
		</Subsection>
	);
}
