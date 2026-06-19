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
