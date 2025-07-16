<?php

namespace App\Environment\Services;

use App\Environment\Entities\EnvLine;
use App\Environment\Enums\EnvLineType;
use Dotenv\Parser\Parser;

class EnvParser
{
    /**
     * The Dotenv parsing engine.
     */
    private Parser $parser;

    public function __construct()
    {
        $this->parser = new Parser;
    }

    /**
     * Parse an array of lines from an .env file into structured EnvLine objects.
     *
     * This method processes each line using the parseLine() helper to detect and
     * parse environment variable entries. Commented-out variables and invalid lines
     * are preserved and wrapped as EnvLine objects with appropriate types.
     * Blank lines are skipped.
     *
     * @return array<int, EnvLine>
     */
    public function parse(array $lines): array
    {
        return collect($lines)
            // turn each raw line into an EnvLine (or null)
            ->map(fn (?string $line) => $this->parseLine($line ?? ''))
            ->filter()                    // drop nulls
            // sort by key (null keys → empty string, so they float to the top)
            ->sortBy(fn (EnvLine $line) => $line->key ?? '')
            ->values()                    // reindex 0,1,2…
            ->all();                      // back to plain array
    }

    /**
     * Parse a single line from an .env file into a structured EnvLine object.
     *
     * This method handles detection of commented-out variables (e.g., "#FOO=bar"),
     * uses Dotenv's parser to handle quoting and escaping rules, and wraps the
     * result in an EnvLine value object. Blank lines return null, and lines that
     * cannot be parsed return an EnvLine of type INVALID.
     */
    private function parseLine(string $raw): ?EnvLine
    {
        $line = trim($raw);

        if ($line === '') {
            return null; // Skip blank lines
        }

        $commented = false;

        if ($this->isCommentedOutVariable($line)) {
            $commented = true;
            $line = ltrim($line, '# ');
        }

        try {
            $parsed = $this->parser->parse($line);
        } catch (\Throwable $e) {
            return new EnvLine(
                type: EnvLineType::INVALID,
                raw: $raw,
                error: $e->getMessage()
            );
        }

        foreach ($parsed as $entry) {
            return new EnvLine(
                type: EnvLineType::ENV,
                key: $entry->getName(),
                value: $this->sanitizeParsedValue($entry->getValue()->get()->getChars()),
                commented: $commented,
                raw: $raw
            );
        }

        return null; // fallback, in case parsing yields no entries
    }

    /**
     * Determine if a line is a commented-out environment variable.
     *
     * A line is considered a commented-out variable if it starts with a `#`
     * and contains an `=` character, e.g., `#APP_DEBUG=false`.
     */
    private function isCommentedOutVariable(string $line): bool
    {
        return str_starts_with($line, '#') && str_contains($line, '=');
    }

    private function sanitizeParsedValue(string $value): string
    {
        // If quoted with " or ', strip quotes and unescape inner characters
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);

            // Unescape escaped quotes and backslashes
            $value = str_replace(['\\"', "\\'", '\\\\'], ['"', "'", '\\'], $value);
        }

        return $value;
    }
}
