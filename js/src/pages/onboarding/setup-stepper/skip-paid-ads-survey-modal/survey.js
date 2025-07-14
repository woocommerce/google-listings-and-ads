/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { CheckboxControl } from '@wordpress/components';

/**
 * Internal dependencies
 */
import { OPTIONS } from './constants';
import { useAdaptiveFormContext } from '~/components/adaptive-form';
import AppInputControl from '~/components/app-input-control';
import './survey.scss';

/**
 * Renders a survey component for the skip paid ads modal.
 * This component allows users to select reasons for skipping the paid ads setup
 * and provides text input fields for additional comments on certain options.
 */
const Survey = () => {
	const { getInputProps } = useAdaptiveFormContext();

	return (
		<div className="gla-skip-paid-ads-survey-modal__survey">
			<h3 className="gla-skip-paid-ads-survey-modal__survey-title">
				{ __(
					'Why do you want to skip ads?',
					'google-listings-and-ads'
				) }
			</h3>

			<ul>
				{ OPTIONS.map( ( option ) => {
					const inputProps = getInputProps( option.value );

					return (
						<li key={ option.value }>
							<CheckboxControl
								label={ option.label }
								value={ option.value }
								{ ...inputProps }
							/>

							{ option.hasTextInput && inputProps.checked && (
								<AppInputControl
									{ ...getInputProps(
										`${ option.value }_text`
									) }
								/>
							) }
						</li>
					);
				} ) }
			</ul>
		</div>
	);
};

export default Survey;
