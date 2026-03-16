import assert from 'node:assert/strict';
import fs from 'node:fs/promises';
import os from 'node:os';
import path from 'node:path';
import test from 'node:test';

import {
  buildOutputPath,
  filterShots,
  loadScreenshotDefinitions,
  resolveThemes,
} from '../../../scripts/screenshots/server/lib/manifest.mjs';
import { resolveTargetUrl } from '../../../scripts/screenshots/server/lib/context.mjs';

test('loadScreenshotDefinitions rejects duplicate screenshot ids', async () => {
  const directory = await fs.mkdtemp(path.join(os.tmpdir(), 'ghostable-screenshot-manifest-'));

  try {
    await fs.writeFile(
      path.join(directory, 'app.yaml'),
      `
version: 1
platform: server
shots:
  - id: duplicate-shot
    filename: first
    target:
      path: /dashboard
    capture:
      mode: viewport
  - id: duplicate-shot
    filename: second
    target:
      path: /dashboard
    capture:
      mode: viewport
`,
    );

    await assert.rejects(
      loadScreenshotDefinitions({ rootDir: directory }),
      /Duplicate screenshot id "duplicate-shot"/,
    );
  } finally {
    await fs.rm(directory, { recursive: true, force: true });
  }
});

test('loadScreenshotDefinitions rejects unsupported capture modes and actions', async () => {
  const directory = await fs.mkdtemp(path.join(os.tmpdir(), 'ghostable-screenshot-manifest-'));

  try {
    await fs.writeFile(
      path.join(directory, 'app.yaml'),
      `
version: 1
platform: server
shots:
  - id: invalid-shot
    filename: invalid
    target:
      path: /dashboard
    actions:
      - hover:
          css: .selector
    capture:
      mode: banana
`,
    );

    await assert.rejects(
      loadScreenshotDefinitions({ rootDir: directory }),
      /Unsupported screenshot action "hover"|unsupported capture mode "banana"/i,
    );
  } finally {
    await fs.rm(directory, { recursive: true, force: true });
  }
});

test('filterShots, resolveThemes, output paths, and route resolution work together', async () => {
  const directory = await fs.mkdtemp(path.join(os.tmpdir(), 'ghostable-screenshot-manifest-'));

  try {
    await fs.writeFile(
      path.join(directory, 'app.yaml'),
      `
version: 1
platform: server
defaults:
  themes: [light, dark]
  viewport: desktop-lg
  output_dir: docs/server
shots:
  - id: flagship-env-variables
    tags: [docs, marketing]
    filename: flagship-environment-variables
    target:
      route: environment.variables
      params:
        environment: alias:environment.control-plane.production
      query:
        screenshot: '1'
    capture:
      mode: locator
      css: '[data-screenshot-frame="env-vars-table"]'
      padding: 24
`,
    );

    const [shot] = await loadScreenshotDefinitions({ rootDir: directory });
    const selected = filterShots([shot], { tag: 'docs' });
    const themes = resolveThemes(selected[0], 'dark');
    const outputPath = buildOutputPath('/tmp/screenshots', selected[0], themes[0]);
    const url = resolveTargetUrl(
      selected[0].target,
      {
        aliases: {
          'environment.control-plane.production': {
            route_key: 'env-123',
          },
        },
        route_templates: {
          'environment.variables': {
            uri: '/environments/{environment}/variables',
            parameters: ['environment'],
          },
        },
      },
      'https://ghostable.test',
    );

    assert.equal(selected.length, 1);
    assert.deepEqual(themes, ['dark']);
    assert.equal(outputPath, '/tmp/screenshots/docs/server/flagship-environment-variables-dark.png');
    assert.equal(url, 'https://ghostable.test/environments/env-123/variables?screenshot=1');
  } finally {
    await fs.rm(directory, { recursive: true, force: true });
  }
});
