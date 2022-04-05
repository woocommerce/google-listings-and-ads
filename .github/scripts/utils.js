/*
 * The workflow commands of GitHub Actions
 * - https://docs.github.com/en/actions/using-workflows/workflow-commands-for-github-actions#setting-an-error-message
 * - https://docs.github.com/en/actions/using-workflows/workflow-commands-for-github-actions#setting-a-warning-message
 * - https://docs.github.com/en/actions/using-workflows/workflow-commands-for-github-actions#grouping-log-lines
 */
function toAnnotationCommand( model ) {
	const regex = /([ ,]?\w+=)?\{(\w+)\}/g;
	const template =
		'::{command} file={filePath},line={line},endLine={endLine},col={column},endColumn={endColumn}::{message}';

	return template.replace( regex, ( _, paramGroup = '', key ) => {
		if ( model.hasOwnProperty( key ) ) {
			return paramGroup + model[ key ];
		}
		return '';
	} );
}

function annotateToGitHubActions( models ) {
	if ( models.length === 0 ) {
		return;
	}

	// Wrap it as an expandable logs group in the GitHub Actions.
	const groupingModels = [
		{ command: 'group', message: 'Annotation commands' },
		...models,
		{ command: 'endgroup' },
	];

	groupingModels
		.map( toAnnotationCommand )
		.forEach( ( command ) => console.log( command ) ); // eslint-disable-line no-console
}

module.exports = {
	annotateToGitHubActions,
};
