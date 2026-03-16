import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import path from 'node:path';
import test from 'node:test';
import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { fileURLToPath } from 'node:url';

const execFileAsync = promisify(execFile);

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repositoryRoot = path.resolve(__dirname, '../../..');
const outputRoot = path.join(repositoryRoot, 'storage/app/screenshots/server');
const shouldRun = process.env.RUN_SERVER_SCREENSHOT_SMOKE_TESTS === '1';
const baseUrl = process.env.SERVER_SCREENSHOT_BASE_URL || 'https://ghostable.test';

test('server screenshot runner captures light/dark locator and clip screenshots', { skip: !shouldRun }, async () => {
  await execFileAsync(
    'node',
    [
      'scripts/screenshots/server/run.mjs',
      '--only=flagship-env-variables,dashboard-projects-overview,organization-audit-webhooks',
      '--theme=both',
      `--base-url=${baseUrl}`,
    ],
    {
      cwd: repositoryRoot,
      maxBuffer: 1024 * 1024 * 8,
    },
  );

  await Promise.all([
    assertFile(path.join(outputRoot, 'docs/server/flagship-environment-variables-light.png')),
    assertFile(path.join(outputRoot, 'docs/server/flagship-environment-variables-dark.png')),
    assertFile(path.join(outputRoot, 'docs/server/dashboard-projects-overview-light.png')),
    assertFile(path.join(outputRoot, 'docs/server/dashboard-projects-overview-dark.png')),
    assertFile(path.join(outputRoot, 'docs/server/organization-audit-webhooks-light.png')),
    assertFile(path.join(outputRoot, 'docs/server/organization-audit-webhooks-dark.png')),
    assertFile(path.join(outputRoot, 'index.json')),
    assertFile(path.join(outputRoot, 'index.html')),
  ]);
});

async function assertFile(filePath) {
  const stat = await fs.stat(filePath);

  assert.ok(stat.isFile(), `${filePath} was not created.`);
}
