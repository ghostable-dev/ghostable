//go:build windows

package userpresence

import (
	"encoding/base64"
	"strings"
	"testing"
	"unicode/utf16"
)

func TestEncodePowerShellCommandUsesUTF16LEBase64(t *testing.T) {
	script := "Write-Output 'ok'"

	encoded := encodePowerShellCommand(script)
	decoded, err := base64.StdEncoding.DecodeString(encoded)
	if err != nil {
		t.Fatal(err)
	}
	if len(decoded)%2 != 0 {
		t.Fatalf("expected UTF-16LE bytes to have even length, got %d", len(decoded))
	}

	chars := make([]uint16, 0, len(decoded)/2)
	for i := 0; i < len(decoded); i += 2 {
		chars = append(chars, uint16(decoded[i])|uint16(decoded[i+1])<<8)
	}
	if got := string(utf16.Decode(chars)); got != script {
		t.Fatalf("expected encoded script to round trip to %q, got %q", script, got)
	}
}

func TestWindowsUserConsentScriptQuotesMessageAndUsesVerifier(t *testing.T) {
	script := windowsUserConsentScript("prod's secrets")

	for _, expected := range []string{
		"Windows.Security.Credentials.UI.UserConsentVerifier",
		"RequestVerificationAsync('prod''s secrets')",
		"UserConsentVerificationResult]::Verified",
		"AsTask",
	} {
		if !strings.Contains(script, expected) {
			t.Fatalf("expected Windows script to contain %q, got:\n%s", expected, script)
		}
	}
}
