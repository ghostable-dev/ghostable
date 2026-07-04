package hygiene

import (
	"os"
	"path/filepath"
	"strings"
	"testing"
)

func TestParseResolvesProjectAndEnvironmentRotationRules(t *testing.T) {
	policy, err := Parse(strings.NewReader(`
rotation:
  keys:
    STRIPE_SECRET_KEY:
      rotationAfterDays: 90
  environments:
    production:
      keys:
        STRIPE_SECRET_KEY:
          rotationAfterDays: 60
        POSTMARK_API_TOKEN:
          rotationAfterDays: 180
`))
	if err != nil {
		t.Fatal(err)
	}

	projectRule, ok := ResolveRotationRule(policy, "staging", "STRIPE_SECRET_KEY")
	if !ok {
		t.Fatal("expected project rotation rule to resolve")
	}
	if projectRule.Rule.RotationAfterDays != 90 || projectRule.Source != "project" {
		t.Fatalf("expected project rule, got %#v", projectRule)
	}

	envRule, ok := ResolveRotationRule(policy, "production", "STRIPE_SECRET_KEY")
	if !ok {
		t.Fatal("expected environment rotation rule to resolve")
	}
	if envRule.Rule.RotationAfterDays != 60 || envRule.Source != "environment" {
		t.Fatalf("expected environment rule, got %#v", envRule)
	}

	envOnlyRule, ok := ResolveRotationRule(policy, "production", "POSTMARK_API_TOKEN")
	if !ok {
		t.Fatal("expected environment-only rotation rule to resolve")
	}
	if envOnlyRule.Rule.RotationAfterDays != 180 || envOnlyRule.Source != "environment" {
		t.Fatalf("expected environment-only rule, got %#v", envOnlyRule)
	}
}

func TestFormatWritesDeterministicRotationPolicy(t *testing.T) {
	policy := Policy{}
	SetEnvironmentRotationRule(&policy, "production", "STRIPE_SECRET_KEY", RotationRule{RotationAfterDays: 60})
	SetProjectRotationRule(&policy, "POSTMARK_API_TOKEN", RotationRule{RotationAfterDays: 180})
	SetProjectRotationRule(&policy, "STRIPE_SECRET_KEY", RotationRule{RotationAfterDays: 90})

	expected := `rotation:
  keys:
    POSTMARK_API_TOKEN:
      rotationAfterDays: 180
    STRIPE_SECRET_KEY:
      rotationAfterDays: 90
  environments:
    production:
      keys:
        STRIPE_SECRET_KEY:
          rotationAfterDays: 60
`
	if got := Format(policy); got != expected {
		t.Fatalf("unexpected formatted policy:\n%s", got)
	}
}

func TestRemoveRotationRulesPrunesEmptyPolicySections(t *testing.T) {
	policy := Policy{}
	SetProjectRotationRule(&policy, "STRIPE_SECRET_KEY", RotationRule{RotationAfterDays: 90})
	SetEnvironmentRotationRule(&policy, "production", "STRIPE_SECRET_KEY", RotationRule{RotationAfterDays: 60})

	if !RemoveProjectRotationRule(&policy, "STRIPE_SECRET_KEY") {
		t.Fatal("expected project rule to be removed")
	}
	if _, ok := policy.Rotation.Keys["STRIPE_SECRET_KEY"]; ok {
		t.Fatal("expected project rule to be removed from policy")
	}
	if !RemoveEnvironmentRotationRule(&policy, "production", "STRIPE_SECRET_KEY") {
		t.Fatal("expected environment rule to be removed")
	}
	if got := Format(policy); got != "" {
		t.Fatalf("expected empty policy after removals, got:\n%s", got)
	}
}

func TestWriteFileRejectsSymlinkedPolicyTarget(t *testing.T) {
	root := t.TempDir()
	policyPath := filepath.Join(root, ".ghostable", "hygiene.yaml")
	if err := os.MkdirAll(filepath.Dir(policyPath), 0o755); err != nil {
		t.Fatal(err)
	}
	outside := filepath.Join(t.TempDir(), "outside.yaml")
	if err := os.Symlink(outside, policyPath); err != nil {
		t.Skipf("symlink unavailable: %v", err)
	}

	err := WriteFile(policyPath, Policy{})
	if err == nil || !strings.Contains(err.Error(), "symlinked path") {
		t.Fatalf("expected symlinked policy target to be rejected, got %v", err)
	}
	if _, statErr := os.Stat(outside); !os.IsNotExist(statErr) {
		t.Fatalf("expected outside target to remain unwritten, stat err: %v", statErr)
	}
}

func TestWriteFileRejectsSymlinkedGhostableDirectory(t *testing.T) {
	root := t.TempDir()
	outside := t.TempDir()
	if err := os.Symlink(outside, filepath.Join(root, ".ghostable")); err != nil {
		t.Skipf("symlink unavailable: %v", err)
	}

	err := WriteFile(filepath.Join(root, ".ghostable", "hygiene.yaml"), Policy{})
	if err == nil || !strings.Contains(err.Error(), "symlinked path") {
		t.Fatalf("expected symlinked .ghostable directory to be rejected, got %v", err)
	}
	if _, statErr := os.Stat(filepath.Join(outside, "hygiene.yaml")); !os.IsNotExist(statErr) {
		t.Fatalf("expected outside hygiene policy to remain unwritten, stat err: %v", statErr)
	}
}
