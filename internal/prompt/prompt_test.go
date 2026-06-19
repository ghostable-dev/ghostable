package prompt

import (
	"bytes"
	"strings"
	"testing"
)

func TestSelectFallsBackToNumberedPromptForNonTerminalIO(t *testing.T) {
	var output bytes.Buffer
	session := New(strings.NewReader("2\n"), &output)

	selected, err := session.Select("Choose command", []string{"setup", "status", "env"}, 0)
	if err != nil {
		t.Fatal(err)
	}
	if selected != "status" {
		t.Fatalf("expected status, got %q", selected)
	}
	if !strings.Contains(output.String(), "1. setup") {
		t.Fatalf("expected numbered fallback output, got:\n%s", output.String())
	}
	if !strings.Contains(output.String(), "\nUse arrow keys to move, Enter to select\n\nChoose command") {
		t.Fatalf("expected blank lines around select help, got:\n%s", output.String())
	}
}

func TestSelectOptionsWithIntroFallsBackToOptionValue(t *testing.T) {
	var output bytes.Buffer
	session := New(strings.NewReader("agents\n"), &output)

	selected, err := session.SelectOptionsWithIntro([]string{"Ghostable"}, "Available commands:", []SelectOption{
		{Label: "setup", Description: "Initialize project"},
		{Label: "agents", Value: "agent", Description: "Print agent guidance"},
	}, 0)
	if err != nil {
		t.Fatal(err)
	}
	if selected != "agent" {
		t.Fatalf("expected agent value, got %q", selected)
	}
	text := output.String()
	if !strings.Contains(text, "agents  Print agent guidance") {
		t.Fatalf("expected option description output, got:\n%s", text)
	}
}

func TestAskHighlightedFallsBackWithLeadingSpace(t *testing.T) {
	var output bytes.Buffer
	session := New(strings.NewReader("staging\n"), &output)

	value, err := session.AskHighlighted("Environment name", "")
	if err != nil {
		t.Fatal(err)
	}
	if value != "staging" {
		t.Fatalf("expected staging, got %q", value)
	}
	if !strings.HasPrefix(output.String(), "\nEnvironment name: ") {
		t.Fatalf("expected leading blank line before highlighted ask fallback, got:\n%s", output.String())
	}
}

func TestAskHighlightedTightFallsBackWithoutLeadingSpace(t *testing.T) {
	var output bytes.Buffer
	session := New(strings.NewReader("custom\n"), &output)

	value, err := session.AskHighlightedTight("Custom environment type", "custom")
	if err != nil {
		t.Fatal(err)
	}
	if value != "custom" {
		t.Fatalf("expected custom, got %q", value)
	}
	if strings.HasPrefix(output.String(), "\n") {
		t.Fatalf("did not expect leading blank line before tight ask fallback, got:\n%s", output.String())
	}
}

func TestTextPromptLineFormatsActiveHighlightedValue(t *testing.T) {
	line := textPromptLine("Environment name", "", greenOpen("staging"), false)
	expected := "Environment name: " + greenOpen("staging")
	if line != expected {
		t.Fatalf("expected %q, got %q", expected, line)
	}
}

func TestTextPromptLineFormatsAnsweredHighlightedValue(t *testing.T) {
	line := textPromptLine("Environment name", "", green("staging"), true)
	expected := yellow("Environment name: ") + green("staging")
	if line != expected {
		t.Fatalf("expected %q, got %q", expected, line)
	}
}

func TestVisibleChoiceCountShowsSmallMenusFully(t *testing.T) {
	if visible := visibleChoiceCount(7); visible != 7 {
		t.Fatalf("expected all 7 choices visible, got %d", visible)
	}
}

func TestVisibleChoiceCountCapsLongMenus(t *testing.T) {
	if visible := visibleChoiceCount(10); visible != 8 {
		t.Fatalf("expected long menus to cap at 8, got %d", visible)
	}
}
