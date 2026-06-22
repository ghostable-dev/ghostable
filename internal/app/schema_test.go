package app

import (
	"bytes"
	"encoding/base64"
	"os"
	"path/filepath"
	"strings"
	"testing"
)

func TestRunSchemaFileDeleteRejectsOutsideProjectPath(t *testing.T) {
	setupRepoForEnvCommandTest(t)
	outside := filepath.Join(t.TempDir(), "schema.yaml")
	original := []byte("APP_KEY:\n  - required\n")
	if err := os.WriteFile(outside, original, 0o600); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "schema", "file", "delete", "--file", outside}, strings.NewReader(""), &output, &output)
	err := runner.Run()
	if err == nil || !strings.Contains(err.Error(), "must stay inside the project") {
		t.Fatalf("expected outside schema delete path to be rejected, got %v", err)
	}

	content, err := os.ReadFile(outside)
	if err != nil {
		t.Fatalf("schema delete should not remove outside file: %v", err)
	}
	if string(content) != string(original) {
		t.Fatalf("schema delete should not modify outside file, got %q", string(content))
	}
}

func TestRunSchemaFileSaveRejectsOutsideProjectPath(t *testing.T) {
	setupRepoForEnvCommandTest(t)
	outside := filepath.Join(t.TempDir(), "schema.yaml")
	content := base64.StdEncoding.EncodeToString([]byte("APP_KEY:\n  - required\n"))

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "schema", "file", "save", "--file", outside, "--content-base64", content}, strings.NewReader(""), &output, &output)
	err := runner.Run()
	if err == nil || !strings.Contains(err.Error(), "must stay inside the project") {
		t.Fatalf("expected outside schema save path to be rejected, got %v", err)
	}
	if _, err := os.Stat(outside); !os.IsNotExist(err) {
		t.Fatalf("schema save should not write outside project, stat err: %v", err)
	}
}

func TestRunSchemaRuleRejectsOutsideProjectPath(t *testing.T) {
	setupRepoForEnvCommandTest(t)
	outside := filepath.Join(t.TempDir(), "schema.yaml")

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "schema", "rule", "add", "--file", outside, "--key", "APP_KEY", "--rule", "required"}, strings.NewReader(""), &output, &output)
	err := runner.Run()
	if err == nil || !strings.Contains(err.Error(), "must stay inside the project") {
		t.Fatalf("expected outside schema rule path to be rejected, got %v", err)
	}
	if _, err := os.Stat(outside); !os.IsNotExist(err) {
		t.Fatalf("schema rule should not write outside project, stat err: %v", err)
	}
}

func TestRunSchemaKeyRejectsOutsideProjectPath(t *testing.T) {
	setupRepoForEnvCommandTest(t)
	outside := filepath.Join(t.TempDir(), "schema.yaml")
	original := []byte("APP_KEY:\n  - required\n")
	if err := os.WriteFile(outside, original, 0o600); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "schema", "key", "rename", "--file", outside, "--old-key", "APP_KEY", "--new-key", "APP_SECRET"}, strings.NewReader(""), &output, &output)
	err := runner.Run()
	if err == nil || !strings.Contains(err.Error(), "must stay inside the project") {
		t.Fatalf("expected outside schema key path to be rejected, got %v", err)
	}

	content, err := os.ReadFile(outside)
	if err != nil {
		t.Fatal(err)
	}
	if string(content) != string(original) {
		t.Fatalf("schema key should not modify outside file, got %q", string(content))
	}
}
