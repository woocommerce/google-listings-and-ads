const { CLIEngine } = require( 'eslint' );
const { annotateToGitHubActions } = require( './utils' );

function toAnnotationModels( errorMetadata ) {
	const truncationPath = process.cwd();
	const models = [];

	errorMetadata.forEach( ( file ) => {
		const filePath = file.filePath.replace( truncationPath, '.' );

		file.messages.forEach( ( lintError ) => {
			const { severity, ruleId, message } = lintError;

			// About the `severity` value: https://eslint.org/docs/user-guide/formatters/#json
			models.push( {
				...lintError,
				command: severity === 2 ? 'error' : 'warning',
				filePath,
				message: `[${ ruleId }] ${ message }`,
			} );
		} );
	} );

	return models;
}

// Ref: https://eslint.org/docs/developer-guide/working-with-custom-formatters
module.exports = function ( results, context ) {
	const errorMetadata = results.filter(
		( { errorCount, warningCount } ) => errorCount || warningCount
	);
	const models = toAnnotationModels( errorMetadata );
	annotateToGitHubActions( models );

	// Still outputting the original CLI logs by default format.
	const format = CLIEngine.getFormatter( 'stylish' );
	return format( results, context );
};
