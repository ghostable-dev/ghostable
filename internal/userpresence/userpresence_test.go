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

func TestConfirmationMessageAndOperationLabels(t *testing.T) {
	tests := []struct {
		request Request
		want    string
	}{
		{
			request: Request{Environment: "production", Operation: "env.pull"},
			want:    "access Ghostable production secrets for env pull",
		},
		{
			request: Request{Environment: " live ", Operation: "env.run"},
			want:    "access Ghostable live secrets for env run",
		},
		{
			request: Request{Environment: "", Operation: "var.pull"},
			want:    "access Ghostable protected environment secrets for var pull",
		},
		{
			request: Request{Environment: "production", Operation: "deploy"},
			want:    "access Ghostable production secrets for deploy",
		},
	}

	for _, tt := range tests {
		if got := confirmationMessage(tt.request); got != tt.want {
			t.Fatalf("expected %q, got %q", tt.want, got)
		}
	}
}
