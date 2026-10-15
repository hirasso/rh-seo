// @ts-check

/**
 * esbuild Build Script
 * Author: Rasso Hilber
 * Author URL: https://rassohilber.com
 * License: MIT
 *
 * Arguments:
 *
 *  -f:                   entry files, comma-separated (glob patterns supported)
 *  -o:                   outDir
 *  --production:         build once, minified (default is watch mode with live reload)
 */

import * as esbuild from 'esbuild';
import * as sass from 'sass';
import http from 'node:http';
import { parseArgs } from 'node:util';
import { dirname, basename } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';
import { execFileSync } from 'node:child_process';
import { globSync, readFileSync, mkdirSync, rmSync, watch } from 'node:fs';

const { values: args } = parseArgs({
  options: {
    f: { type: 'string' },
    o: { type: 'string', default: 'assets/dist' },
    production: { type: 'boolean', default: false },
  },
});

const isProduction = args.production;
const outDir = args.o;
const preferredReloadPort = 35799; // 35729 is often taken by other livereload servers

const entryPoints = (args.f ?? '')
  .split(',')
  .map((pattern) => pattern.trim())
  .filter(Boolean)
  .flatMap((pattern) => globSync(pattern));

if (!entryPoints.length) {
  console.warn('No entry files given.');
  process.exit(1);
}

/**
 * Compile .scss files with dart-sass, then hand the CSS over to esbuild
 * url() is resolved relative to the entry .scss file (same as parcel did)
 * @type {esbuild.Plugin}
 */
const sassPlugin = {
  name: 'sass',
  setup(build) {
    build.onLoad({ filter: /\.scss$/ }, (args) => {
      try {
        const result = sass.compile(args.path, {
          sourceMap: !isProduction,
          sourceMapIncludeSources: true,
        });
        let contents = result.css;
        if (result.sourceMap) {
          const map = Buffer.from(JSON.stringify(result.sourceMap)).toString('base64');
          contents += `\n/*# sourceMappingURL=data:application/json;base64,${map} */`;
        }
        return {
          contents,
          loader: 'css',
          resolveDir: dirname(args.path),
          watchFiles: result.loadedUrls
            .filter((url) => url.protocol === 'file:')
            .map((url) => fileURLToPath(url)),
        };
      } catch (error) {
        return { errors: [{ text: error instanceof Error ? error.message : String(error) }] };
      }
    });
  },
};

/**
 * Connected live reload clients
 * @type {Set<http.ServerResponse>}
 */
const clients = new Set();

/**
 * Send an event to all connected browsers
 * @param {'reload' | 'css'} event
 * @param {string} [data]
 */
function notifyBrowsers(event, data = '') {
  for (const res of clients) res.write(`event: ${event}\ndata: ${data}\n\n`);
}

function reloadBrowsers() {
  notifyBrowsers('reload');
}

/**
 * Content hashes of the last build's output files
 * @type {Map<string, string>}
 */
const outputHashes = new Map();

/**
 * Get the output files whose content changed since the last build
 * @param {esbuild.Metafile} metafile
 */
function getChangedOutputs(metafile) {
  return Object.keys(metafile.outputs)
    .filter((file) => !file.endsWith('.map'))
    .filter((file) => {
      const hash = createHash('md5').update(readFileSync(file)).digest('hex');
      const changed = outputHashes.get(file) !== hash;
      outputHashes.set(file, hash);
      return changed;
    });
}

/**
 * Inject CSS if only stylesheets changed, reload the page otherwise
 * @type {esbuild.Plugin}
 */
const reloadPlugin = {
  name: 'reload',
  setup(build) {
    build.initialOptions.metafile = true;
    build.onEnd((result) => {
      if (result.errors.length || !result.metafile) return;
      const changed = getChangedOutputs(result.metafile);
      if (!changed.length) return;
      if (changed.every((file) => file.endsWith('.css'))) {
        notifyBrowsers('css', JSON.stringify(changed.map((file) => basename(file))));
      } else {
        reloadBrowsers();
      }
    });
  },
};

/**
 * Runs in the browser (injected as a banner in dev builds)
 * @param {number} port
 */
function liveReloadClient(port) {
  const events = new EventSource(`http://localhost:${port}`);
  events.addEventListener('reload', () => location.reload());
  events.addEventListener('css', (event) => {
    /** @type {string[]} */
    const files = JSON.parse(event.data);
    /** @type {NodeListOf<HTMLLinkElement>} */
    const links = document.querySelectorAll('link[rel="stylesheet"]');
    for (const link of links) {
      const url = new URL(link.href);
      if (!files.some((file) => url.pathname.endsWith(`/${file}`))) continue;
      url.searchParams.set('v', String(Date.now()));
      // swap after the new stylesheet has loaded to avoid a flash of unstyled content
      const clone = /** @type {HTMLLinkElement} */ (link.cloneNode());
      clone.href = url.href;
      clone.onload = () => link.remove();
      link.after(clone);
    }
  });
}

/**
 * The options for esbuild
 * @type {esbuild.BuildOptions}
 */
const options = {
  entryPoints,
  outdir: outDir,
  bundle: true,
  format: 'iife',
  target: ['es2020', 'chrome100', 'edge100', 'firefox100', 'safari14', 'ios14'],
  minify: isProduction,
  sourcemap: !isProduction,
  resolveExtensions: ['.js', '.mjs', '.json', '.scss', '.css'],
  loader: {
    '.png': 'file',
    '.jpg': 'file',
    '.jpeg': 'file',
    '.gif': 'file',
    '.webp': 'file',
    '.eot': 'file',
    '.svg': 'file',
    '.ttf': 'file',
    '.woff': 'file',
    '.woff2': 'file',
  },
  assetNames: '[name].[hash]',
  define: {
    'process.env.NODE_ENV': JSON.stringify(isProduction ? 'production' : 'development'),
    // parcel's HMR API – doesn't exist with esbuild
    'module.hot': 'undefined',
  },
  plugins: isProduction ? [sassPlugin] : [sassPlugin, reloadPlugin],
  logLevel: 'info',
};

/**
 * Start the live reload server (dev only)
 * Falls back to a random free port if the preferred one is taken (e.g. by another watch)
 * Resolves once listening, so a failure happens before outDir gets wiped
 * @param {number} port
 * @returns {Promise<number>} the port actually used
 */
function startReloadServer(port) {
  const server = http.createServer((req, res) => {
    res.writeHead(200, {
      'Content-Type': 'text/event-stream',
      'Cache-Control': 'no-cache',
      'Access-Control-Allow-Origin': '*',
    });
    clients.add(res);
    req.on('close', () => clients.delete(res));
  });
  return new Promise((resolve, reject) => {
    server.once('error', (error) => {
      if (port && /** @type {NodeJS.ErrnoException} */ (error).code === 'EADDRINUSE') {
        resolve(startReloadServer(0));
      } else {
        reject(error);
      }
    });
    server.listen(port, () => {
      resolve(/** @type {import('node:net').AddressInfo} */ (server.address()).port);
    });
  });
}

/**
 * Get the local site URL from wp-config.php (WP_HOME_DEV), if available
 * @returns {string | undefined}
 */
function getSiteUrl() {
  try {
    return execFileSync('wp', ['config', 'get', 'WP_HOME_DEV'], {
      encoding: 'utf8',
      stdio: ['ignore', 'pipe', 'ignore'],
    }).trim();
  } catch {
    return undefined;
  }
}

if (!isProduction) {
  const reloadPort = await startReloadServer(preferredReloadPort);
  options.banner = { js: `(${liveReloadClient})(${reloadPort});` };
  console.log(`Live reload on http://localhost:${reloadPort}`);
}

rmSync(outDir, { recursive: true, force: true });
mkdirSync(outDir, { recursive: true });

if (isProduction) {
  await esbuild.build(options);
} else {
  // also reload on PHP changes
  /** @type {NodeJS.Timeout | undefined} */
  let timeout;
  watch('.', { recursive: true }, (_event, file) => {
    if (!file?.endsWith('.php') || /^(node_modules|vendor)\//.test(file)) return;
    clearTimeout(timeout);
    timeout = setTimeout(reloadBrowsers, 100);
  });

  const ctx = await esbuild.context(options);
  await ctx.watch();

  const siteUrl = getSiteUrl();
  if (siteUrl) console.log(`\n  ➜  Site: ${siteUrl}\n`);
}
