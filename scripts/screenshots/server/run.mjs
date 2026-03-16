#!/usr/bin/env node

import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

import { chromium } from 'playwright';

import {
  buildOutputPath,
  filterShots,
  loadScreenshotDefinitions,
  resolveThemes,
} from './lib/manifest.mjs';
import {
  loadScreenshotContext,
  resolveTargetUrl,
  seedScreenshotAccount,
} from './lib/context.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const repositoryRoot = path.resolve(__dirname, '../../..');
const manifestRoot = path.join(repositoryRoot, 'screenshots/server');
const outputRoot = path.join(repositoryRoot, 'storage/app/screenshots/server');

const LOGIN_EMAIL = 'avery@northstar.test';
const LOGIN_PASSWORD = 'password';

async function main() {
  const options = parseArguments(process.argv.slice(2));
  const shots = await loadScreenshotDefinitions({
    rootDir: manifestRoot,
    manifestPath: options.manifestPath,
  });
  const selectedShots = filterShots(shots, {
    onlyIds: options.onlyIds,
    tag: options.tag,
  });

  if (selectedShots.length === 0) {
    throw new Error('No screenshots matched the current filters.');
  }

  if (options.listOnly) {
    printShotList(selectedShots);

    return;
  }

  console.log('Seeding the screenshot account...');
  await seedScreenshotAccount({ cwd: repositoryRoot });

  console.log('Resolving screenshot context...');
  const screenshotContext = await loadScreenshotContext({ cwd: repositoryRoot });
  const baseUrl = options.baseUrl || screenshotContext.base_url;
  const plan = buildCapturePlan(selectedShots, screenshotContext, baseUrl, options.themeFilter);

  if (plan.length === 0) {
    throw new Error('No screenshots matched the requested theme filter.');
  }

  if (options.dryRun) {
    printPlan(plan);

    return;
  }

  await fs.mkdir(outputRoot, { recursive: true });

  console.log(`Logging into ${baseUrl}...`);
  const browser = await chromium.launch({ headless: true });
  const browserContext = await browser.newContext({
    ignoreHTTPSErrors: true,
    viewport: plan[0].shot.viewport,
  });
  const page = await browserContext.newPage();

  try {
    await login(page, baseUrl);

    const results = [];

    for (const entry of plan) {
      console.log(`Capturing ${entry.shot.id} (${entry.theme})...`);
      await page.setViewportSize(entry.shot.viewport);
      await applyTheme(page, entry.theme);
      await page.goto(entry.url, { waitUntil: 'networkidle' });
      await waitForTheme(page, entry.theme);
      await runActions(page, entry.shot.actions);
      await captureScreenshot(page, entry);

      results.push({
        id: entry.shot.id,
        theme: entry.theme,
        filename: path.basename(entry.outputPath),
        manifest: path.relative(repositoryRoot, entry.shot.manifestPath).replaceAll(path.sep, '/'),
        output_path: path.relative(repositoryRoot, entry.outputPath).replaceAll(path.sep, '/'),
        url: entry.url,
        tags: entry.shot.tags,
      });
    }

    await writeReviewArtifacts(results);
    console.log(`Captured ${results.length} screenshot(s) into ${path.relative(repositoryRoot, outputRoot)}.`);
  } finally {
    await browser.close();
  }
}

function buildCapturePlan(shots, screenshotContext, baseUrl, themeFilter) {
  const plan = [];

  for (const shot of shots) {
    for (const theme of resolveThemes(shot, themeFilter)) {
      plan.push({
        shot,
        theme,
        url: resolveTargetUrl(shot.target, screenshotContext, baseUrl),
        outputPath: buildOutputPath(outputRoot, shot, theme),
      });
    }
  }

  return plan;
}

async function login(page, baseUrl) {
  const loginUrl = new URL('/login', ensureTrailingSlash(baseUrl)).toString();

  await page.goto(loginUrl, { waitUntil: 'networkidle' });
  await page.locator('input[autocomplete="email"]').fill(LOGIN_EMAIL);
  await page.locator('input[autocomplete="current-password"]').fill(LOGIN_PASSWORD);
  await page.getByRole('button', { name: 'Log in' }).click();
  await page.waitForURL('**/dashboard', { timeout: 30000 });
  await page.waitForLoadState('networkidle');
}

async function applyTheme(page, theme) {
  await page.evaluate((nextTheme) => {
    window.localStorage.setItem('flux.appearance', nextTheme);

    if (window.Flux) {
      window.Flux.appearance = nextTheme;
    }
  }, theme);
}

async function waitForTheme(page, theme) {
  if (theme === 'dark') {
    await page.waitForFunction(() => document.documentElement.classList.contains('dark'));

    return;
  }

  await page.waitForFunction(() => !document.documentElement.classList.contains('dark'));
}

async function runActions(page, actions) {
  for (const action of actions) {
    const locator = resolveLocator(page, action.locator);

    if (action.type === 'wait_for') {
      await locator.waitFor({ state: 'visible', timeout: 15000 });
      continue;
    }

    if (action.type === 'click') {
      await locator.waitFor({ state: 'visible', timeout: 15000 });
      await locator.click();
      await page.waitForTimeout(150);
      continue;
    }
  }
}

async function captureScreenshot(page, entry) {
  await fs.mkdir(path.dirname(entry.outputPath), { recursive: true });

  const capture = entry.shot.capture;
  const masks = capture.mask.map((selector) => page.locator(selector));

  if (capture.mode === 'full_page') {
    await page.screenshot({
      path: entry.outputPath,
      fullPage: true,
      mask: masks,
    });

    return;
  }

  if (capture.mode === 'viewport') {
    await page.screenshot({
      path: entry.outputPath,
      mask: masks,
    });

    return;
  }

  if (capture.mode === 'clip') {
    await page.screenshot({
      path: entry.outputPath,
      clip: clampClip(capture.clip, entry.shot.viewport),
      mask: masks,
    });

    return;
  }

  const locator = page.locator(capture.css).first();

  await locator.waitFor({ state: 'visible', timeout: 15000 });
  await locator.scrollIntoViewIfNeeded();

  const boundingBox = await locator.boundingBox();

  if (!boundingBox) {
    throw new Error(`Unable to resolve a bounding box for screenshot "${entry.shot.id}".`);
  }

  const clip = clampClip(
    {
      x: boundingBox.x - capture.padding,
      y: boundingBox.y - capture.padding,
      width: boundingBox.width + (capture.padding * 2),
      height: boundingBox.height + (capture.padding * 2),
    },
    entry.shot.viewport,
  );

  await page.screenshot({
    path: entry.outputPath,
    clip,
    mask: masks,
  });
}

function resolveLocator(page, locator) {
  if (locator.type === 'css') {
    return page.locator(locator.css).first();
  }

  if (locator.type === 'text') {
    return page.getByText(locator.text, { exact: true }).first();
  }

  return page.getByRole(locator.role, { name: locator.name }).first();
}

function clampClip(clip, viewport) {
  const x = Math.max(0, clip.x);
  const y = Math.max(0, clip.y);
  const width = Math.min(clip.width, viewport.width - x);
  const height = Math.min(clip.height, viewport.height - y);

  if (width <= 0 || height <= 0) {
    throw new Error('Resolved screenshot clip is outside the current viewport.');
  }

  return { x, y, width, height };
}

async function writeReviewArtifacts(results) {
  const payload = {
    generated_at: new Date().toISOString(),
    entries: results,
  };

  await fs.writeFile(
    path.join(outputRoot, 'index.json'),
    JSON.stringify(payload, null, 2),
  );

  const items = results
    .map((entry) => {
      const imagePath = entry.output_path.replace('storage/app/screenshots/server/', '');

      return `
        <article>
          <h2>${escapeHtml(entry.id)} <small>${escapeHtml(entry.theme)}</small></h2>
          <p><code>${escapeHtml(entry.output_path)}</code></p>
          <img src="./${escapeHtml(imagePath)}" alt="${escapeHtml(entry.id)} ${escapeHtml(entry.theme)}">
        </article>
      `;
    })
    .join('\n');

  const html = `<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Screenshot Review</title>
    <style>
      body { font-family: Arial, sans-serif; margin: 32px; background: #f5f5f5; color: #111827; }
      article { margin-bottom: 32px; padding: 20px; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; }
      img { display: block; max-width: 100%; border-radius: 12px; border: 1px solid #e5e7eb; }
      code { font-size: 12px; }
      small { font-weight: normal; color: #6b7280; }
    </style>
  </head>
  <body>
    <h1>Server Screenshot Review</h1>
    ${items}
  </body>
</html>`;

  await fs.writeFile(path.join(outputRoot, 'index.html'), html);
}

function printShotList(shots) {
  for (const shot of shots) {
    console.log(`${shot.id} -> ${shot.filename} [${shot.tags.join(', ')}]`);
  }
}

function printPlan(plan) {
  for (const entry of plan) {
    console.log(`${entry.shot.id} (${entry.theme})`);
    console.log(`  URL: ${entry.url}`);
    console.log(`  Output: ${path.relative(repositoryRoot, entry.outputPath)}`);
  }
}

function parseArguments(argv) {
  const options = {
    onlyIds: [],
    tag: '',
    themeFilter: 'both',
    manifestPath: '',
    listOnly: false,
    dryRun: false,
    baseUrl: '',
  };

  for (let index = 0; index < argv.length; index += 1) {
    const argument = argv[index];
    const [flag, inlineValue] = argument.split('=', 2);

    const value = inlineValue ?? argv[index + 1];

    switch (flag) {
      case '--all':
        break;
      case '--only':
        options.onlyIds = value.split(',').map((entry) => entry.trim()).filter(Boolean);
        if (inlineValue === undefined) {
          index += 1;
        }
        break;
      case '--tag':
        options.tag = value.trim();
        if (inlineValue === undefined) {
          index += 1;
        }
        break;
      case '--theme':
        options.themeFilter = value.trim();
        if (inlineValue === undefined) {
          index += 1;
        }
        break;
      case '--manifest':
        options.manifestPath = path.resolve(value);
        if (inlineValue === undefined) {
          index += 1;
        }
        break;
      case '--list':
        options.listOnly = true;
        break;
      case '--dry-run':
        options.dryRun = true;
        break;
      case '--base-url':
        options.baseUrl = value.trim();
        if (inlineValue === undefined) {
          index += 1;
        }
        break;
      default:
        throw new Error(`Unknown argument "${argument}".`);
    }
  }

  if (!['light', 'dark', 'both'].includes(options.themeFilter)) {
    throw new Error(`Unsupported theme filter "${options.themeFilter}".`);
  }

  return options;
}

function ensureTrailingSlash(value) {
  return value.endsWith('/') ? value : `${value}/`;
}

function escapeHtml(value) {
  return String(value)
    .replaceAll('&', '&amp;')
    .replaceAll('<', '&lt;')
    .replaceAll('>', '&gt;')
    .replaceAll('"', '&quot;')
    .replaceAll("'", '&#39;');
}

main().catch((error) => {
  console.error(error.message);
  process.exitCode = 1;
});
