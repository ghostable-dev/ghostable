package app

import (
	"bytes"
	"encoding/json"
	"fmt"
	"os"
	"path/filepath"
	"strings"
	"testing"

	"github.com/ghostable-dev/ghostable/internal/store"
	"github.com/ghostable-dev/ghostable/internal/userpresence"
)

func TestRunValidateChecksStoredEnvironmentValues(t *testing.T) {
	root := setupRepoForEnvCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("default", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "validate", "--env", "default"}, strings.NewReader(""), &output, &output)
	if err := runner.Run(); err != nil {
		t.Fatal(err)
	}
	if !strings.Contains(output.String(), success("Validation passed.")) {
		t.Fatalf("expected validation success output, got:\n%s", output.String())
	}
}

func TestRunValidateKeepsSchemaRulesInSchemaFiles(t *testing.T) {
	root := setupRepoForEnvCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("default", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(filepath.Join(root, ".ghostable", "schema.yaml"), []byte("APP_NAME:\n  - required\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "validate", "--env", "default"}, strings.NewReader(""), &output, &output)
	if err := runner.Run(); err != nil {
		t.Fatal(err)
	}
	if !strings.Contains(output.String(), success("Validation passed.")) {
		t.Fatalf("expected validation success output, got:\n%s", output.String())
	}

	metadata := readKeyMetadataForTest(t, root, "default")
	encoded, err := json.Marshal(metadata["APP_NAME"])
	if err != nil {
		t.Fatal(err)
	}
	if strings.Contains(string(encoded), "required") {
		t.Fatalf("schema rule should stay in schema files, got key metadata: %s", string(encoded))
	}
}

func TestRunValidateRequiresPresenceForDifferentFromProtectedEnvironment(t *testing.T) {
	root := setupRepoForEnvCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if _, err := repo.CreateEnvironment("production", "production"); err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("default", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(filepath.Join(root, ".ghostable", "schema.yaml"), []byte("APP_NAME:\n  - different_from:production\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	called := false
	withProtectedEnvironmentVerifierForTest(t, func(request userpresence.Request) error {
		called = true
		if request.Environment != "production" || request.Operation != protectedOperationValidate {
			return fmt.Errorf("unexpected user-presence request: %#v", request)
		}
		return fmt.Errorf("blocked")
	})

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "validate", "--env", "default"}, strings.NewReader(""), &output, &output)
	err = runner.Run()
	if err == nil || !strings.Contains(err.Error(), "blocked") {
		t.Fatalf("expected protected environment validation to be blocked, got %v", err)
	}
	if !called {
		t.Fatal("expected different_from protected environment to require local user presence")
	}
}

func TestRunValidateHelp(t *testing.T) {
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "validate", "--help"}, strings.NewReader(""), &output, &output)
	if err := runner.Run(); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	for _, expected := range []string{
		"Usage: ghostable validate [options]",
		"--env <ENV>",
		"--file <PATH>",
		"--json",
	} {
		if !strings.Contains(text, expected) {
			t.Fatalf("expected validate help to contain %q:\n%s", expected, text)
		}
	}
}
