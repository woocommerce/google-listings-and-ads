exports.handlers = {
	beforeParse( e ) {
		e.source = e.source.replace( /(import\(["'])\.?~\//g, `$1` );
	},
};
