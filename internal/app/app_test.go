package app

import (
	"bytes"
	"errors"
	"strings"
	"testing"

	"github.com/ghostable-dev/beta/internal/prompt"
	"github.com/manifoldco/promptui"
)

func TestRunInteractiveRootShowsHeadingBeforeStandardMenu(t *testing.T) {
	setupRepoForEnvCommandTest(t)

	input := strings.NewReader("2\n")
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable"}, input, &output, &output)
	runner.interactive = true
	runner.prompts = prompt.New(input, &output)

	if err := runner.Run(); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	heading := strings.Index(text, "Ghostable ")
	instructions := strings.Index(text, "Use arrow keys to move, Enter to select")
	menu := strings.Index(text, "Available commands")
	if heading < 0 || instructions < 0 || menu < 0 {
		t.Fatalf("expected heading, instructions, and menu label, got:\n%s", text)
	}
	if !(heading < instructions && instructions < menu) {
		t.Fatalf("expected heading, instructions, then menu label, got:\n%s", text)
	}
	if !strings.Contains(text, accent(version)) {
		t.Fatalf("expected accented version in heading, got:\n%s", text)
	}
	if !strings.Contains(text, "2. status") {
		t.Fatalf("expected standard numbered fallback menu, got:\n%s", text)
	}
	if !strings.Contains(text, "Show local project status") {
		t.Fatalf("expected command descriptions, got:\n%s", text)
	}
}

func TestPrintRunErrorShowsCleanCanceledMessageForPromptInterrupt(t *testing.T) {
	var output bytes.Buffer

	code := printRunError(promptui.ErrInterrupt, &output)

	if code != 130 {
		t.Fatalf("expected exit code 130, got %d", code)
	}
	text := output.String()
	if !strings.Contains(text, "Canceled.") {
		t.Fatalf("expected canceled message, got:\n%s", text)
	}
	if strings.Contains(text, "Error:") || strings.Contains(text, "^C") {
		t.Fatalf("did not expect generic error output for prompt cancellation:\n%s", text)
	}
}

func TestPrintRunErrorKeepsGenericErrors(t *testing.T) {
	var output bytes.Buffer

	code := printRunError(errors.New("boom"), &output)

	if code != 1 {
		t.Fatalf("expected exit code 1, got %d", code)
	}
	if !strings.Contains(output.String(), "Error:") || !strings.Contains(output.String(), "boom") {
		t.Fatalf("expected generic error output, got:\n%s", output.String())
	}
}
