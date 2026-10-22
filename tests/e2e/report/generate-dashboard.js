#!/usr/bin/env node
/* eslint-disable no-console -- CLI script, console is its output. */
/**
 * Reads the Playwright JSON reporter output (tests/e2e/test-results/results.json)
 * and writes a self-contained HTML dashboard (tests/e2e/test-results/dashboard.html):
 * overall pass/fail counts and a by-area breakdown up top, then failing cases with
 * their error details and screenshots (click to enlarge), the full pass/fail case
 * table, and a "reproducing this run" command reference at the bottom.
 */

const fs = require( 'fs' );
const path = require( 'path' );

const RESULTS_PATH = path.join( __dirname, '../test-results/results.json' );
const OUTPUT_PATH = path.join( __dirname, '../test-results/dashboard.html' );
// JSON reporter attachment paths are written relative to this directory when
// not already absolute.
const PLUGIN_ROOT = path.join( __dirname, '..', '..', '..' );

function readResults() {
	if ( ! fs.existsSync( RESULTS_PATH ) ) {
		console.error(
			`No results file at ${ RESULTS_PATH }. Run "npm run test:e2e" first.`
		);
		process.exit( 1 );
	}
	return JSON.parse( fs.readFileSync( RESULTS_PATH, 'utf8' ) );
}

function specFileTitle( fileTitle ) {
	return fileTitle.replace( /\.test\.js$/, '' );
}

// test.status is Playwright's own outcome classification for the JSON
// reporter: 'skipped' | 'expected' | 'unexpected' | 'flaky' (see
// JSONReportTest in playwright's testReporter.d.ts) -- not a raw pass/fail
// value, so it's used as-is rather than compared against expectedStatus.
function statusOf( spec ) {
	const statuses = spec.tests.map( ( t ) => t.status );
	if ( statuses.includes( 'unexpected' ) ) {
		return 'failed';
	}
	if ( statuses.includes( 'flaky' ) ) {
		return 'flaky';
	}
	if ( statuses.every( ( s ) => s === 'skipped' ) ) {
		return 'skipped';
	}
	return 'passed';
}

const ANSI_RE = /\x1b\[[0-9;]*m/g;

function stripAnsi( str ) {
	return str.replace( ANSI_RE, '' );
}

// Playwright's expect() failures print a fairly consistent shape (matcher
// name, the locator involved, then "Expected ...:" / "Received ...:" lines).
// Pull those out so the report can show a short summary instead of the full
// call log and stack trace by default; anything that doesn't match this
// shape (thrown errors, strict-mode violations, timeouts) just falls back to
// showing the raw message's own first line.
function parseFailure( message ) {
	const clean = stripAnsi( message ).trim();
	const matcherMatch = clean.match( /expect\([^)]*\)\.(\w+)\(/ );
	const locatorMatch = clean.match( /^Locator:\s*(.+)$/m );
	const expectedMatch = clean.match( /^Expected[^:]*:\s*(.+)$/m );
	const receivedMatch = clean.match( /^Received[^:]*:\s*(.+)$/m );
	const firstLine = clean.split( '\n' )[ 0 ].replace( /^Error:\s*/, '' );
	return {
		raw: clean,
		summary: firstLine,
		matcher: matcherMatch ? matcherMatch[ 1 ] : null,
		locator: locatorMatch ? locatorMatch[ 1 ] : null,
		expected: expectedMatch ? expectedMatch[ 1 ] : null,
		received: receivedMatch ? receivedMatch[ 1 ] : null,
	};
}

function collectErrors( spec ) {
	const errors = [];
	for ( const test of spec.tests ) {
		for ( const result of test.results ) {
			for ( const err of result.errors || [] ) {
				if ( err.message ) {
					errors.push( parseFailure( err.message ) );
				}
			}
		}
	}
	return errors;
}

function collectScreenshots( spec ) {
	const shots = [];
	for ( const test of spec.tests ) {
		test.results.forEach( ( result, attemptIndex ) => {
			for ( const attachment of result.attachments || [] ) {
				if (
					! attachment.contentType ||
					! attachment.contentType.startsWith( 'image/' )
				) {
					continue;
				}
				const dataUri = readAttachmentAsDataUri( attachment );
				if ( dataUri ) {
					shots.push( {
						dataUri,
						project: test.projectName,
						attempt: attemptIndex + 1,
						retried: test.results.length > 1,
					} );
				}
			}
		} );
	}
	return shots;
}

function readAttachmentAsDataUri( attachment ) {
	try {
		let buffer;
		if ( attachment.body ) {
			buffer = Buffer.from( attachment.body, 'base64' );
		} else if ( attachment.path ) {
			const filePath = path.isAbsolute( attachment.path )
				? attachment.path
				: path.join( PLUGIN_ROOT, attachment.path );
			buffer = fs.readFileSync( filePath );
		} else {
			return null;
		}
		return `data:${ attachment.contentType };base64,${ buffer.toString(
			'base64'
		) }`;
	} catch {
		return null;
	}
}

function projectsOf( spec ) {
	return [ ...new Set( spec.tests.map( ( t ) => t.projectName ) ) ];
}

function durationOf( spec ) {
	let total = 0;
	for ( const test of spec.tests ) {
		for ( const result of test.results ) {
			total += result.duration || 0;
		}
	}
	return total;
}

function walkSuite( suite, area, out ) {
	for ( const spec of suite.specs || [] ) {
		out.push( {
			area,
			title: spec.title,
			status: statusOf( spec ),
			projects: projectsOf( spec ),
			duration: durationOf( spec ),
			errors: collectErrors( spec ),
			screenshots: collectScreenshots( spec ),
		} );
	}
	for ( const child of suite.suites || [] ) {
		walkSuite( child, area, out );
	}
}

function collectCases( results ) {
	const cases = [];
	for ( const file of results.suites ) {
		walkSuite( file, specFileTitle( file.title ), cases );
	}
	return cases;
}

function byArea( cases ) {
	const areas = new Map();
	for ( const c of cases ) {
		if ( ! areas.has( c.area ) ) {
			areas.set( c.area, [] );
		}
		areas.get( c.area ).push( c );
	}
	return areas;
}

function esc( str ) {
	return String( str ).replace(
		/[&<>"']/g,
		( ch ) =>
			( {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#39;',
			} )[ ch ]
	);
}

function fmtDuration( ms ) {
	if ( ms < 1000 ) {
		return `${ Math.round( ms ) }ms`;
	}
	return `${ ( ms / 1000 ).toFixed( 1 ) }s`;
}

function fmtTotalDuration( ms ) {
	const totalSeconds = Math.round( ms / 1000 );
	const minutes = Math.floor( totalSeconds / 60 );
	const seconds = totalSeconds % 60;
	if ( minutes === 0 ) {
		return `${ seconds }s`;
	}
	return `${ minutes }m ${ seconds }s`;
}

function renderShots( c, sizeClass ) {
	if ( ! c.screenshots.length ) {
		return '';
	}
	return `<div class="shots">${ c.screenshots
		.map(
			( s ) => `
		<button type="button" class="shot-thumb ${ sizeClass }" data-full="${
			s.dataUri
		}" aria-label="Screenshot: ${ esc( c.title ) } (${ esc( s.project ) }${
			s.retried ? `, attempt ${ s.attempt }` : ''
		})">
			<img src="${ s.dataUri }" alt="" loading="lazy">
			<span class="shot-caption">${ esc( s.project ) }${
				s.retried ? ` · attempt ${ s.attempt }` : ''
			}</span>
		</button>`
		)
		.join( '' ) }</div>`;
}

function renderFailureError( e ) {
	const rows = [];
	if ( e.expected !== null ) {
		rows.push(
			`<div class="expect-row"><span class="expect-label">Expected</span><code>${ esc(
				e.expected
			) }</code></div>`
		);
	}
	if ( e.received !== null ) {
		rows.push(
			`<div class="expect-row"><span class="expect-label">Received</span><code>${ esc(
				e.received
			) }</code></div>`
		);
	}
	const expectation = rows.length
		? `<div class="expectation">${ rows.join( '' ) }${
				e.locator
					? `<div class="expect-locator">on <code>${ esc(
							e.locator
					  ) }</code></div>`
					: ''
		  }</div>`
		: `<p class="expect-summary">${ esc( e.summary ) }</p>`;
	return `
		<div class="error-block">
			${ expectation }
			<details class="raw-error">
				<summary>Full error log</summary>
				<pre>${ esc( e.raw ) }</pre>
			</details>
		</div>`;
}

function renderFailureCard( c ) {
	const errorBlock = c.errors.map( renderFailureError ).join( '' );
	return `
	<article class="failure-card">
		<div class="failure-head">
			<span class="failure-area">${ esc( c.area ) }</span>
		</div>
		<h3 class="failure-title">${ esc( c.title ) }</h3>
		<div class="failure-meta">${ c.projects
			.map( esc )
			.join( ', ' ) } · ${ fmtDuration( c.duration ) }</div>
		${ errorBlock }
		${ renderShots( c, 'shot-thumb-lg' ) }
	</article>`;
}

function renderCaseRow( c ) {
	const badgeClass = `badge-${ c.status }`;
	return `
		<tr class="case-row status-${ c.status }">
			<td class="cell-status"><span class="badge ${ badgeClass }">${
				c.status
			}</span></td>
			<td class="cell-title">${ esc( c.title ) }</td>
			<td class="cell-projects">${ c.projects.map( esc ).join( ', ' ) }</td>
			<td class="cell-duration">${ fmtDuration( c.duration ) }</td>
		</tr>`;
}

function renderAreaSummaryCard( area, cases ) {
	const passed = cases.filter( ( c ) => c.status === 'passed' ).length;
	const failed = cases.filter( ( c ) => c.status === 'failed' ).length;
	const flaky = cases.filter( ( c ) => c.status === 'flaky' ).length;
	const skipped = cases.filter( ( c ) => c.status === 'skipped' ).length;
	const total = cases.length;
	const pct = total ? Math.round( ( passed / total ) * 100 ) : 0;
	const statusLine = [
		`${ passed }/${ total } passing`,
		failed ? `${ failed } failing` : null,
		flaky ? `${ flaky } flaky` : null,
		skipped ? `${ skipped } skipped` : null,
	]
		.filter( Boolean )
		.join( ' · ' );

	return `
	<div class="area-card">
		<h3>${ esc( area ) }</h3>
		<div class="area-pct ${ failed ? 'has-failures' : '' }">${ pct }%</div>
		<div class="area-line">${ statusLine }</div>
	</div>`;
}

function renderAreaTable( area, cases ) {
	const rows = cases.map( renderCaseRow ).join( '' );
	return `
	<section class="area">
		<h3 class="area-table-heading">${ esc( area ) }</h3>
		<table class="case-table">
			<thead>
				<tr>
					<th class="cell-status">Result</th>
					<th class="cell-title">Title</th>
					<th class="cell-projects">Ran on</th>
					<th class="cell-duration">Duration</th>
				</tr>
			</thead>
			<tbody>${ rows }</tbody>
		</table>
	</section>`;
}

function renderReproSection( exampleCase ) {
	const config = '--config=tests/e2e/config/playwright.config.js';
	const specPath = exampleCase
		? `tests/e2e/specs/${ exampleCase.area }.test.js`
		: 'tests/e2e/specs/get-started.test.js';
	const oneFile = `npx playwright test ${ config } ${ specPath }`;
	const oneCase = `npx playwright test ${ config } --grep "${
		exampleCase
			? exampleCase.title.replace( /"/g, '\\"' )
			: 'text in a test title'
	}"`;
	const lines = [
		[ 'npm install', 'installs deps' ],
		[ 'npx playwright install chromium', 'installs the test browser' ],
		[ 'npm run build', 'builds the plugin assets under test' ],
		[ 'npm run wp-env:up', 'boots the wp-env e2e environment' ],
		[ 'npm run test:e2e', 'all cases, chromium' ],
		[ oneFile, 'one spec file' ],
		[ oneCase, 'one case' ],
		[ 'npm run test:e2e:report', 'regenerate this dashboard' ],
	];
	const rows = lines
		.map( ( [ cmd, note ] ) => `${ esc( cmd ) }  # ${ esc( note ) }` )
		.join( '\n' );
	return `
	<section class="block" id="reproduce">
		<h2>Reproducing this run</h2>
		<p class="block-sub">Runs against the disposable wp-env e2e site at localhost:8889, never a site with real data.</p>
		<pre class="repro">${ rows }</pre>
	</section>`;
}

function render( results ) {
	const cases = collectCases( results );
	const total = cases.length;
	const passed = cases.filter( ( c ) => c.status === 'passed' ).length;
	const failed = cases.filter( ( c ) => c.status === 'failed' ).length;
	const flaky = cases.filter( ( c ) => c.status === 'flaky' ).length;
	const skipped = cases.filter( ( c ) => c.status === 'skipped' ).length;
	const passPct = total ? ( passed / total ) * 100 : 0;
	const flakyPct = total ? ( flaky / total ) * 100 : 0;
	const failedPct = total ? ( failed / total ) * 100 : 0;
	const skippedPct = total ? ( skipped / total ) * 100 : 0;

	const areas = byArea( cases );

	const areaSummaryCards = [ ...areas.entries() ]
		.map( ( [ area, areaCases ] ) =>
			renderAreaSummaryCard( area, areaCases )
		)
		.join( '' );

	const failedCases = cases.filter( ( c ) => c.status === 'failed' );
	const failuresSection = failedCases.length
		? failedCases.map( renderFailureCard ).join( '' )
		: '<p class="all-clear">No failing cases in this run.</p>';

	const areaTables = [ ...areas.entries() ]
		.map( ( [ area, areaCases ] ) => renderAreaTable( area, areaCases ) )
		.join( '' );

	const reproSection = renderReproSection( failedCases[ 0 ] || cases[ 0 ] );

	const startTime = new Date( results.stats.startTime );
	const runAt = startTime.toLocaleString( 'en-US', {
		dateStyle: 'medium',
		timeStyle: 'short',
	} );

	const gradientStops = [];
	let cursor = 0;
	const seg = ( amount, color ) => {
		if ( amount <= 0 ) {
			return;
		}
		gradientStops.push( `${ color } ${ cursor }% ${ cursor + amount }%` );
		cursor += amount;
	};
	seg( passPct, 'var(--c-pass)' );
	seg( flakyPct, 'var(--c-flaky)' );
	seg( failedPct, 'var(--c-fail)' );
	seg( skippedPct, 'var(--c-skip)' );
	const donutStyle = gradientStops.length
		? `conic-gradient(${ gradientStops.join( ', ' ) })`
		: 'var(--border)';

	return `<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Google for WooCommerce E2E Results</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<style>
:root {
	--bg: #f7f7f5;
	--surface: #ffffff;
	--border: #e5e3df;
	--text: #1f1e1c;
	--text-muted: #6b6a66;
	--c-pass: #2f9e5a;
	--c-fail: #d64545;
	--c-flaky: #d6a52f;
	--c-skip: #9b9a95;
}
@media (prefers-color-scheme: dark) {
	:root:not([data-theme="light"]) {
		--bg: #171614;
		--surface: #201f1c;
		--border: #37352f;
		--text: #f2f1ee;
		--text-muted: #a7a59f;
	}
}
:root[data-theme="dark"] {
	--bg: #171614;
	--surface: #201f1c;
	--border: #37352f;
	--text: #f2f1ee;
	--text-muted: #a7a59f;
}
* { box-sizing: border-box; }
body {
	margin: 0;
	background: var(--bg);
	color: var(--text);
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif;
	line-height: 1.5;
}
.wrap { max-width: 980px; margin: 0 auto; padding: 40px 24px 80px; }
header.top { margin-bottom: 24px; }
header.top h1 { font-size: 1.6rem; margin: 0 0 4px; }
header.top .meta { color: var(--text-muted); font-size: 0.9rem; }
nav.jump { display: flex; gap: 16px; margin-bottom: 32px; font-size: 0.85rem; }
nav.jump a { color: var(--text-muted); text-decoration: none; border-bottom: 1px solid var(--border); }
nav.jump a:hover { color: var(--text); border-color: var(--text-muted); }
.summary {
	display: flex;
	gap: 32px;
	align-items: center;
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 12px;
	padding: 24px;
	margin-bottom: 24px;
	flex-wrap: wrap;
}
.donut {
	width: 120px;
	height: 120px;
	border-radius: 50%;
	background: ${ donutStyle };
	flex-shrink: 0;
	position: relative;
}
.donut::after {
	content: "";
	position: absolute;
	inset: 18px;
	border-radius: 50%;
	background: var(--surface);
}
.stats { display: flex; gap: 28px; flex-wrap: wrap; }
.stat-item { min-width: 90px; }
.stat-num { font-size: 1.7rem; font-weight: 600; line-height: 1.1; }
.stat-label { font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.03em; }
.stat-item.pass .stat-num { color: var(--c-pass); }
.stat-item.fail .stat-num { color: var(--c-fail); }
.stat-item.flaky .stat-num { color: var(--c-flaky); }
.stat-item.skip .stat-num { color: var(--c-skip); }
.area-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
	gap: 12px;
	margin-bottom: 40px;
}
.area-card {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 10px;
	padding: 14px 16px;
}
.area-card h3 { margin: 0 0 6px; font-size: 0.9rem; }
.area-pct { font-size: 1.3rem; font-weight: 600; }
.area-pct.has-failures { color: var(--c-fail); }
.area-line { color: var(--text-muted); font-size: 0.8rem; margin-top: 2px; }
section.block { margin-bottom: 40px; }
section.block > h2 { font-size: 1.15rem; margin: 0 0 4px; }
section.block > .block-sub { color: var(--text-muted); font-size: 0.85rem; margin: 0 0 16px; }
.all-clear {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 10px;
	padding: 20px;
	color: var(--c-pass);
	font-weight: 600;
	margin: 0;
}
.failure-card {
	background: var(--surface);
	border: 1px solid var(--border);
	border-left: 4px solid var(--c-fail);
	border-radius: 10px;
	padding: 18px 20px;
	margin-bottom: 16px;
}
.failure-head { display: flex; align-items: center; gap: 8px; margin-bottom: 6px; }
.failure-area {
	font-size: 0.72rem;
	text-transform: uppercase;
	letter-spacing: 0.03em;
	color: var(--text-muted);
}
.failure-title { margin: 0 0 4px; font-size: 1.02rem; }
.failure-meta { color: var(--text-muted); font-size: 0.82rem; margin-bottom: 10px; }
.area { margin-bottom: 28px; }
.area-table-heading { font-size: 1.05rem; margin: 0 0 8px; }
.case-table {
	width: 100%;
	border-collapse: collapse;
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 10px;
	overflow: hidden;
	font-size: 0.88rem;
}
.case-table th {
	text-align: left;
	font-size: 0.75rem;
	text-transform: uppercase;
	letter-spacing: 0.03em;
	color: var(--text-muted);
	padding: 10px 12px;
	border-bottom: 1px solid var(--border);
}
.case-table td { padding: 10px 12px; border-bottom: 1px solid var(--border); vertical-align: top; }
.case-table tr:last-child td { border-bottom: none; }
.cell-status { white-space: nowrap; }
.cell-projects { white-space: nowrap; color: var(--text-muted); }
.cell-duration { white-space: nowrap; text-align: right; color: var(--text-muted); font-variant-numeric: tabular-nums; }
.badge {
	display: inline-block;
	padding: 2px 8px;
	border-radius: 999px;
	font-size: 0.72rem;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.02em;
}
.badge-passed { background: color-mix(in srgb, var(--c-pass) 18%, transparent); color: var(--c-pass); }
.badge-failed { background: color-mix(in srgb, var(--c-fail) 18%, transparent); color: var(--c-fail); }
.badge-flaky { background: color-mix(in srgb, var(--c-flaky) 18%, transparent); color: var(--c-flaky); }
.badge-skipped { background: color-mix(in srgb, var(--c-skip) 18%, transparent); color: var(--c-skip); }
tr.status-failed { background: color-mix(in srgb, var(--c-fail) 6%, transparent); }
tr.status-flaky { background: color-mix(in srgb, var(--c-flaky) 6%, transparent); }
.error-block { margin-top: 4px; }
.expectation {
	display: flex;
	flex-direction: column;
	gap: 3px;
	margin-bottom: 6px;
}
.expect-row { display: flex; gap: 8px; align-items: baseline; font-size: 0.85rem; }
.expect-label {
	color: var(--text-muted);
	font-size: 0.72rem;
	text-transform: uppercase;
	letter-spacing: 0.03em;
	width: 64px;
	flex-shrink: 0;
}
.expect-row code, .expect-locator code {
	font-size: 0.82rem;
	background: var(--bg);
	border: 1px solid var(--border);
	border-radius: 4px;
	padding: 1px 6px;
	word-break: break-word;
}
.expect-locator { font-size: 0.78rem; color: var(--text-muted); }
.expect-summary { margin: 0 0 6px; font-size: 0.85rem; color: var(--c-fail); }
.raw-error summary {
	cursor: pointer;
	font-size: 0.78rem;
	color: var(--text-muted);
	user-select: none;
}
.raw-error summary:hover { color: var(--text); }
.raw-error pre {
	background: var(--bg);
	border: 1px solid var(--border);
	border-radius: 6px;
	padding: 10px;
	font-size: 0.78rem;
	overflow-x: auto;
	white-space: pre-wrap;
	word-break: break-word;
	color: var(--c-fail);
	margin: 6px 0 0;
}
.repro {
	background: var(--surface);
	border: 1px solid var(--border);
	border-radius: 10px;
	padding: 16px 18px;
	font-size: 0.85rem;
	overflow-x: auto;
	white-space: pre;
	margin: 0;
}
.shots { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 10px; }
.shot-thumb {
	display: flex;
	flex-direction: column;
	align-items: stretch;
	padding: 0;
	background: var(--bg);
	border: 1px solid var(--border);
	border-radius: 6px;
	overflow: hidden;
	cursor: zoom-in;
	font: inherit;
	color: inherit;
}
.shot-thumb img {
	display: block;
	width: 100%;
	object-fit: cover;
	object-position: top;
	background: var(--surface);
}
.shot-thumb.shot-thumb-lg { width: 220px; }
.shot-thumb.shot-thumb-lg img { height: 140px; }
.shot-thumb:hover img { opacity: 0.85; }
.shot-caption {
	font-size: 0.68rem;
	color: var(--text-muted);
	padding: 4px 6px;
	white-space: nowrap;
	overflow: hidden;
	text-overflow: ellipsis;
}
.lightbox {
	position: fixed;
	inset: 0;
	background: color-mix(in srgb, black 78%, transparent);
	display: flex;
	align-items: center;
	justify-content: center;
	padding: 40px;
	cursor: zoom-out;
	z-index: 100;
}
.lightbox[hidden] { display: none; }
.lightbox img {
	max-width: 100%;
	max-height: 100%;
	border-radius: 8px;
	box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
}
</style>
</head>
<body>
<div class="wrap">
	<header class="top">
		<h1>Google for WooCommerce E2E Results</h1>
		<div class="meta">Run started ${ esc( runAt ) } · ${ fmtTotalDuration(
			results.stats.duration
		) } · ${ total } cases</div>
	</header>

	<nav class="jump">
		<a href="#failures">Failing cases (${ failed })</a>
		<a href="#results">Full results</a>
		<a href="#reproduce">Reproducing this run</a>
	</nav>

	<div class="summary" id="summary">
		<div class="donut" role="img" aria-label="${ passed } of ${ total } passed"></div>
		<div class="stats">
			<div class="stat-item pass"><div class="stat-num">${ passed }</div><div class="stat-label">Passed</div></div>
			<div class="stat-item fail"><div class="stat-num">${ failed }</div><div class="stat-label">Failed</div></div>
			<div class="stat-item flaky"><div class="stat-num">${ flaky }</div><div class="stat-label">Flaky</div></div>
			<div class="stat-item skip"><div class="stat-num">${ skipped }</div><div class="stat-label">Skipped</div></div>
		</div>
	</div>

	<div class="area-grid">${ areaSummaryCards }</div>

	<section class="block" id="failures">
		<h2>Failing cases</h2>
		<p class="block-sub">Each with its error and the screenshot captured at the point of failure.</p>
		${ failuresSection }
	</section>

	<section class="block" id="results">
		<h2>Full results</h2>
		<p class="block-sub">Every case in this run, passed or failed.</p>
		${ areaTables }
	</section>

	${ reproSection }
</div>

<div class="lightbox" id="lightbox" hidden>
	<img id="lightbox-img" src="" alt="">
</div>
<script>
( function () {
	var lightbox = document.getElementById( 'lightbox' );
	var lightboxImg = document.getElementById( 'lightbox-img' );
	document.addEventListener( 'click', function ( event ) {
		var thumb = event.target.closest( '.shot-thumb' );
		if ( thumb ) {
			lightboxImg.src = thumb.getAttribute( 'data-full' );
			lightboxImg.alt = thumb.getAttribute( 'aria-label' ) || '';
			lightbox.hidden = false;
			return;
		}
		if ( event.target === lightbox ) {
			lightbox.hidden = true;
		}
	} );
	document.addEventListener( 'keydown', function ( event ) {
		if ( event.key === 'Escape' ) {
			lightbox.hidden = true;
		}
	} );
} )();
</script>
</body>
</html>`;
}

function main() {
	const results = readResults();
	const html = render( results );
	fs.writeFileSync( OUTPUT_PATH, html );
	console.log( `Dashboard written to ${ OUTPUT_PATH }` );
}

main();
