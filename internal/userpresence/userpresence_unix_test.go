//go:build !windows && !darwin

package userpresence

import "testing"

func TestSudoPromptUsesConfirmationMessage(t *testing.T) {
	prompt := sudoPrompt(Request{Environment: "production", Operation: "deploy"})
	expected := "access Ghostable production secrets for deploy Password: "
	if prompt != expected {
		t.Fatalf("expected sudo prompt %q, got %q", expected, prompt)
	}
}
