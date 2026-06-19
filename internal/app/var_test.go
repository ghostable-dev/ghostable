package app

import (
	"bytes"
	"os"
	"path/filepath"
	"strings"
	"testing"

	"github.com/ghostable-dev/beta/internal/prompt"
	"github.com/ghostable-dev/beta/internal/store"
)

func TestRunVarPullSelectsExistingVariable(t *testing.T) {
	root := setupRepoForVarCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("default", "ALPHA", "one", "test"); err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("default", "BETA", "two", "test"); err != nil {
		t.Fatal(err)
	}

	input := strings.NewReader("2\n")
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "var", "pull", "--env", "default", "--show-values"}, input, &output, &output)
	runner.interactive = true
	runner.prompts = prompt.New(input, &output)

	if err := runner.runVarPull(runner.args[3:]); err != nil {
		t.Fatal(err)
	}
	text := output.String()
	if !strings.Contains(text, "Select a variable") {
		t.Fatalf("expected variable selector, got:\n%s", text)
	}
	if !strings.Contains(text, "BETA=two") {
		t.Fatalf("expected selected variable output, got:\n%s", text)
	}
}

func TestRunVarPushSelectsVariableFromFile(t *testing.T) {
	root := setupRepoForVarCommandTest(t)
	envFile := filepath.Join(root, ".env.seed")
	if err := os.WriteFile(envFile, []byte("ALPHA=one\nBETA=two\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	input := strings.NewReader("2\n")
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "var", "push", "--env", "default", "--file", ".env.seed"}, input, &output, &output)
	runner.interactive = true
	runner.prompts = prompt.New(input, &output)

	if err := runner.runVarPush(runner.args[3:]); err != nil {
		t.Fatal(err)
	}
	text := output.String()
	if !strings.Contains(text, "Select a variable") {
		t.Fatalf("expected variable selector, got:\n%s", text)
	}

	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	variable, exists, err := repo.GetVariable("default", "BETA")
	if err != nil {
		t.Fatal(err)
	}
	if !exists || variable.Value != "two" {
		t.Fatalf("expected selected file variable to be stored, got exists=%v variable=%#v", exists, variable)
	}
	if _, exists, err := repo.GetVariable("default", "ALPHA"); err != nil {
		t.Fatal(err)
	} else if exists {
		t.Fatal("did not expect unselected file variable to be stored")
	}
}

func TestRunVarPushAcceptsAbsoluteFilePath(t *testing.T) {
	root := setupRepoForVarCommandTest(t)
	envFile := filepath.Join(t.TempDir(), ".env.seed")
	if err := os.WriteFile(envFile, []byte("ALPHA=one\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "var", "push", "--env", "default", "--key", "ALPHA", "--file", envFile, "--json"}, strings.NewReader(""), &output, &output)

	if err := runner.runVarPush(runner.args[3:]); err != nil {
		t.Fatal(err)
	}

	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	variable, exists, err := repo.GetVariable("default", "ALPHA")
	if err != nil {
		t.Fatal(err)
	}
	if !exists || variable.Value != "one" {
		t.Fatalf("expected absolute file variable to be stored, got exists=%v variable=%#v", exists, variable)
	}
}

func TestRunVarPushStoresCommentedFileVariable(t *testing.T) {
	root := setupRepoForVarCommandTest(t)
	envFile := filepath.Join(t.TempDir(), ".env.seed")
	if err := os.WriteFile(envFile, []byte("# ALPHA=one\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "var", "push", "--env", "default", "--key", "ALPHA", "--file", envFile, "--json"}, strings.NewReader(""), &output, &output)

	if err := runner.runVarPush(runner.args[3:]); err != nil {
		t.Fatal(err)
	}

	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	variable, exists, err := repo.GetVariable("default", "ALPHA")
	if err != nil {
		t.Fatal(err)
	}
	if !exists || variable.Value != "one" || !variable.Commented {
		t.Fatalf("expected commented variable to be stored disabled, got exists=%v variable=%#v", exists, variable)
	}
	if !strings.Contains(output.String(), `"commented": true`) {
		t.Fatalf("expected JSON payload to include commented state, got:\n%s", output.String())
	}
}

func TestRunVarPushStoresVaporSecretMetadata(t *testing.T) {
	root := setupRepoForVarCommandTest(t)
	envFile := filepath.Join(t.TempDir(), ".env.seed")
	if err := os.WriteFile(envFile, []byte("ALPHA=one\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "var", "push", "--env", "default", "--key", "ALPHA", "--file", envFile, "--vapor-secret", "--json"}, strings.NewReader(""), &output, &output)

	if err := runner.runVarPush(runner.args[3:]); err != nil {
		t.Fatal(err)
	}

	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	variable, exists, err := repo.GetVariable("default", "ALPHA")
	if err != nil {
		t.Fatal(err)
	}
	if !exists || !variable.VaporSecret {
		t.Fatalf("expected variable to be marked as a Vapor Secret, got exists=%v variable=%#v", exists, variable)
	}
	if !strings.Contains(output.String(), `"vaporSecret": true`) {
		t.Fatalf("expected JSON payload to include Vapor Secret state, got:\n%s", output.String())
	}
}

func TestRunVarVaporSecretTogglesMetadata(t *testing.T) {
	root := setupRepoForVarCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("default", "ALPHA", "one", "test"); err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariableVaporSecret("default", "ALPHA", true, "test"); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "var", "vapor-secret", "--env", "default", "--key", "ALPHA", "--enabled=false", "--json"}, strings.NewReader(""), &output, &output)
	if err := runner.runVarVaporSecret(runner.args[3:]); err != nil {
		t.Fatal(err)
	}

	variable, exists, err := repo.GetVariable("default", "ALPHA")
	if err != nil {
		t.Fatal(err)
	}
	if !exists || variable.VaporSecret {
		t.Fatalf("expected variable to be unmarked as a Vapor Secret, got exists=%v variable=%#v", exists, variable)
	}
	if !strings.Contains(output.String(), `"vaporSecret": false`) {
		t.Fatalf("expected JSON payload to include disabled Vapor Secret state, got:\n%s", output.String())
	}
}

func TestRunVarPullAcceptsAbsoluteFilePath(t *testing.T) {
	setupRepoForVarCommandTest(t)
	repo, err := store.Open(".")
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("default", "ALPHA", "one", "test"); err != nil {
		t.Fatal(err)
	}

	envFile := filepath.Join(t.TempDir(), ".env.out")
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "var", "pull", "--env", "default", "--key", "ALPHA", "--file", envFile, "--json"}, strings.NewReader(""), &output, &output)

	if err := runner.runVarPull(runner.args[3:]); err != nil {
		t.Fatal(err)
	}

	content, err := os.ReadFile(envFile)
	if err != nil {
		t.Fatal(err)
	}
	if !strings.Contains(string(content), "ALPHA=one") {
		t.Fatalf("expected absolute output file to contain ALPHA, got:\n%s", string(content))
	}
}

func setupRepoForVarCommandTest(t *testing.T) string {
	t.Helper()
	return setupRepoForEnvCommandTest(t)
}
