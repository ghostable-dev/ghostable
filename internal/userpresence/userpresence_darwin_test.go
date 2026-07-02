//go:build darwin

package userpresence

import (
	"strconv"
	"strings"
	"testing"
)

func TestMacOSBiometricVerificationScriptUsesLocalAuthentication(t *testing.T) {
	message := "production \"quoted\" secrets; $.exit(0)"

	script := macOSBiometricVerificationScript(message)

	for _, expected := range []string{
		"ObjC.import('LocalAuthentication')",
		"LAPolicyDeviceOwnerAuthenticationWithBiometrics",
		"evaluatePolicyLocalizedReasonReply",
		"$.exit(0)",
		"$(" + strconv.Quote(message) + ")",
	} {
		if !strings.Contains(script, expected) {
			t.Fatalf("expected macOS script to contain %q, got:\n%s", expected, script)
		}
	}
}
