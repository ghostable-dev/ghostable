package app

import (
	"bytes"
	"encoding/json"
	"os"
	"path/filepath"
	"strings"
	"testing"

	"github.com/ghostable-dev/ghostable/v3/internal/manifest"
	"github.com/ghostable-dev/ghostable/v3/internal/store"
)

func TestRunScanAppliesSignedSuppression(t *testing.T) {
	root := setupRepoForEnvCommandTest(t)
	secretPath := filepath.Join(root, "config.env")
	if err := os.WriteFile(secretPath, []byte("APP_SECRET=abc123!abc123!abc123!\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	var suppressOutput bytes.Buffer
	suppressRunner := NewRunner([]string{
		"ghostable", "scan", "suppress",
		"--path", "config.env",
		"--line", "1",
		"--kind", "Secret assignment",
		"--key", "APP_SECRET",
	}, strings.NewReader(""), &suppressOutput, &suppressOutput)
	if err := suppressRunner.Run(); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(secretPath, []byte("# moved down\nAPP_SECRET=abc123!abc123!abc123!\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "scan", "config.env"}, strings.NewReader(""), &output, &output)
	if err := runner.Run(); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	if !strings.Contains(text, "No hard-coded secrets found") {
		t.Fatalf("expected scan to pass after suppression, got:\n%s", text)
	}
	if !strings.Contains(text, "Suppressed:") {
		t.Fatalf("expected suppressed finding count, got:\n%s", text)
	}
}

func TestRunScanSuppressesOnlySelectedDuplicateFinding(t *testing.T) {
	root := setupRepoForEnvCommandTest(t)
	secretPath := filepath.Join(root, "config.env")
	contents := strings.Join([]string{
		"APP_SECRET=abc123!abc123!abc123!",
		"APP_SECRET=abc123!abc123!abc123!",
		"",
	}, "\n")
	if err := os.WriteFile(secretPath, []byte(contents), 0o600); err != nil {
		t.Fatal(err)
	}

	var suppressOutput bytes.Buffer
	suppressRunner := NewRunner([]string{
		"ghostable", "scan", "suppress",
		"--path", "config.env",
		"--line", "1",
		"--kind", "Secret assignment",
		"--key", "APP_SECRET",
	}, strings.NewReader(""), &suppressOutput, &suppressOutput)
	if err := suppressRunner.Run(); err != nil {
		t.Fatal(err)
	}

	movedContents := strings.Join([]string{
		"# moved down",
		"APP_SECRET=abc123!abc123!abc123!",
		"APP_SECRET=abc123!abc123!abc123!",
		"",
	}, "\n")
	if err := os.WriteFile(secretPath, []byte(movedContents), 0o600); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "scan", "config.env"}, strings.NewReader(""), &output, &output)
	err := runner.Run()
	if err == nil {
		t.Fatalf("expected scan to still report the unsuppressed duplicate, got nil error:\n%s", output.String())
	}

	text := output.String()
	if !strings.Contains(text, "config.env:3:12") {
		t.Fatalf("expected second duplicate to remain unsuppressed, got:\n%s", text)
	}
	if !strings.Contains(text, "Suppressed:") || !strings.Contains(text, "1 finding suppressed.") {
		t.Fatalf("expected only one duplicate to be suppressed, got:\n%s", text)
	}
}

func TestRunScanJSONReturnsErrorWhenFindingsRemain(t *testing.T) {
	root := setupRepoForEnvCommandTest(t)
	secretPath := filepath.Join(root, "config.env")
	if err := os.WriteFile(secretPath, []byte("APP_SECRET=abc123!abc123!abc123!\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "scan", "--json", "config.env"}, strings.NewReader(""), &output, &output)
	err := runner.Run()
	if err == nil {
		t.Fatalf("expected scan --json to fail when findings remain, got nil error:\n%s", output.String())
	}
	if !strings.Contains(err.Error(), "found 1 possible secret") {
		t.Fatalf("expected finding count error, got %v", err)
	}

	var payload struct {
		Findings []struct {
			Path string `json:"path"`
		} `json:"findings"`
		HasSecrets bool `json:"hasSecrets"`
	}
	if jsonErr := json.Unmarshal(output.Bytes(), &payload); jsonErr != nil {
		t.Fatalf("expected valid scan JSON, got %v:\n%s", jsonErr, output.String())
	}
	if !payload.HasSecrets || len(payload.Findings) != 1 || payload.Findings[0].Path != "config.env" {
		t.Fatalf("expected one JSON finding for config.env, got %#v", payload)
	}
}

func TestRunScanDoesNotHonorManifestIgnoresByDefault(t *testing.T) {
	root := setupRepoForEnvCommandTest(t)
	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	project := repo.Manifest
	project.ScanIgnores = []string{"config.env"}
	project.ScanLevel = "relaxed"
	manifestFile, err := os.Create(filepath.Join(root, ".ghostable", "ghostable.yaml"))
	if err != nil {
		t.Fatal(err)
	}
	if err := manifest.Write(manifestFile, project); err != nil {
		_ = manifestFile.Close()
		t.Fatal(err)
	}
	if err := manifestFile.Close(); err != nil {
		t.Fatal(err)
	}

	secretPath := filepath.Join(root, "config.env")
	if err := os.WriteFile(secretPath, []byte("APP_SECRET=abc123!abc123!abc123!\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "scan", "config.env"}, strings.NewReader(""), &output, &output)
	err = runner.Run()
	if err == nil {
		t.Fatalf("expected scan to report config.env despite manifest ignore, got nil error:\n%s", output.String())
	}
	if !strings.Contains(output.String(), "config.env") {
		t.Fatalf("expected ignored file to be scanned, got:\n%s", output.String())
	}
}
