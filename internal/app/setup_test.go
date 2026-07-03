package app

import (
	"bytes"
	"encoding/json"
	"io"
	"os"
	"path/filepath"
	"strings"
	"testing"

	"github.com/ghostable-dev/beta/internal/domain"
	"github.com/ghostable-dev/beta/internal/manifest"
	"github.com/ghostable-dev/beta/internal/prompt"
	"github.com/ghostable-dev/beta/internal/store"
)

func TestRunSetupDefaultsEnvironmentWithoutPrompt(t *testing.T) {
	root := setupTempWorkdir(t)

	input := strings.NewReader("")
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "setup", "--name", "Test Project", "--device-name", "test-device", "--no-agent-instructions"}, input, &output, &output)
	runner.interactive = true
	runner.prompts = prompt.New(input, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}
	if strings.Contains(output.String(), "Initial environments") {
		t.Fatal("setup should not prompt for initial environments")
	}

	file, err := os.Open(filepath.Join(root, ".ghostable", "ghostable.yaml"))
	if err != nil {
		t.Fatal(err)
	}
	defer file.Close()

	project, err := manifest.Read(file)
	if err != nil {
		t.Fatal(err)
	}
	if _, ok := project.Environments["default"]; !ok {
		t.Fatalf("expected default environment, got %#v", project.Environments)
	}
	if _, ok := project.Environments["local"]; ok {
		t.Fatalf("did not expect local environment, got %#v", project.Environments)
	}
}

func TestRunSetupPrintsBannerAfterPrompts(t *testing.T) {
	setupTempWorkdir(t)

	input := strings.NewReader("Prompted Project\nPrompted Device\nn\n")
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "setup"}, input, &output, &output)
	runner.interactive = true
	runner.prompts = prompt.New(input, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	projectPrompt := strings.Index(text, "Project name")
	devicePrompt := strings.Index(text, "Device label")
	banner := strings.Index(text, "▗▄▄▖")
	initialized := strings.Index(text, "Initialized Ghostable")
	if projectPrompt < 0 || devicePrompt < 0 || banner < 0 || initialized < 0 {
		t.Fatalf("expected prompts, banner, and setup notes in output:\n%s", text)
	}
	if !(projectPrompt < banner && devicePrompt < banner && banner < initialized) {
		t.Fatalf("expected prompts before banner and notes after banner:\n%s", text)
	}
}

func TestNormalizeDefaultDeviceName(t *testing.T) {
	tests := map[string]string{
		" Joe's Mac Studio.local ": "Joe's Mac Studio",
		"build-runner.LOCAL":       "build-runner",
		"workstation":              "workstation",
		"\x00":                     "",
	}

	for input, expected := range tests {
		if actual := normalizeDefaultDeviceName(input); actual != expected {
			t.Fatalf("normalizeDefaultDeviceName(%q) = %q, want %q", input, actual, expected)
		}
	}
}

func TestRunSetupSeedsDefaultEnvironmentFromDotenvWhenConfirmed(t *testing.T) {
	root := setupTempWorkdir(t)
	if err := os.WriteFile(filepath.Join(root, ".env"), []byte("app-key=super-secret\napp name=Ghostable\n# disabled-key=off\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	input := strings.NewReader("y\n")
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "setup", "--name", "Test Project", "--device-name", "test-device", "--no-create-example", "--no-agent-instructions"}, input, &output, &output)
	runner.interactive = true
	runner.prompts = prompt.New(input, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	seedPrompt := strings.Index(text, "A .env file was detected")
	banner := strings.Index(text, "▗▄▄▖")
	if seedPrompt < 0 || banner < 0 || seedPrompt > banner {
		t.Fatalf("expected .env prompt before banner:\n%s", text)
	}
	if !strings.Contains(text, "Imported 3 variables from .env into default.") {
		t.Fatalf("expected import summary in output:\n%s", text)
	}
	if !strings.Contains(text, "Encrypting 3 variables from .env") {
		t.Fatalf("expected setup progress while encrypting seed values:\n%s", text)
	}

	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	values, err := repo.ReadVariables("default")
	if err != nil {
		t.Fatal(err)
	}
	if values["APP_KEY"].Value != "super-secret" {
		t.Fatalf("unexpected APP_KEY value: %#v", values["APP_KEY"])
	}
	if values["APP_NAME"].Value != "Ghostable" {
		t.Fatalf("unexpected APP_NAME value: %#v", values["APP_NAME"])
	}
	if values["DISABLED_KEY"].Value != "off" || !values["DISABLED_KEY"].Commented {
		t.Fatalf("expected commented seed value, got %#v", values["DISABLED_KEY"])
	}
	if _, exists := values["app-key"]; exists {
		t.Fatalf("did not expect raw app-key to be stored: %#v", values)
	}
}

func TestRunSetupPromptsToCreateExampleAfterDotenvImport(t *testing.T) {
	root := setupTempWorkdir(t)
	if err := os.WriteFile(filepath.Join(root, ".env"), []byte("APP_NAME=Ghostable\nAPP_DEBUG=false\nAPP_KEY=base64:secret\nOPENAI_API_KEY=sk-test\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	input := strings.NewReader("y\ny\n")
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "setup", "--name", "Test Project", "--device-name", "test-device", "--no-agent-instructions"}, input, &output, &output)
	runner.interactive = true
	runner.prompts = prompt.New(input, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	content, err := os.ReadFile(filepath.Join(root, ".env.example"))
	if err != nil {
		t.Fatal(err)
	}
	text := string(content)
	for _, expected := range []string{
		"APP_DEBUG=false",
		"APP_KEY=",
		"APP_NAME=Ghostable",
		"OPENAI_API_KEY=",
	} {
		if !strings.Contains(text, expected) {
			t.Fatalf("expected .env.example to contain %q:\n%s", expected, text)
		}
	}
	if strings.Contains(text, "base64:secret") || strings.Contains(text, "sk-test") {
		t.Fatalf("expected sensitive values to be blanked:\n%s", text)
	}

	outputText := output.String()
	for _, expected := range []string{
		"No .env.example file was found.",
		"Create .env.example from the imported keys? Sensitive-looking values will be blank.",
		"Created .env.example with 4 keys.",
		"Kept example values for 2 non-sensitive keys.",
		"Blanked 2 sensitive-looking values.",
		"Next: review with `ghostable env diff --env default --file .env`.",
	} {
		if !strings.Contains(outputText, expected) {
			t.Fatalf("expected setup output to contain %q:\n%s", expected, outputText)
		}
	}
}

func TestRunSetupSkipsExamplePromptWhenExampleAlreadyExists(t *testing.T) {
	root := setupTempWorkdir(t)
	if err := os.WriteFile(filepath.Join(root, ".env"), []byte("APP_NAME=Ghostable\n"), 0o600); err != nil {
		t.Fatal(err)
	}
	if err := os.WriteFile(filepath.Join(root, ".env.example"), []byte("EXISTING=value\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	input := strings.NewReader("y\n")
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "setup", "--name", "Test Project", "--device-name", "test-device", "--no-agent-instructions"}, input, &output, &output)
	runner.interactive = true
	runner.prompts = prompt.New(input, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	content, err := os.ReadFile(filepath.Join(root, ".env.example"))
	if err != nil {
		t.Fatal(err)
	}
	if string(content) != "EXISTING=value\n" {
		t.Fatalf("expected existing .env.example to stay unchanged, got:\n%s", string(content))
	}
	if strings.Contains(output.String(), "Create .env.example from the imported keys") {
		t.Fatalf("did not expect setup example prompt when .env.example exists:\n%s", output.String())
	}
}

func TestRunSetupCreateExampleFlagWorksNonInteractively(t *testing.T) {
	root := setupTempWorkdir(t)
	if err := os.WriteFile(filepath.Join(root, ".env"), []byte("APP_NAME=Ghostable\nAPP_KEY=base64:secret\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{
		"ghostable", "setup",
		"--name", "Test Project",
		"--device-name", "test-device",
		"--seed-dotenv",
		"--create-example",
	}, strings.NewReader(""), &output, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	content, err := os.ReadFile(filepath.Join(root, ".env.example"))
	if err != nil {
		t.Fatal(err)
	}
	text := string(content)
	if !strings.Contains(text, "APP_NAME=Ghostable") || !strings.Contains(text, "APP_KEY=") || strings.Contains(text, "base64:secret") {
		t.Fatalf("expected generated non-sensitive .env.example, got:\n%s", text)
	}
	if strings.Contains(output.String(), "Create .env.example from the imported keys") {
		t.Fatalf("did not expect prompt in noninteractive create-example mode:\n%s", output.String())
	}
}

func TestRunSetupCreateExampleFlagSupportsAllValuesMode(t *testing.T) {
	root := setupTempWorkdir(t)
	if err := os.WriteFile(filepath.Join(root, ".env"), []byte("APP_NAME=Ghostable\nAPP_KEY=base64:secret\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{
		"ghostable", "setup",
		"--name", "Test Project",
		"--device-name", "test-device",
		"--seed-dotenv",
		"--create-example",
		"--example-values", "all",
	}, strings.NewReader(""), &output, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	content, err := os.ReadFile(filepath.Join(root, ".env.example"))
	if err != nil {
		t.Fatal(err)
	}
	text := string(content)
	if !strings.Contains(text, "APP_NAME=Ghostable") || !strings.Contains(text, "APP_KEY=base64:secret") {
		t.Fatalf("expected all values to be kept in .env.example, got:\n%s", text)
	}
}

func TestRunSetupDoesNotCreateExampleNonInteractivelyByDefault(t *testing.T) {
	root := setupTempWorkdir(t)
	if err := os.WriteFile(filepath.Join(root, ".env"), []byte("APP_NAME=Ghostable\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{
		"ghostable", "setup",
		"--name", "Test Project",
		"--device-name", "test-device",
		"--seed-dotenv",
	}, strings.NewReader(""), &output, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	if _, err := os.Stat(filepath.Join(root, ".env.example")); !os.IsNotExist(err) {
		t.Fatalf("expected .env.example not to be created by default, err=%v", err)
	}
	if strings.Contains(output.String(), "No .env.example file was found.") {
		t.Fatalf("did not expect setup example messaging by default in noninteractive mode:\n%s", output.String())
	}
}

func TestRunSetupPromptsToAddAgentInstructionsAtEnd(t *testing.T) {
	root := setupTempWorkdir(t)

	input := strings.NewReader("y\n")
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "setup", "--name", "Test Project", "--device-name", "test-device"}, input, &output, &output)
	runner.interactive = true
	runner.prompts = prompt.New(input, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	content, err := os.ReadFile(filepath.Join(root, "AGENTS.md"))
	if err != nil {
		t.Fatal(err)
	}
	text := string(content)
	for _, expected := range []string{
		agentsBlockStart,
		"# Ghostable",
		"ghostable agent capabilities --json",
		agentsBlockEnd,
	} {
		if !strings.Contains(text, expected) {
			t.Fatalf("expected AGENTS.md to contain %q:\n%s", expected, text)
		}
	}

	outputText := output.String()
	promptIndex := strings.Index(outputText, "Add Ghostable guidance to AGENTS.md for AI coding assistants?")
	initializedIndex := strings.Index(outputText, "Initialized Ghostable")
	if promptIndex < 0 {
		t.Fatalf("expected AGENTS.md prompt, got:\n%s", outputText)
	}
	if initializedIndex < 0 || promptIndex < initializedIndex {
		t.Fatalf("expected AGENTS.md prompt after setup summary:\n%s", outputText)
	}
	if !strings.Contains(outputText, "Updated AGENTS.md.") {
		t.Fatalf("expected agent init output, got:\n%s", outputText)
	}
}

func TestRunSetupCanDeclineAgentInstructions(t *testing.T) {
	root := setupTempWorkdir(t)

	input := strings.NewReader("n\n")
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "setup", "--name", "Test Project", "--device-name", "test-device"}, input, &output, &output)
	runner.interactive = true
	runner.prompts = prompt.New(input, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	if _, err := os.Stat(filepath.Join(root, "AGENTS.md")); !os.IsNotExist(err) {
		t.Fatalf("expected AGENTS.md not to be created, err=%v", err)
	}
	outputText := output.String()
	for _, expected := range []string{
		"Skipped AGENTS.md.",
		"You can add it later with `ghostable agent init`.",
	} {
		if !strings.Contains(outputText, expected) {
			t.Fatalf("expected setup output to contain %q:\n%s", expected, outputText)
		}
	}
}

func TestRunSetupSkipsAgentPromptWhenManagedBlockExists(t *testing.T) {
	root := setupTempWorkdir(t)
	existing := "Team notes.\n\n" + agentsBlockStart + "\nold\n" + agentsBlockEnd + "\n"
	if err := os.WriteFile(filepath.Join(root, "AGENTS.md"), []byte(existing), 0o644); err != nil {
		t.Fatal(err)
	}

	input := strings.NewReader("")
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "setup", "--name", "Test Project", "--device-name", "test-device"}, input, &output, &output)
	runner.interactive = true
	runner.prompts = prompt.New(input, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	content, err := os.ReadFile(filepath.Join(root, "AGENTS.md"))
	if err != nil {
		t.Fatal(err)
	}
	if string(content) != existing {
		t.Fatalf("expected existing AGENTS.md managed block to stay unchanged, got:\n%s", string(content))
	}
	if strings.Contains(output.String(), "Add Ghostable guidance to AGENTS.md") {
		t.Fatalf("did not expect AGENTS.md prompt when managed block exists:\n%s", output.String())
	}
}

func TestRunSetupAgentInstructionsFlagWorksNonInteractively(t *testing.T) {
	root := setupTempWorkdir(t)

	var output bytes.Buffer
	runner := NewRunner([]string{
		"ghostable", "setup",
		"--name", "Test Project",
		"--device-name", "test-device",
		"--agent-instructions",
	}, strings.NewReader(""), &output, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	content, err := os.ReadFile(filepath.Join(root, "AGENTS.md"))
	if err != nil {
		t.Fatal(err)
	}
	if !strings.Contains(string(content), agentsBlockStart) || !strings.Contains(string(content), "# Ghostable") {
		t.Fatalf("expected AGENTS.md to contain Ghostable managed block, got:\n%s", string(content))
	}
	if strings.Contains(output.String(), "Add Ghostable guidance to AGENTS.md") {
		t.Fatalf("did not expect prompt in noninteractive agent-instructions mode:\n%s", output.String())
	}
}

func TestRunSetupDoesNotCreateAgentInstructionsNonInteractivelyByDefault(t *testing.T) {
	root := setupTempWorkdir(t)

	var output bytes.Buffer
	runner := NewRunner([]string{
		"ghostable", "setup",
		"--name", "Test Project",
		"--device-name", "test-device",
	}, strings.NewReader(""), &output, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	if _, err := os.Stat(filepath.Join(root, "AGENTS.md")); !os.IsNotExist(err) {
		t.Fatalf("expected AGENTS.md not to be created by default, err=%v", err)
	}
}

func TestRunSetupCanPrintAdoptPrompt(t *testing.T) {
	setupTempWorkdir(t)

	var output bytes.Buffer
	runner := NewRunner([]string{
		"ghostable", "setup",
		"--name", "Test Project",
		"--device-name", "test-device",
		"--no-agent-instructions",
		"--adopt-prompt",
	}, strings.NewReader(""), &output, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	for _, expected := range []string{
		"--- BEGIN GHOSTABLE ADOPTION PROMPT ---",
		renderAdoptPrompt(defaultAdoptSections()),
		"--- END GHOSTABLE ADOPTION PROMPT ---",
	} {
		if !strings.Contains(text, expected) {
			t.Fatalf("expected setup output to contain %q:\n%s", expected, text)
		}
	}
}

func TestRunSetupInteractiveAdoptPromptUsesSectionWizard(t *testing.T) {
	setupTempWorkdir(t)

	input := newSingleByteReader("y\ny\nn\nn\nn\nn\ny\n")
	var output bytes.Buffer
	runner := NewRunner([]string{
		"ghostable", "setup",
		"--name", "Test Project",
		"--device-name", "test-device",
		"--no-agent-instructions",
	}, input, &output, &output)
	runner.interactive = true
	runner.prompts = prompt.New(input, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	for _, expected := range []string{
		"Generate a Ghostable adoption prompt for your AI coding assistant now?",
		"Generate Ghostable adoption prompt",
		"[1/6] Schema rule recommendations",
		"Recommend validation rules such as required, nullable, boolean, url, integer, in, starts_with, and provider prefixes.",
		"[6/6] Optional CI recommendations",
		"Schema rule recommendations:",
		"Optional CI recommendations:",
	} {
		if !strings.Contains(text, expected) {
			t.Fatalf("expected interactive setup adopt prompt to contain %q:\n%s", expected, text)
		}
	}
	for _, unexpected := range []string{
		"Key annotation recommendations:",
		".env.example review:",
		"Hygiene recommendations:",
		"Missing or stale key findings:",
	} {
		if strings.Contains(text, unexpected) {
			t.Fatalf("did not expect declined section %q in generated prompt:\n%s", unexpected, text)
		}
	}
}

func TestRunSetupDoesNotPrintAdoptPromptNonInteractivelyByDefault(t *testing.T) {
	setupTempWorkdir(t)

	var output bytes.Buffer
	runner := NewRunner([]string{
		"ghostable", "setup",
		"--name", "Test Project",
		"--device-name", "test-device",
	}, strings.NewReader(""), &output, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	if strings.Contains(output.String(), "--- BEGIN GHOSTABLE ADOPTION PROMPT ---") {
		t.Fatalf("did not expect setup to print adopt prompt by default in noninteractive mode:\n%s", output.String())
	}
}

func TestRunSetupAgentInstructionsFlagWorksWithJSON(t *testing.T) {
	root := setupTempWorkdir(t)

	var output bytes.Buffer
	runner := NewRunner([]string{
		"ghostable", "setup",
		"--name", "Test Project",
		"--device-name", "test-device",
		"--agent-instructions",
		"--json",
	}, strings.NewReader(""), &output, &output)

	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	if _, err := os.Stat(filepath.Join(root, "AGENTS.md")); err != nil {
		t.Fatal(err)
	}
	var payload map[string]interface{}
	if err := json.Unmarshal(output.Bytes(), &payload); err != nil {
		t.Fatalf("parse setup JSON: %v\n%s", err, output.String())
	}
	if _, ok := payload["agentInstructions"].(map[string]interface{}); !ok {
		t.Fatalf("expected setup JSON to include agentInstructions result, got %#v", payload)
	}
	if strings.Contains(output.String(), "Updated AGENTS.md.") {
		t.Fatalf("did not expect agent init text in JSON output:\n%s", output.String())
	}
}

func TestRunSetupSeedsDefaultEnvironmentFromDotenvFlag(t *testing.T) {
	root := setupTempWorkdir(t)
	if err := os.WriteFile(filepath.Join(root, ".env"), []byte("APP_NAME=Ghostable\nAPP_KEY=super-secret\n"), 0o600); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{
		"ghostable", "setup",
		"--name", "Test Project",
		"--device-name", "test-device",
		"--seed-dotenv",
		"--json",
	}, strings.NewReader(""), &output, &output)
	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	repo, err := store.Open(root)
	if err != nil {
		t.Fatal(err)
	}
	values, err := repo.ReadVariables("default")
	if err != nil {
		t.Fatal(err)
	}
	if values["APP_NAME"].Value != "Ghostable" {
		t.Fatalf("expected APP_NAME to be seeded, got %#v", values)
	}
	if values["APP_KEY"].Value != "super-secret" {
		t.Fatalf("expected APP_KEY to be seeded, got %#v", values)
	}
	keyMetadata := readKeyMetadataForTest(t, root, "default")
	if keyMetadata["APP_NAME"].Position != 1000 || keyMetadata["APP_KEY"].Position != 2000 {
		t.Fatalf("expected setup seed positions to follow .env order, got %#v", keyMetadata)
	}
	if !strings.Contains(output.String(), `"seededFrom"`) {
		t.Fatalf("expected setup JSON to include seeded result, got:\n%s", output.String())
	}
	var payload map[string]interface{}
	if err := json.Unmarshal(output.Bytes(), &payload); err != nil {
		t.Fatalf("parse setup JSON: %v\n%s", err, output.String())
	}
	project := jsonObjectForTest(t, payload, "project")
	if _, exists := project["Schema"]; exists {
		t.Fatalf("setup JSON should not expose raw ProjectManifest fields: %#v", project)
	}
	if _, exists := project["ID"]; exists {
		t.Fatalf("setup JSON should not expose raw ProjectManifest fields: %#v", project)
	}
	if project["schema"] != domain.ProjectSchema {
		t.Fatalf("expected lower-camel project schema, got %#v", project)
	}
	if project["name"] != "Test Project" {
		t.Fatalf("expected lower-camel project name, got %#v", project)
	}
	if _, exists := project["packageManager"]; !exists {
		t.Fatalf("expected lower-camel packageManager field, got %#v", project)
	}
}

func TestRunStatusPrintsBannerAndInventory(t *testing.T) {
	setupTempWorkdir(t)
	repo, _, err := store.Setup(".", store.SetupOptions{
		Name:       "Status Project",
		DeviceName: "test-device",
	})
	if err != nil {
		t.Fatal(err)
	}
	if err := repo.SetVariable("default", "APP_KEY", "secret", "test"); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "status"}, strings.NewReader(""), &output, &output)
	if err := runner.runStatus(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	for _, expected := range []string{
		"▗▄▄▖",
		"Ghostable status",
		warn("Project      "),
		success("Status Project"),
		warn("Inventory"),
		success("1"),
		"Inventory",
		"Environments",
		"default",
		"Devices",
		"test-device",
	} {
		if !strings.Contains(text, expected) {
			t.Fatalf("expected status output to contain %q:\n%s", expected, text)
		}
	}
}

func TestRunSetupAcceptsNoMetadataCompatibilityFlag(t *testing.T) {
	setupTempWorkdir(t)

	var output bytes.Buffer
	runner := NewRunner([]string{
		"ghostable", "setup",
		"--name", "Compat Project",
		"--env", "development",
		"--device-name", "test-device",
		"--no-metadata",
		"--json",
	}, strings.NewReader(""), &output, &output)
	if err := runner.runSetup(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	if !strings.Contains(text, `"created": true`) {
		t.Fatalf("expected setup JSON payload:\n%s", text)
	}
}

func TestRunStatusJSONDoesNotPrintBanner(t *testing.T) {
	setupTempWorkdir(t)
	if _, _, err := store.Setup(".", store.SetupOptions{
		Name:       "Status Project",
		DeviceName: "test-device",
	}); err != nil {
		t.Fatal(err)
	}

	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "status", "--json"}, strings.NewReader(""), &output, &output)
	if err := runner.runStatus(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	if strings.Contains(text, "▗▄▄▖") {
		t.Fatalf("did not expect banner in JSON output:\n%s", text)
	}
	if !strings.Contains(text, `"project"`) {
		t.Fatalf("expected status JSON payload:\n%s", text)
	}
	var payload map[string]interface{}
	if err := json.Unmarshal(output.Bytes(), &payload); err != nil {
		t.Fatalf("parse status JSON: %v\n%s", err, text)
	}
	project := jsonObjectForTest(t, payload, "project")
	if _, exists := project["Schema"]; exists {
		t.Fatalf("status JSON should not expose raw ProjectManifest fields: %#v", project)
	}
	if project["schema"] != domain.ProjectSchema {
		t.Fatalf("expected lower-camel project schema, got %#v", project)
	}
	devices, ok := payload["devices"].([]interface{})
	if !ok || len(devices) != 1 {
		t.Fatalf("expected one status device, got %#v", payload["devices"])
	}
	device, ok := devices[0].(map[string]interface{})
	if !ok {
		t.Fatalf("expected status device object, got %#v", devices[0])
	}
	if _, exists := device["device_id"]; exists {
		t.Fatalf("status JSON should not expose device_id compatibility field: %#v", device)
	}
	if _, exists := device["client_sig"]; exists {
		t.Fatalf("status JSON should not expose client_sig signature field: %#v", device)
	}
	if device["schema"] != domain.DeviceSchema || device["id"] == "" {
		t.Fatalf("expected stable device JSON fields, got %#v", device)
	}
}

func jsonObjectForTest(t *testing.T, payload map[string]interface{}, key string) map[string]interface{} {
	t.Helper()
	object, ok := payload[key].(map[string]interface{})
	if !ok {
		t.Fatalf("expected %s object, got %#v", key, payload[key])
	}
	return object
}

func setupTempWorkdir(t *testing.T) string {
	t.Helper()
	root := t.TempDir()
	previousWD, err := os.Getwd()
	if err != nil {
		t.Fatal(err)
	}
	if err := os.Chdir(root); err != nil {
		t.Fatal(err)
	}
	t.Cleanup(func() {
		if err := os.Chdir(previousWD); err != nil {
			t.Fatalf("restore working directory: %v", err)
		}
	})
	t.Setenv("GHOSTABLE_KEYSTORE", filepath.Join(root, "keys"))
	return root
}

type singleByteReader struct {
	value  string
	offset int
}

func newSingleByteReader(value string) *singleByteReader {
	return &singleByteReader{value: value}
}

func (reader *singleByteReader) Read(p []byte) (int, error) {
	if reader.offset >= len(reader.value) {
		return 0, io.EOF
	}
	p[0] = reader.value[reader.offset]
	reader.offset++
	return 1, nil
}
