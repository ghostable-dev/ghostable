package userpresence

import (
	"strings"
	"testing"
)

func TestVerifyNonInteractiveProtectedAccessFailsBeforePlatformPrompt(t *testing.T) {
	err := Verify(Request{
		Environment: "production",
		Operation:   "env.pull",
		Interactive: false,
	})
	if err == nil {
		t.Fatal("expected non-interactive protected access to fail")
	}

	text := err.Error()
	for _, expected := range []string{
		`protected environment "production"`,
		"requires local user confirmation",
		"env.pull",
		"GHOSTABLE_CI_TOKEN",
	} {
		if !strings.Contains(text, expected) {
			t.Fatalf("expected error to contain %q, got %q", expected, text)
		}
	}
}
