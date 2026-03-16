import fs from 'node:fs/promises';
import path from 'node:path';

import { parse as parseYaml } from 'yaml';

export const VIEWPORT_PRESETS = {
  'desktop-md': { width: 1280, height: 900 },
  'desktop-lg': { width: 1440, height: 1024 },
  'desktop-xl': { width: 1600, height: 1200 },
};

const VALID_CAPTURE_MODES = new Set(['full_page', 'viewport', 'locator', 'clip']);
const VALID_THEMES = new Set(['light', 'dark']);

export async function loadScreenshotDefinitions({ rootDir, manifestPath }) {
  const manifestFiles = await resolveManifestFiles(rootDir, manifestPath);
  const seenIds = new Map();
  const shots = [];

  for (const filePath of manifestFiles) {
    const raw = await fs.readFile(filePath, 'utf8');
    const manifest = parseYaml(raw);

    validateManifest(manifest, filePath);

    const defaults = normalizeDefaults(manifest.defaults ?? {}, filePath);

    for (const shot of manifest.shots) {
      const normalizedShot = normalizeShot(shot, defaults, filePath);
      const duplicatePath = seenIds.get(normalizedShot.id);

      if (duplicatePath) {
        throw new Error(`Duplicate screenshot id "${normalizedShot.id}" found in ${filePath} and ${duplicatePath}.`);
      }

      seenIds.set(normalizedShot.id, filePath);
      shots.push(normalizedShot);
    }
  }

  return shots;
}

export function filterShots(shots, options = {}) {
  const onlyIds = normalizeCsvList(options.onlyIds ?? []);
  const tag = typeof options.tag === 'string' ? options.tag.trim() : '';

  if (onlyIds.length === 0 && tag === '') {
    return shots;
  }

  const selected = shots.filter((shot) => {
    const matchesIds = onlyIds.length === 0 || onlyIds.includes(shot.id);
    const matchesTag = tag === '' || shot.tags.includes(tag);

    return matchesIds && matchesTag;
  });

  const missingIds = onlyIds.filter((id) => !selected.some((shot) => shot.id === id));

  if (missingIds.length > 0) {
    throw new Error(`Unknown screenshot id(s): ${missingIds.join(', ')}.`);
  }

  if (tag !== '' && !selected.some((shot) => shot.tags.includes(tag))) {
    throw new Error(`No screenshots matched tag "${tag}".`);
  }

  return selected;
}

export function resolveThemes(shot, themeFilter = 'both') {
  if (themeFilter === 'both') {
    return [...shot.themes];
  }

  if (!VALID_THEMES.has(themeFilter)) {
    throw new Error(`Unsupported theme filter "${themeFilter}". Expected light, dark, or both.`);
  }

  if (!shot.themes.includes(themeFilter)) {
    return [];
  }

  return [themeFilter];
}

export function buildOutputPath(outputRoot, shot, theme) {
  return path.join(outputRoot, shot.outputDir, `${shot.filename}-${theme}.png`);
}

async function resolveManifestFiles(rootDir, manifestPath) {
  if (manifestPath) {
    const absolutePath = path.resolve(manifestPath);
    const stat = await fs.stat(absolutePath);

    if (stat.isDirectory()) {
      return collectYamlFiles(absolutePath);
    }

    return [absolutePath];
  }

  return collectYamlFiles(rootDir);
}

async function collectYamlFiles(directory) {
  const entries = await fs.readdir(directory, { withFileTypes: true });

  return entries
    .filter((entry) => entry.isFile() && (entry.name.endsWith('.yaml') || entry.name.endsWith('.yml')))
    .map((entry) => path.join(directory, entry.name))
    .sort();
}

function validateManifest(manifest, filePath) {
  if (!manifest || typeof manifest !== 'object') {
    throw new Error(`Screenshot manifest ${filePath} must contain a YAML object.`);
  }

  if (manifest.version !== 1) {
    throw new Error(`Screenshot manifest ${filePath} must declare version: 1.`);
  }

  if (manifest.platform !== 'server') {
    throw new Error(`Screenshot manifest ${filePath} must declare platform: server.`);
  }

  if (!Array.isArray(manifest.shots) || manifest.shots.length === 0) {
    throw new Error(`Screenshot manifest ${filePath} must declare a non-empty shots array.`);
  }
}

function normalizeDefaults(defaults, filePath) {
  return {
    themes: normalizeThemes(defaults.themes ?? ['light', 'dark'], `${filePath} defaults.themes`),
    viewport: resolveViewport(defaults, filePath, 'defaults'),
    outputDir: normalizeOutputDir(defaults.output_dir ?? 'docs/server', `${filePath} defaults.output_dir`),
  };
}

function normalizeShot(shot, defaults, filePath) {
  if (!shot || typeof shot !== 'object') {
    throw new Error(`Screenshot shot in ${filePath} must be an object.`);
  }

  const id = normalizeRequiredString(shot.id, `${filePath} shot.id`);
  const filename = normalizeFilename(shot.filename, `${filePath} shot "${id}" filename`);
  const capture = normalizeCapture(shot.capture ?? {}, filePath, id);

  return {
    id,
    tags: normalizeTags(shot.tags ?? [], `${filePath} shot "${id}" tags`),
    filename,
    manifestPath: filePath,
    outputDir: normalizeOutputDir(shot.output_dir ?? defaults.outputDir, `${filePath} shot "${id}" output_dir`),
    themes: normalizeThemes(shot.themes ?? defaults.themes, `${filePath} shot "${id}" themes`),
    viewport: resolveViewport(
      {
        viewport: shot.viewport ?? defaults.viewport.name,
        width: shot.width ?? defaults.viewport.width,
        height: shot.height ?? defaults.viewport.height,
      },
      filePath,
      id,
    ),
    target: normalizeTarget(shot.target, filePath, id),
    actions: normalizeActions(shot.actions ?? [], filePath, id),
    capture,
  };
}

function normalizeTarget(target, filePath, shotId) {
  if (!target || typeof target !== 'object') {
    throw new Error(`Screenshot shot "${shotId}" in ${filePath} must declare a target object.`);
  }

  const query = normalizeQuery(target.query ?? null, filePath, shotId);

  if (typeof target.route === 'string' && target.route.trim() !== '') {
    return {
      type: 'route',
      route: target.route.trim(),
      params: normalizeParams(target.params ?? {}, filePath, shotId),
      query,
    };
  }

  if (typeof target.path === 'string' && target.path.trim() !== '') {
    return {
      type: 'path',
      path: normalizeSitePath(target.path, `${filePath} shot "${shotId}" target.path`),
      query,
    };
  }

  throw new Error(`Screenshot shot "${shotId}" in ${filePath} must define target.route or target.path.`);
}

function normalizeActions(actions, filePath, shotId) {
  if (!Array.isArray(actions)) {
    throw new Error(`Screenshot shot "${shotId}" in ${filePath} must define actions as an array.`);
  }

  return actions.map((action, index) => {
    if (!action || typeof action !== 'object') {
      throw new Error(`Screenshot shot "${shotId}" action #${index + 1} in ${filePath} must be an object.`);
    }

    const entries = Object.entries(action);

    if (entries.length !== 1) {
      throw new Error(`Screenshot shot "${shotId}" action #${index + 1} in ${filePath} must declare exactly one action type.`);
    }

    const [type, value] = entries[0];

    if (type === 'wait_for') {
      return {
        type,
        locator: normalizeLocatorDescriptor(value, filePath, shotId, index),
      };
    }

    if (type === 'click') {
      return {
        type,
        locator: normalizeLocatorDescriptor(value, filePath, shotId, index),
      };
    }

    throw new Error(`Unsupported screenshot action "${type}" in shot "${shotId}" (${filePath}).`);
  });
}

function normalizeCapture(capture, filePath, shotId) {
  if (!capture || typeof capture !== 'object') {
    throw new Error(`Screenshot shot "${shotId}" in ${filePath} must define a capture object.`);
  }

  const mode = typeof capture.mode === 'string' ? capture.mode.trim() : 'locator';

  if (!VALID_CAPTURE_MODES.has(mode)) {
    throw new Error(`Screenshot shot "${shotId}" in ${filePath} uses unsupported capture mode "${mode}".`);
  }

  const normalized = {
    mode,
    css: typeof capture.css === 'string' ? capture.css.trim() : '',
    padding: normalizePadding(capture.padding ?? 0, filePath, shotId),
    mask: normalizeMask(capture.mask ?? [], filePath, shotId),
    clip: null,
  };

  if (mode === 'locator' && normalized.css === '') {
    throw new Error(`Screenshot shot "${shotId}" in ${filePath} must define capture.css when using locator mode.`);
  }

  if (mode === 'clip') {
    normalized.clip = normalizeClip(capture.clip, filePath, shotId);
  }

  return normalized;
}

function normalizeLocatorDescriptor(value, filePath, shotId, index) {
  if (!value || typeof value !== 'object') {
    throw new Error(`Screenshot shot "${shotId}" action #${index + 1} in ${filePath} must define a locator object.`);
  }

  if (typeof value.css === 'string' && value.css.trim() !== '') {
    return { type: 'css', css: value.css.trim() };
  }

  if (typeof value.text === 'string' && value.text.trim() !== '') {
    return { type: 'text', text: value.text.trim() };
  }

  if (typeof value.role === 'string' && value.role.trim() !== '') {
    const name = normalizeRequiredString(value.name, `${filePath} shot "${shotId}" action #${index + 1} role.name`);

    return { type: 'role', role: value.role.trim(), name };
  }

  throw new Error(`Screenshot shot "${shotId}" action #${index + 1} in ${filePath} must define css, text, or role+name.`);
}

function normalizeClip(clip, filePath, shotId) {
  if (!clip || typeof clip !== 'object') {
    throw new Error(`Screenshot shot "${shotId}" in ${filePath} must define capture.clip when using clip mode.`);
  }

  const x = normalizeNumber(clip.x, `${filePath} shot "${shotId}" clip.x`);
  const y = normalizeNumber(clip.y, `${filePath} shot "${shotId}" clip.y`);
  const width = normalizeNumber(clip.width, `${filePath} shot "${shotId}" clip.width`);
  const height = normalizeNumber(clip.height, `${filePath} shot "${shotId}" clip.height`);

  if (width <= 0 || height <= 0) {
    throw new Error(`Screenshot shot "${shotId}" in ${filePath} must define a positive clip width and height.`);
  }

  return { x, y, width, height };
}

function resolveViewport(config, filePath, label) {
  const presetName = typeof config.viewport === 'string' ? config.viewport.trim() : 'desktop-lg';
  const preset = VIEWPORT_PRESETS[presetName];

  if (!preset) {
    throw new Error(`Screenshot ${label} in ${filePath} references unknown viewport preset "${presetName}".`);
  }

  const width = normalizeOptionalDimension(config.width, `${filePath} ${label} width`, preset.width);
  const height = normalizeOptionalDimension(config.height, `${filePath} ${label} height`, preset.height);

  return {
    name: presetName,
    width,
    height,
  };
}

function normalizeThemes(themes, label) {
  if (!Array.isArray(themes) || themes.length === 0) {
    throw new Error(`Screenshot ${label} must be a non-empty array.`);
  }

  const normalized = themes.map((theme) => normalizeRequiredString(theme, label));

  for (const theme of normalized) {
    if (!VALID_THEMES.has(theme)) {
      throw new Error(`Screenshot ${label} includes unsupported theme "${theme}".`);
    }
  }

  return [...new Set(normalized)];
}

function normalizeTags(tags, label) {
  if (!Array.isArray(tags)) {
    throw new Error(`Screenshot ${label} must be an array.`);
  }

  return [...new Set(tags.map((tag) => normalizeRequiredString(tag, label)))];
}

function normalizeOutputDir(value, label) {
  const normalized = normalizeRelativePath(value, label);

  if (normalized === '.') {
    return '';
  }

  return normalized;
}

function normalizeRelativePath(value, label) {
  const normalized = normalizeRequiredString(value, label).replaceAll('\\', '/');

  if (path.isAbsolute(normalized) || normalized.startsWith('../') || normalized.includes('/../')) {
    throw new Error(`Screenshot ${label} must stay within the screenshot output root.`);
  }

  return path.posix.normalize(normalized);
}

function normalizeSitePath(value, label) {
  const normalized = normalizeRequiredString(value, label).replaceAll('\\', '/');

  if (/^https?:\/\//.test(normalized)) {
    throw new Error(`Screenshot ${label} must be a site-relative path, not a full URL.`);
  }

  const withLeadingSlash = normalized.startsWith('/') ? normalized : `/${normalized}`;
  const resolved = path.posix.normalize(withLeadingSlash);

  if (!resolved.startsWith('/')) {
    throw new Error(`Screenshot ${label} must stay within the application root.`);
  }

  return resolved;
}

function normalizeParams(params, filePath, shotId) {
  if (!params || typeof params !== 'object' || Array.isArray(params)) {
    throw new Error(`Screenshot shot "${shotId}" in ${filePath} must define target.params as an object.`);
  }

  return Object.fromEntries(
    Object.entries(params).map(([key, value]) => [key, value]),
  );
}

function normalizeQuery(query, filePath, shotId) {
  if (query === null) {
    return {};
  }

  if (!query || typeof query !== 'object' || Array.isArray(query)) {
    throw new Error(`Screenshot shot "${shotId}" in ${filePath} must define target.query as an object when present.`);
  }

  return Object.fromEntries(
    Object.entries(query).map(([key, value]) => [key, value]),
  );
}

function normalizeMask(mask, filePath, shotId) {
  if (!Array.isArray(mask)) {
    throw new Error(`Screenshot shot "${shotId}" in ${filePath} must define capture.mask as an array when present.`);
  }

  return mask.map((selector, index) => normalizeRequiredString(selector, `${filePath} shot "${shotId}" capture.mask[${index}]`));
}

function normalizePadding(value, filePath, shotId) {
  const padding = normalizeNumber(value, `${filePath} shot "${shotId}" padding`);

  if (padding < 0) {
    throw new Error(`Screenshot shot "${shotId}" in ${filePath} cannot use a negative capture padding.`);
  }

  return padding;
}

function normalizeOptionalDimension(value, label, fallback) {
  if (value === undefined || value === null || value === '') {
    return fallback;
  }

  const normalized = normalizeNumber(value, label);

  if (normalized <= 0) {
    throw new Error(`Screenshot ${label} must be a positive number.`);
  }

  return normalized;
}

function normalizeFilename(value, label) {
  const normalized = normalizeRequiredString(value, label)
    .replace(/\.png$/i, '')
    .replaceAll('\\', '/');

  if (normalized.includes('/')) {
    throw new Error(`Screenshot ${label} must be a filename without path separators.`);
  }

  return normalized;
}

function normalizeRequiredString(value, label) {
  if (typeof value !== 'string' || value.trim() === '') {
    throw new Error(`Screenshot ${label} must be a non-empty string.`);
  }

  return value.trim();
}

function normalizeNumber(value, label) {
  if (typeof value !== 'number' && typeof value !== 'string') {
    throw new Error(`Screenshot ${label} must be numeric.`);
  }

  const normalized = Number(value);

  if (!Number.isFinite(normalized)) {
    throw new Error(`Screenshot ${label} must be numeric.`);
  }

  return normalized;
}

function normalizeCsvList(value) {
  if (Array.isArray(value)) {
    return value.flatMap((entry) => normalizeCsvList(entry));
  }

  if (typeof value !== 'string') {
    return [];
  }

  return value
    .split(',')
    .map((entry) => entry.trim())
    .filter(Boolean);
}
