package app

import (
	"bytes"
	"os"
	"path/filepath"
	"strings"
	"testing"

	"github.com/ghostable-dev/ghostable/v3/internal/domain"
	"github.com/ghostable-dev/ghostable/v3/internal/store"
)

func TestRunDeployCloudInvokesLaravelCloudCLI(t *testing.T) {
	root := setupDeployCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "API_KEY", "secret", "test"); err != nil {
		t.Fatal(err)
	}

	binDir := t.TempDir()
	logPath := filepath.Join(t.TempDir(), "cloud.log")
	unixScript := "#!/bin/sh\n" +
		"echo \"$@\" >> \"$CLOUD_LOG\"\n" +
		"cat >/dev/null\n" +
		"exit 0\n"
	windowsScript := "@echo off\r\n" +
		"echo %* >> \"%CLOUD_LOG%\"\r\n" +
		"set /p CLOUD_VALUE=\r\n" +
		"exit /b 0\r\n"
	writeFakeExecutable(t, binDir, "cloud", unixScript, windowsScript)
	prependPathForTest(t, binDir)
	t.Setenv("CLOUD_LOG", logPath)

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "deploy", "laravel-cloud", "production", "--cloud-env", "cloud-prod", "--only", "APP_NAME"}, strings.NewReader(""), &output, &output)
	if err := runner.Run(); err != nil {
		t.Fatal(err)
	}

	logContent, err := os.ReadFile(logPath)
	if err != nil {
		t.Fatal(err)
	}
	logText := string(logContent)
	for _, expected := range []string{
		"environment:variables cloud-prod --json --no-interaction --action=set --key=APP_NAME --value-stdin",
	} {
		if !strings.Contains(logText, expected) {
			t.Fatalf("expected Laravel Cloud CLI log to contain %q, got:\n%s", expected, logText)
		}
	}
	if strings.Contains(logText, "Ghostable") {
		t.Fatalf("did not expect Laravel Cloud value to be passed in argv, got:\n%s", logText)
	}
	if strings.Contains(logText, "API_KEY") {
		t.Fatalf("did not expect --only filtered key to be synced, got:\n%s", logText)
	}
	if _, err := os.Stat(filepath.Join(root, ".env")); !os.IsNotExist(err) {
		t.Fatalf("Laravel Cloud deploy should not write .env, stat err: %v", err)
	}
	if !strings.Contains(output.String(), "👻 Ghostable Laravel Cloud deploy successful.") {
		t.Fatalf("expected Cloud deploy success output, got:\n%s", output.String())
	}
}

func TestRunDeployLaravelCloudUsesCloudTarget(t *testing.T) {
	root := setupDeployCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "deploy", "laravel-cloud", "production", "--dry-run", "--json"}, strings.NewReader(""), &output, &output)
	if err := runner.Run(); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	if !strings.Contains(text, `"target": "laravel-cloud"`) || !strings.Contains(text, `"provider": "Laravel Cloud"`) {
		t.Fatalf("expected laravel-cloud route to use Cloud target JSON, got:\n%s", text)
	}
	if _, err := os.Stat(filepath.Join(root, ".env")); !os.IsNotExist(err) {
		t.Fatalf("dry-run should not write .env, stat err: %v", err)
	}
}

func TestRunDeployCloudRedactsValueFromCLIError(t *testing.T) {
	root := setupDeployCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}

	binDir := t.TempDir()
	unixScript := "#!/bin/sh\n" +
		"value=$(cat)\n" +
		"echo \"failed with $@ value=$value\" >&2\n" +
		"exit 1\n"
	windowsScript := "@echo off\r\n" +
		"set /p CLOUD_VALUE=\r\n" +
		"echo failed with %* value=%CLOUD_VALUE% 1>&2\r\n" +
		"exit /b 1\r\n"
	writeFakeExecutable(t, binDir, "cloud", unixScript, windowsScript)
	prependPathForTest(t, binDir)

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "deploy", "laravel-cloud", "production"}, strings.NewReader(""), &output, &output)
	err = runner.Run()
	if err == nil {
		t.Fatal("expected Laravel Cloud CLI failure")
	}
	if strings.Contains(err.Error(), "Ghostable") {
		t.Fatalf("expected CLI error to redact variable value, got %v", err)
	}
	if !strings.Contains(err.Error(), "[redacted]") {
		t.Fatalf("expected CLI error to include redaction marker, got %v", err)
	}
}

func TestRunDeployCloudRejectsProjectLocalCloudCLI(t *testing.T) {
	root := setupDeployCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}

	binDir := filepath.Join(root, "bin")
	if err := os.MkdirAll(binDir, 0o755); err != nil {
		t.Fatal(err)
	}
	writeFakeExecutable(t, binDir, "cloud", "", "")
	prependPathForTest(t, binDir)

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "deploy", "laravel-cloud", "production"}, strings.NewReader(""), &output, &output)
	if err := runner.Run(); err == nil || !strings.Contains(err.Error(), "refusing to run Laravel Cloud CLI from project path") {
		t.Fatalf("expected project-local Laravel Cloud CLI to be rejected, got %v", err)
	}
}

func TestRunDeployLaravelVaporUsesVaporTarget(t *testing.T) {
	root := setupDeployCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "deploy", "laravel-vapor", "production", "--dry-run"}, strings.NewReader(""), &output, &output)
	if err := runner.Run(); err != nil {
		t.Fatal(err)
	}

	if !strings.Contains(output.String(), "👻 Ghostable Vapor deploy plan.") {
		t.Fatalf("expected laravel-vapor route to use Vapor deploy path, got:\n%s", output.String())
	}
}

func TestRunDeployVaporRedactsValueFromCLIError(t *testing.T) {
	root := setupDeployCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}

	binDir := t.TempDir()
	unixScript := "#!/bin/sh\n" +
		"file=${3#--file=}\n" +
		"if [ \"$1\" = \"env:pull\" ]; then printf 'EXISTING=1\\n' > \"$file\"; exit 0; fi\n" +
		"if [ \"$1\" = \"env:push\" ]; then cat \"$file\" >&2; exit 1; fi\n" +
		"exit 0\n"
	windowsScript := "@echo off\r\n" +
		"set GHOSTABLE_FAKE_VAPOR_CLI=1\r\n" +
		"set GHOSTABLE_FAKE_VAPOR_PUSH_STDERR=1\r\n" +
		"call " + windowsCommandLineQuote(os.Args[0]) + " -test.run=TestFakeVaporCLIHelperProcess -- %*\r\n" +
		"exit /b %ERRORLEVEL%\r\n"
	writeFakeExecutable(t, binDir, "vapor", unixScript, windowsScript)
	prependPathForTest(t, binDir)

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "deploy", "laravel-vapor", "production"}, strings.NewReader(""), &output, &output)
	err = runner.Run()
	if err == nil {
		t.Fatal("expected Vapor CLI failure")
	}
	if strings.Contains(err.Error(), "Ghostable") {
		t.Fatalf("expected Vapor CLI error to redact variable value, got %v", err)
	}
	if !strings.Contains(err.Error(), "[redacted]") {
		t.Fatalf("expected Vapor CLI error to include redaction marker, got %v", err)
	}
}

func TestRunDeployShortProviderTargetsRouteToProviders(t *testing.T) {
	root := setupDeployCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}

	tests := []struct {
		name     string
		args     []string
		expected string
	}{
		{name: "cloud", args: []string{"ghostable", "deploy", "cloud", "production", "--dry-run", "--json"}, expected: `"target": "laravel-cloud"`},
		{name: "vapor", args: []string{"ghostable", "deploy", "vapor", "production", "--dry-run"}, expected: "Ghostable Vapor deploy plan"},
		{name: "forge", args: []string{"ghostable", "deploy", "forge", "production", "--forge-site", "example.com", "--dry-run", "--json"}, expected: `"target": "laravel-forge"`},
	}

	for _, test := range tests {
		t.Run(test.name, func(t *testing.T) {
			var output bytes.Buffer
			runner := NewRunner(test.args, strings.NewReader(""), &output, &output)
			if err := runner.Run(); err != nil {
				t.Fatal(err)
			}
			if !strings.Contains(output.String(), test.expected) {
				t.Fatalf("expected short provider target to route to %q, got:\n%s", test.expected, output.String())
			}
		})
	}
}

func TestRunDeployUsesManifestDeployTargetByDefault(t *testing.T) {
	root := setupDeployCommandTestWithTarget(t, "laravel-cloud")
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "deploy", "production", "--dry-run", "--json"}, strings.NewReader(""), &output, &output)
	if err := runner.Run(); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	if !strings.Contains(text, `"target": "laravel-cloud"`) || !strings.Contains(text, `"cloudEnvironment": "production"`) {
		t.Fatalf("expected manifest deploy target to route to Laravel Cloud, got:\n%s", text)
	}
	if _, err := os.Stat(filepath.Join(root, ".env")); !os.IsNotExist(err) {
		t.Fatalf("provider deploy dry-run should not write .env, stat err: %v", err)
	}
}

func TestRunDeployLocalFileOptionsBypassManifestDeployTarget(t *testing.T) {
	root := setupDeployCommandTestWithTarget(t, "laravel-cloud")
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "deploy", "production", "--file", ".env.deploy", "--dry-run", "--json"}, strings.NewReader(""), &output, &output)
	if err := runner.Run(); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	if strings.Contains(text, `"target": "laravel-cloud"`) {
		t.Fatalf("expected local file option to bypass provider target, got:\n%s", text)
	}
	if !strings.Contains(text, `"file": ".env.deploy"`) || !strings.Contains(text, `"valueOmitted": true`) {
		t.Fatalf("expected local deploy dry-run JSON, got:\n%s", text)
	}
}

func TestRunDeployForgeInvokesLaravelForgeCLI(t *testing.T) {
	root := setupDeployCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "API_KEY", "secret", "test"); err != nil {
		t.Fatal(err)
	}

	binDir := t.TempDir()
	logPath := filepath.Join(t.TempDir(), "forge.log")
	unixScript := "#!/bin/sh\n" +
		"echo \"$@\" >> \"$FORGE_LOG\"\n" +
		"if [ \"$1\" = \"env:pull\" ]; then printf 'EXISTING=1\\n' > \"$3\"; exit 0; fi\n" +
		"if [ \"$1\" = \"env:push\" ]; then cat \"$3\" >> \"$FORGE_LOG\"; exit 0; fi\n" +
		"exit 0\n"
	windowsScript := "@echo off\r\n" +
		"echo %* >> \"%FORGE_LOG%\"\r\n" +
		"if \"%~1\"==\"env:pull\" (\r\n" +
		"  >\"%~3\" echo EXISTING=1\r\n" +
		"  exit /b 0\r\n" +
		")\r\n" +
		"if \"%~1\"==\"env:push\" (\r\n" +
		"  type \"%~3\" >> \"%FORGE_LOG%\"\r\n" +
		"  exit /b 0\r\n" +
		")\r\n" +
		"exit /b 0\r\n"
	writeFakeExecutable(t, binDir, "forge", unixScript, windowsScript)
	prependPathForTest(t, binDir)
	t.Setenv("FORGE_LOG", logPath)

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "deploy", "laravel-forge", "production", "--forge-site", "example.com", "--only", "APP_NAME"}, strings.NewReader(""), &output, &output)
	if err := runner.Run(); err != nil {
		t.Fatal(err)
	}

	logContent, err := os.ReadFile(logPath)
	if err != nil {
		t.Fatal(err)
	}
	logText := string(logContent)
	for _, expected := range []string{
		"env:pull example.com ",
		"env:push example.com ",
		"EXISTING=1",
		"APP_NAME=Ghostable",
	} {
		if !strings.Contains(logText, expected) {
			t.Fatalf("expected Laravel Forge CLI log to contain %q, got:\n%s", expected, logText)
		}
	}
	if strings.Contains(logText, "API_KEY") {
		t.Fatalf("did not expect --only filtered key to be synced, got:\n%s", logText)
	}
	if _, err := os.Stat(filepath.Join(root, ".env")); !os.IsNotExist(err) {
		t.Fatalf("Laravel Forge deploy should not write .env, stat err: %v", err)
	}

	text := output.String()
	if !strings.Contains(text, "👻 Ghostable Laravel Forge deploy successful.") ||
		!strings.Contains(text, warn("Forge site:")+" example.com") {
		t.Fatalf("expected Forge deploy output to include provider success and site details, got:\n%s", text)
	}
}

func setupDeployCommandTestWithTarget(t *testing.T, deployTarget string) string {
	t.Helper()
	allowProtectedEnvironmentAccessForTest(t)
	root := setupTempWorkdir(t)
	_, _, err := store.Setup(".", store.SetupOptions{
		Name: "Test Project",
		Environments: []domain.Environment{
			{Name: "default", Type: "local"},
			{Name: "production", Type: "production"},
		},
		DeviceName:   "test-device",
		DeployTarget: deployTarget,
	})
	if err != nil {
		t.Fatal(err)
	}
	return root
}

func TestRunDeployForgeRejectsProjectLocalForgeCLI(t *testing.T) {
	root := setupDeployCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}

	binDir := filepath.Join(root, "bin")
	if err := os.MkdirAll(binDir, 0o755); err != nil {
		t.Fatal(err)
	}
	writeFakeExecutable(t, binDir, "forge", "", "")
	prependPathForTest(t, binDir)

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "deploy", "laravel-forge", "production", "--forge-site", "example.com"}, strings.NewReader(""), &output, &output)
	if err := runner.Run(); err == nil || !strings.Contains(err.Error(), "refusing to run Laravel Forge CLI from project path") {
		t.Fatalf("expected project-local Laravel Forge CLI to be rejected, got %v", err)
	}
}

func TestRunDeployForgeRequiresSite(t *testing.T) {
	setupDeployCommandTest(t)

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "deploy", "laravel-forge", "production", "--dry-run"}, strings.NewReader(""), &output, &output)
	err := runner.Run()
	if err == nil || !strings.Contains(err.Error(), "Laravel Forge site is required") {
		t.Fatalf("expected Forge deploy to require --forge-site, got %v", err)
	}
}

func TestRunDeployForgeRedactsValuesFromCLIError(t *testing.T) {
	root := setupDeployCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("production", "APP_NAME", "Ghostable", "test"); err != nil {
		t.Fatal(err)
	}

	binDir := t.TempDir()
	unixScript := "#!/bin/sh\n" +
		"echo \"failed with Ghostable\" >&2\n" +
		"exit 1\n"
	windowsScript := "@echo off\r\n" +
		"echo failed with Ghostable 1>&2\r\n" +
		"exit /b 1\r\n"
	writeFakeExecutable(t, binDir, "forge", unixScript, windowsScript)
	prependPathForTest(t, binDir)

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "deploy", "laravel-forge", "production", "--forge-site", "example.com"}, strings.NewReader(""), &output, &output)
	err = runner.Run()
	if err == nil {
		t.Fatal("expected Laravel Forge CLI failure")
	}
	if strings.Contains(err.Error(), "Ghostable") {
		t.Fatalf("expected CLI error to redact variable value, got %v", err)
	}
	if !strings.Contains(err.Error(), "[redacted]") {
		t.Fatalf("expected CLI error to include redaction marker, got %v", err)
	}
}
