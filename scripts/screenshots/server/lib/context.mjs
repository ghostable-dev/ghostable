import { execFile, spawn } from 'node:child_process';
import { promisify } from 'node:util';

const execFileAsync = promisify(execFile);

export async function seedScreenshotAccount({ cwd, phpBinary = 'php' }) {
  await runStreamingCommand(phpBinary, ['artisan', 'app:seed-screenshot-account', '--force', '--no-ansi'], cwd);
}

export async function loadScreenshotContext({ cwd, phpBinary = 'php' }) {
  const { stdout } = await execFileAsync(
    phpBinary,
    ['artisan', 'app:screenshot-context', '--json', '--no-ansi'],
    {
      cwd,
      maxBuffer: 1024 * 1024 * 4,
    },
  );

  return JSON.parse(stdout);
}

export function resolveTargetUrl(target, screenshotContext, baseUrl) {
  const normalizedBaseUrl = ensureBaseUrl(baseUrl || screenshotContext.base_url);

  if (target.type === 'path') {
    return appendQuery(new URL(target.path, normalizedBaseUrl), target.query).toString();
  }

  const routeTemplate = screenshotContext.route_templates[target.route];

  if (!routeTemplate) {
    throw new Error(`Unknown screenshot route "${target.route}".`);
  }

  let uri = routeTemplate.uri;

  for (const parameter of routeTemplate.parameters) {
    if (!(parameter in target.params)) {
      throw new Error(`Missing route parameter "${parameter}" for screenshot route "${target.route}".`);
    }

    const resolvedValue = resolveParameterValue(target.params[parameter], screenshotContext);
    const token = new RegExp(`\\{${parameter}\\??\\}`, 'g');

    uri = uri.replace(token, encodeURIComponent(String(resolvedValue)));
  }

  const url = new URL(uri, normalizedBaseUrl);

  return appendQuery(url, target.query).toString();
}

function resolveParameterValue(value, screenshotContext) {
  if (typeof value === 'string' && value.startsWith('alias:')) {
    const alias = value.slice('alias:'.length);
    const entry = screenshotContext.aliases[alias];

    if (!entry) {
      throw new Error(`Unknown screenshot alias "${alias}".`);
    }

    return entry.route_key;
  }

  return value;
}

function appendQuery(url, query) {
  for (const [key, value] of Object.entries(query ?? {})) {
    if (value === undefined || value === null || value === '') {
      continue;
    }

    url.searchParams.set(key, String(value));
  }

  return url;
}

function ensureBaseUrl(baseUrl) {
  if (!baseUrl) {
    throw new Error('A base URL is required for server screenshot capture.');
  }

  return baseUrl.endsWith('/') ? baseUrl : `${baseUrl}/`;
}

function runStreamingCommand(command, args, cwd) {
  return new Promise((resolve, reject) => {
    const child = spawn(command, args, {
      cwd,
      stdio: 'inherit',
    });

    child.on('error', reject);
    child.on('close', (code) => {
      if (code === 0) {
        resolve();

        return;
      }

      reject(new Error(`${command} ${args.join(' ')} exited with code ${code}.`));
    });
  });
}
