package dotenv

import (
	"strings"
	"testing"
)

func TestParseAndMergePreservesComments(t *testing.T) {
	input := strings.Join([]string{
		"# app settings",
		"APP_NAME=Ghostable",
		"export APP_ENV=local",
		"",
	}, "\n")

	next, err := Merge(input, map[string]string{
		"APP_ENV": "production",
		"APP_KEY": "base64:abc 123",
	}, []string{"APP_NAME", "APP_ENV", "APP_KEY"}, false)
	if err != nil {
		t.Fatal(err)
	}

	if !strings.Contains(next, "# app settings") {
		t.Fatalf("expected comment to be preserved: %q", next)
	}
	if !strings.Contains(next, "export APP_ENV=production") {
		t.Fatalf("expected export line to update: %q", next)
	}
	if !strings.Contains(next, `APP_KEY="base64:abc 123"`) {
		t.Fatalf("expected quoted inserted value: %q", next)
	}
}

func TestParseStringIgnoresDisabledComments(t *testing.T) {
	values, err := ParseString("# API_KEY=disabled\nAPP_NAME=Ghostable\n")
	if err != nil {
		t.Fatal(err)
	}
	if _, ok := values["API_KEY"]; ok {
		t.Fatal("disabled commented value should not be active")
	}
	if values["APP_NAME"] != "Ghostable" {
		t.Fatalf("unexpected APP_NAME: %q", values["APP_NAME"])
	}
}

func TestParseStringFormatsKeysAndKeepsLastValue(t *testing.T) {
	values, err := ParseString("app-name=old\nAPP_NAME=new\n1password=secret\n")
	if err != nil {
		t.Fatal(err)
	}

	if values["APP_NAME"] != "new" {
		t.Fatalf("expected normalized APP_NAME to use last value, got %#v", values)
	}
	if values["_1PASSWORD"] != "secret" {
		t.Fatalf("expected digit-leading key to be normalized, got %#v", values)
	}
	if _, ok := values["app-name"]; ok {
		t.Fatalf("did not expect raw app-name key, got %#v", values)
	}
}

func TestMergeFormatsExistingKeys(t *testing.T) {
	next, err := Merge("app-name=Old\n", map[string]string{"APP_NAME": "New"}, []string{"APP_NAME"}, false)
	if err != nil {
		t.Fatal(err)
	}
	if next != "APP_NAME=New\n" {
		t.Fatalf("expected existing key to be normalized during merge, got %q", next)
	}
}

func TestMergeRemovesEarlierDuplicateFormattedKeys(t *testing.T) {
	next, err := Merge("app-name=Old\nAPP_NAME=Older\nOTHER=value\n", map[string]string{"APP_NAME": "New"}, []string{"APP_NAME"}, false)
	if err != nil {
		t.Fatal(err)
	}

	expected := "APP_NAME=New\nOTHER=value\n"
	if next != expected {
		t.Fatalf("expected duplicate formatted keys to collapse to last key, got %q", next)
	}
}

func TestFormatKeyMatchesManualEntryRules(t *testing.T) {
	formatted, changed, err := FormatKey("bad key")
	if err != nil {
		t.Fatal(err)
	}
	if formatted != "BAD_KEY" || !changed {
		t.Fatalf("expected bad key to format as BAD_KEY, got formatted=%q changed=%v", formatted, changed)
	}
}
