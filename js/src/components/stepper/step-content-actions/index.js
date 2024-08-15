/**
 * Internal dependencies
 */
import Section from '.~/wcdl/section';
import VerticalGapLayout from '.~/components/vertical-gap-layout';

const StepContentActions = ( { children } ) => {
	return (
		<Section className="gla-step-content-actions">
			<VerticalGapLayout size="xlarge">{ children }</VerticalGapLayout>
		</Section>
	);
};

export default StepContentActions;
