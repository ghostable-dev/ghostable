package dotenv

import (
	"bufio"
	"fmt"
	"io"
	"regexp"
	"sort"
	"strconv"
	"strings"
	"unicode"
)

var keyPattern = regexp.MustCompile(`^[A-Za-z_][A-Za-z0-9_]*$`)

type Entry struct {
	Key      string
	Value    string
	Line     int
	Export   bool
	Disabled bool
}

type File struct {
	Entries map[string]Entry
	Lines   []string
}

func Parse(reader io.Reader) (File, error) {
	result := File{Entries: make(map[string]Entry)}
	scanner := bufio.NewScanner(reader)
	lineNumber := 0

	for scanner.Scan() {
		lineNumber++
		line := scanner.Text()
		result.Lines = append(result.Lines, line)

		entry, ok := parseLine(line, lineNumber)
		if !ok {
			continue
		}
		result.Entries[entry.Key] = entry
	}

	if err := scanner.Err(); err != nil {
		return result, err
	}

	return result, nil
}

func ParseString(content string) (map[string]string, error) {
	file, err := Parse(strings.NewReader(content))
	if err != nil {
		return nil, err
	}

	values := make(map[string]string, len(file.Entries))
	for key, entry := range file.Entries {
		if !entry.Disabled {
			values[key] = entry.Value
		}
	}

	return values, nil
}

func IsValidKey(key string) bool {
	return keyPattern.MatchString(key)
}

func FormatKey(key string) (string, bool, error) {
	original := strings.TrimSpace(key)
	if original == "" {
		return "", false, fmt.Errorf("variable key is required")
	}

	var builder strings.Builder
	lastUnderscore := false
	hasAlphaNumeric := false
	for _, r := range original {
		switch {
		case unicode.IsLetter(r):
			builder.WriteRune(unicode.ToUpper(r))
			lastUnderscore = false
			hasAlphaNumeric = true
		case unicode.IsDigit(r):
			builder.WriteRune(r)
			lastUnderscore = false
			hasAlphaNumeric = true
		case r == '_':
			if !lastUnderscore {
				builder.WriteRune('_')
				lastUnderscore = true
			}
		default:
			if !lastUnderscore {
				builder.WriteRune('_')
				lastUnderscore = true
			}
		}
	}

	formatted := builder.String()
	if formatted != "" && formatted[0] >= '0' && formatted[0] <= '9' {
		formatted = "_" + formatted
	}
	if !hasAlphaNumeric {
		return "", false, fmt.Errorf("variable key must include at least one letter or number")
	}
	if !IsValidKey(formatted) {
		return "", false, fmt.Errorf("use letters, numbers, and underscores")
	}
	return formatted, formatted != original, nil
}

func Render(values map[string]string, order []string) string {
	keys := orderedKeys(values, order)
	lines := make([]string, 0, len(keys))
	for _, key := range keys {
		lines = append(lines, fmt.Sprintf("%s=%s", key, FormatValue(values[key])))
	}
	return strings.Join(lines, "\n") + "\n"
}

func Merge(existing string, values map[string]string, order []string, replace bool) (string, error) {
	if replace || strings.TrimSpace(existing) == "" {
		return Render(values, order), nil
	}

	parsed, err := Parse(strings.NewReader(existing))
	if err != nil {
		return "", err
	}

	updates := map[int]string{}
	removals := map[int]bool{}
	written := make(map[string]bool)
	for key, entry := range parsed.Entries {
		value, ok := values[key]
		if !ok {
			continue
		}
		updates[entry.Line-1] = fmt.Sprintf("%s%s=%s", exportPrefix(entry.Export), key, FormatValue(value))
		written[key] = true
	}

	for index, line := range parsed.Lines {
		entry, ok := parseLine(line, index+1)
		if !ok {
			continue
		}
		if _, ok := values[entry.Key]; !ok {
			continue
		}
		effectiveEntry := parsed.Entries[entry.Key]
		if entry.Line != effectiveEntry.Line {
			removals[index] = true
		}
	}

	lines := make([]string, 0, len(parsed.Lines))
	for index, line := range parsed.Lines {
		if removals[index] {
			continue
		}
		if updated, ok := updates[index]; ok {
			line = updated
		}
		lines = append(lines, line)
	}

	for _, key := range orderedKeys(values, order) {
		if written[key] {
			continue
		}
		lines = append(lines, fmt.Sprintf("%s=%s", key, FormatValue(values[key])))
	}

	return strings.Join(lines, "\n") + "\n", nil
}

func FormatValue(value string) string {
	if value == "" {
		return `""`
	}

	plain := true
	for _, r := range value {
		if !(r == '_' || r == '-' || r == '.' || r == '/' || r == ':' || r == '@' || r >= '0' && r <= '9' || r >= 'A' && r <= 'Z' || r >= 'a' && r <= 'z') {
			plain = false
			break
		}
	}
	if plain {
		return value
	}

	return strconv.Quote(value)
}

func parseLine(line string, lineNumber int) (Entry, bool) {
	trimmed := strings.TrimSpace(line)
	disabled := false

	if strings.HasPrefix(trimmed, "#") {
		candidate := strings.TrimSpace(strings.TrimPrefix(trimmed, "#"))
		if !strings.Contains(candidate, "=") {
			return Entry{}, false
		}
		disabled = true
		trimmed = candidate
	}

	export := false
	if strings.HasPrefix(trimmed, "export ") {
		export = true
		trimmed = strings.TrimSpace(strings.TrimPrefix(trimmed, "export "))
	}

	key, raw, ok := strings.Cut(trimmed, "=")
	if !ok {
		return Entry{}, false
	}

	key, _, err := FormatKey(key)
	if err != nil {
		return Entry{}, false
	}

	return Entry{
		Key:      key,
		Value:    parseValue(strings.TrimSpace(raw)),
		Line:     lineNumber,
		Export:   export,
		Disabled: disabled,
	}, true
}

func parseValue(raw string) string {
	if raw == "" {
		return ""
	}

	if strings.HasPrefix(raw, `"`) {
		value, err := strconv.Unquote(raw)
		if err == nil {
			return value
		}
	}

	if strings.HasPrefix(raw, "'") && strings.HasSuffix(raw, "'") && len(raw) >= 2 {
		return raw[1 : len(raw)-1]
	}

	for i, r := range raw {
		if r == '#' && i > 0 && raw[i-1] == ' ' {
			return strings.TrimSpace(raw[:i])
		}
	}

	return raw
}

func orderedKeys(values map[string]string, preferred []string) []string {
	seen := make(map[string]bool, len(values))
	keys := make([]string, 0, len(values))
	for _, key := range preferred {
		if _, ok := values[key]; ok && !seen[key] {
			keys = append(keys, key)
			seen[key] = true
		}
	}

	rest := make([]string, 0, len(values))
	for key := range values {
		if !seen[key] {
			rest = append(rest, key)
		}
	}
	sort.Strings(rest)
	keys = append(keys, rest...)

	return keys
}

func exportPrefix(export bool) string {
	if export {
		return "export "
	}
	return ""
}
