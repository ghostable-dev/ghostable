//go:build !windows && !darwin

package userpresence

import "testing"

func TestSudoPromptUsesConfirmationMessage(t *testing.T) {
	tests := []struct {
		request Request
		want    string
	}{
		{
			request: Request{Environment: "production", Operation: "deploy"},
			want:    "access Ghostable production secrets for deploy Password: ",
		},
		{
			request: Request{Environment: "production", Operation: "env.run"},
			want:    "access Ghostable production secrets for env run Password: ",
		},
	}

	for _, tt := range tests {
		if got := sudoPrompt(tt.request); got != tt.want {
			t.Fatalf("expected sudo prompt %q, got %q", tt.want, got)
		}
	}
}
