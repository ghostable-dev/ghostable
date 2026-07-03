package app

import (
	"bytes"
	"regexp"
	"strings"
	"testing"
)

func TestRunDispatchesAdoptCommand(t *testing.T) {
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "adopt", "--yes"}, strings.NewReader(""), &output, &output)

	if err := runner.Run(); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	if !strings.Contains(text, "--- BEGIN GHOSTABLE ADOPTION PROMPT ---") {
		t.Fatalf("expected adopt command to print adoption prompt, got:\n%s", text)
	}
}

func TestRunAdoptYesPrintsDefaultPromptAndExcludesCISection(t *testing.T) {
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "adopt", "--yes"}, strings.NewReader(""), &output, &output)

	if err := runner.runAdopt(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	for _, expected := range []string{
		"--- BEGIN GHOSTABLE ADOPTION PROMPT ---",
		"Schema rule recommendations:",
		"Key annotation recommendations:",
		".env.example review:",
		"Hygiene recommendations:",
		"Missing or stale key findings:",
		"`ghostable validate --env <env> --json`",
		"`ghostable hygiene report --env <env> --unused --json`",
		"Return a concise adoption plan with:",
		"- findings by selected section",
		"- confidence level for each recommendation",
		"--- END GHOSTABLE ADOPTION PROMPT ---",
	} {
		if !strings.Contains(text, expected) {
			t.Fatalf("expected default adopt prompt to contain %q:\n%s", expected, text)
		}
	}
	for _, unexpected := range []string{
		"Optional CI recommendations:",
		"`ghostable review --secrets-only --json`",
		"GHOSTABLE_CI_TOKEN",
	} {
		if strings.Contains(text, unexpected) {
			t.Fatalf("did not expect default adopt prompt to contain %q:\n%s", unexpected, text)
		}
	}
}

func TestRunAdoptAllIncludesCISection(t *testing.T) {
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "adopt", "--all"}, strings.NewReader(""), &output, &output)

	if err := runner.runAdopt(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	for _, expected := range []string{
		"Optional CI recommendations:",
		"`ghostable review --secrets-only --json`",
		"GHOSTABLE_CI_TOKEN",
	} {
		if !strings.Contains(text, expected) {
			t.Fatalf("expected --all adopt prompt to contain %q:\n%s", expected, text)
		}
	}
}

func TestRunAdoptSectionsAlterPrompt(t *testing.T) {
	var output bytes.Buffer
	runner := NewRunner([]string{"ghostable", "adopt", "--sections", "schema,example"}, strings.NewReader(""), &output, &output)

	if err := runner.runAdopt(runner.args[2:]); err != nil {
		t.Fatal(err)
	}

	text := output.String()
	for _, expected := range []string{
		"Schema rule recommendations:",
		".env.example review:",
		"`ghostable validate --env <env> --json`",
	} {
		if !strings.Contains(text, expected) {
			t.Fatalf("expected selected adopt prompt to contain %q:\n%s", expected, text)
		}
	}
	for _, unexpected := range []string{
		"Key annotation recommendations:",
		"Hygiene recommendations:",
		"Missing or stale key findings:",
		"Optional CI recommendations:",
		"`ghostable hygiene report --env <env> --unused --json`",
	} {
		if strings.Contains(text, unexpected) {
			t.Fatalf("did not expect selected adopt prompt to contain %q:\n%s", unexpected, text)
		}
	}
}

func TestAdoptPromptKeepsSecurityGuardrails(t *testing.T) {
	text := renderAdoptPrompt(allAdoptSections())

	for _, expected := range []string{
		"Do not print, reveal, summarize, or copy secret values.",
		"Do not use `--show-values`.",
		"Do not write files or mutate Ghostable state until you present a plan and get approval.",
		"Never put secret\nvalues in annotations.",
		"blank values\nfor sensitive-looking variables.",
		"Do not add deploy, pull, write, decrypt, or secret-printing commands to CI unless the user explicitly asks.",
		"Ask for approval.",
		"Summarize what changed without exposing secrets.",
	} {
		if !strings.Contains(text, expected) {
			t.Fatalf("expected security guardrail %q in adopt prompt:\n%s", expected, text)
		}
	}

	allowedCommands := map[string]bool{
		"ghostable ... --json":                                 true,
		"ghostable status --json":                              true,
		"ghostable env list --json":                            true,
		"ghostable review --env-only --json":                   true,
		"ghostable example generate --dry-run --json":          true,
		"ghostable agent capabilities --json":                  true,
		"ghostable validate --env <env> --json":                true,
		"ghostable hygiene report --env <env> --unused --json": true,
		"ghostable review --secrets-only --json":               true,
	}
	commands := backtickedGhostableCommandsForTest(text)
	if len(commands) == 0 {
		t.Fatalf("expected adopt prompt to include backticked Ghostable commands:\n%s", text)
	}
	for _, command := range commands {
		if !allowedCommands[command] {
			t.Fatalf("unexpected or unsafe Ghostable command %q in adopt prompt:\n%s", command, text)
		}
		for _, forbidden := range []string{
			"--show-values",
			" deploy ",
			" pull ",
			" push ",
			" write ",
			" decrypt ",
			" delete ",
			" remove ",
			" set ",
			" init",
		} {
			if strings.Contains(" "+command+" ", forbidden) {
				t.Fatalf("unsafe Ghostable command fragment %q found in %q", forbidden, command)
			}
		}
		if strings.Contains(command, "example generate") && !strings.Contains(command, "--dry-run") {
			t.Fatalf("example generation command must stay dry-run only, got %q", command)
		}
	}
}

func backtickedGhostableCommandsForTest(text string) []string {
	matches := regexp.MustCompile("`([^`]+)`").FindAllStringSubmatch(text, -1)
	commands := []string{}
	for _, match := range matches {
		value := match[1]
		if strings.HasPrefix(value, "ghostable ") {
			commands = append(commands, value)
		}
	}
	return commands
}
