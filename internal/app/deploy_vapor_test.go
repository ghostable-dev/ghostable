package app

import (
	"bytes"
	"os"
	"path/filepath"
	"strings"
	"testing"

	"github.com/ghostable-dev/beta/internal/store"
)

func TestRunDeployVaporDryRunUsesVaporSecretMetadata(t *testing.T) {
	root := setupDeployCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_KEY", "secret", "test"); err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariableVaporSecret("production", "APP_KEY", true, "test"); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "deploy", "vapor", "production", "--dry-run"}, strings.NewReader(""), &output, &output)
	if err := runner.Run(); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	for _, expected := range []string{
		"👻 Ghostable Vapor deploy plan.",
		warn("Environment:") + " production",
		warn("Vapor environment:") + " production",
		warn("Env vars:") + " 1",
		warn("Vapor secrets:") + " 1",
	} {
		if !strings.Contains(text, expected) {
			t.Fatalf("expected Vapor dry run output to contain %q, got:\n%s", expected, text)
		}
	}
}

func TestRunDeployVaporInvokesVaporCLI(t *testing.T) {
	root := setupDeployCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_KEY", "secret", "test"); err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariableVaporSecret("production", "APP_KEY", true, "test"); err != nil {
		t.Fatal(err)
	}

	binDir := t.TempDir()
	logPath := filepath.Join(t.TempDir(), "vapor.log")
	vaporPath := filepath.Join(binDir, "vapor")
	script := "#!/bin/sh\n" +
		"echo \"$@\" >> \"$VAPOR_LOG\"\n" +
		"if [ \"$1\" = \"env:pull\" ]; then touch \".env.$2\"; fi\n" +
		"exit 0\n"
	if err := os.WriteFile(vaporPath, []byte(script), 0o700); err != nil {
		t.Fatal(err)
	}
	t.Setenv("PATH", binDir+string(os.PathListSeparator)+os.Getenv("PATH"))
	t.Setenv("VAPOR_LOG", logPath)

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "deploy", "vapor", "production", "--vapor-env", "staging"}, strings.NewReader(""), &output, &output)
	if err := runner.Run(); err != nil {
		t.Fatal(err)
	}

	logContent, err := os.ReadFile(logPath)
	if err != nil {
		t.Fatal(err)
	}
	logText := string(logContent)
	for _, expected := range []string{
		"env:pull staging",
		"env:push staging",
		"secret staging --name=APP_KEY --file=",
	} {
		if !strings.Contains(logText, expected) {
			t.Fatalf("expected Vapor CLI log to contain %q, got:\n%s", expected, logText)
		}
	}

	envContent, err := os.ReadFile(filepath.Join(root, ".env.staging"))
	if err != nil {
		t.Fatal(err)
	}
	if !strings.Contains(string(envContent), "APP_NAME=Ghostable") {
		t.Fatalf("expected Vapor env file to contain normal variable, got:\n%s", string(envContent))
	}
	if strings.Contains(string(envContent), "APP_KEY=secret") {
		t.Fatalf("did not expect Vapor Secret value in normal env file, got:\n%s", string(envContent))
	}
	if !strings.Contains(output.String(), "👻 Ghostable Vapor deploy successful.") {
		t.Fatalf("expected successful Vapor deploy output, got:\n%s", output.String())
	}
}
