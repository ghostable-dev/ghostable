package app

import (
	"fmt"
	"io"
	"strings"

	"github.com/ghostable-dev/beta/internal/cli"
)

type adoptSection struct {
	ID              string
	Title           string
	Description     string
	ConfirmLabel    string
	DefaultIncluded bool
	Body            string
}

var adoptSections = []adoptSection{
	{
		ID:              "schema",
		Title:           "Schema rule recommendations",
		Description:     "Recommend validation rules such as required, nullable, boolean, url, integer, in, starts_with, and provider prefixes.",
		ConfirmLabel:    "Include schema rule recommendations?",
		DefaultIncluded: true,
		Body: strings.Join([]string{
			"Schema rule recommendations:",
			"Recommend validation rules with confidence levels. Use Ghostable's supported rule vocabulary:",
			"`required`, `nullable`, `string`, `integer`, `numeric`, `boolean`, `email`, `url`,",
			"`starts_with`, `ends_with`, `regex`, `in`, `min`, `max`, `different_from`.",
		}, "\n"),
	},
	{
		ID:              "annotations",
		Title:           "Key annotation recommendations",
		Description:     "Recommend only evidence-backed non-secret metadata; move unknown ownership, deploy, visibility, and rotation questions to open questions.",
		ConfirmLabel:    "Include key annotation recommendations?",
		DefaultIncluded: true,
		Body: strings.Join([]string{
			"Key annotation recommendations:",
			"Recommend only non-secret annotations supported by evidence from Ghostable metadata,",
			"repo files, deployment config, CI config, framework config, or user-provided context.",
			"Do not invent owner/team, provider, deploy-managed status, agent visibility, or rotation policy.",
			"Move unknown annotation fields into open questions instead of proposed writes. Never put secret",
			"values in annotations.",
		}, "\n"),
	},
	{
		ID:              "example",
		Title:           ".env.example review",
		Description:     "Check that example keys are complete and sensitive-looking values are blank.",
		ConfirmLabel:    "Include .env.example review?",
		DefaultIncluded: true,
		Body: strings.Join([]string{
			".env.example review:",
			"Check whether `.env.example` has the right keys, safe example values, and blank values",
			"for sensitive-looking variables.",
		}, "\n"),
	},
	{
		ID:              "hygiene",
		Title:           "Hygiene recommendations",
		Description:     "Recommend stale-variable follow-up, heuristic unused-variable review, and production readiness checks.",
		ConfirmLabel:    "Include hygiene recommendations?",
		DefaultIncluded: true,
		Body: strings.Join([]string{
			"Hygiene recommendations:",
			"Recommend stale-variable follow-up and any keys that should be reviewed before production use.",
			"Treat `ghostable hygiene report --env <env> --unused --json` findings as heuristic:",
			"stored-but-unreferenced means not referenced by scanned app-owned code/schema/example,",
			"not necessarily safe to delete. For framework-conventional keys such as Laravel defaults,",
			"recommend review or documentation rather than deletion unless stronger evidence proves the key is unused.",
			"Do not create suppressions unless there is a clear reason.",
		}, "\n"),
	},
	{
		ID:              "drift",
		Title:           "Missing or stale key findings",
		Description:     "Ask the assistant to compare Ghostable state, schema, .env.example, and code references.",
		ConfirmLabel:    "Include missing/stale key findings?",
		DefaultIncluded: true,
		Body: strings.Join([]string{
			"Missing or stale key findings:",
			"List keys referenced in code but missing from Ghostable/schema/example, and keys stored",
			"in Ghostable that are not referenced by scanned app-owned code/schema/example or are undocumented.",
			"Do not treat stored-but-unreferenced keys as safe to delete without stronger project evidence.",
		}, "\n"),
	},
	{
		ID:              "ci",
		Title:           "Optional CI recommendations",
		Description:     "Recommend read-only Ghostable checks for CI workflows. No deploy/write/decrypt commands.",
		ConfirmLabel:    "Include optional CI recommendations?",
		DefaultIncluded: false,
		Body: strings.Join([]string{
			"Optional CI recommendations:",
			"If the repository has CI workflow files, recommend a minimal read-only Ghostable validation",
			"step. Do not edit CI files unless the user explicitly approves CI changes. Prefer:",
			"- `ghostable validate --env <env> --json`",
			"- `ghostable review --env-only --json`",
			"- `ghostable review --secrets-only --json`",
			"- `ghostable example generate --dry-run --json`",
			"Do not add deploy, pull, write, decrypt, or secret-printing commands to CI unless the user explicitly asks.",
			"Call out whether `GHOSTABLE_CI_TOKEN` or another scoped credential would be required.",
		}, "\n"),
	},
}

func (r *Runner) runAdopt(args []string) error {
	fs := newFlagSet("adopt", r.errOut)
	yes := fs.Bool("yes", false, "Use default adoption prompt sections without prompting")
	all := fs.Bool("all", false, "Include every adoption prompt section")
	ciSection := fs.Bool("ci", false, "Include optional CI recommendations")
	sectionsInput := fs.String("sections", "", "Comma-separated sections: schema,annotations,example,hygiene,drift,ci")
	if _, err := cli.Parse(fs, args, cli.BoolFlags("yes", "all", "ci")); err != nil {
		return err
	}

	selection, err := r.resolveAdoptSectionSelection(adoptSelectionOptions{
		Yes:      *yes,
		All:      *all,
		CI:       *ciSection,
		Sections: *sectionsInput,
	})
	if err != nil {
		return err
	}
	return printAdoptPrompt(r.out, selection)
}

type adoptSelectionOptions struct {
	Yes      bool
	All      bool
	CI       bool
	Sections string
}

func (r *Runner) resolveAdoptSectionSelection(options adoptSelectionOptions) (map[string]bool, error) {
	switch {
	case options.All:
		return allAdoptSections(), nil
	case strings.TrimSpace(options.Sections) != "":
		selection, err := parseAdoptSections(options.Sections)
		if err != nil {
			return nil, err
		}
		if options.CI {
			selection["ci"] = true
		}
		return selection, nil
	case options.Yes || options.CI:
		selection := defaultAdoptSections()
		if options.CI {
			selection["ci"] = true
		}
		return selection, nil
	case !r.interactive:
		return nil, fmt.Errorf("pass --yes, --all, or --sections to generate an adoption prompt non-interactively")
	default:
		return r.promptAdoptSections()
	}
}

func (r *Runner) promptAdoptSections() (map[string]bool, error) {
	fmt.Fprintln(r.out, "Generate Ghostable adoption prompt")
	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, "Choose the sections to include. Press Enter to accept the default.")

	selection := map[string]bool{}
	for index, section := range adoptSections {
		fmt.Fprintln(r.out)
		fmt.Fprintf(r.out, "[%d/%d] %s\n", index+1, len(adoptSections), section.Title)
		fmt.Fprintln(r.out, section.Description)
		include, err := r.prompts.Confirm(section.ConfirmLabel, section.DefaultIncluded)
		if err != nil {
			return nil, err
		}
		r.printPromptAnswer(section.ConfirmLabel, yesNo(include))
		selection[section.ID] = include
	}
	return selection, nil
}

func defaultAdoptSections() map[string]bool {
	selection := map[string]bool{}
	for _, section := range adoptSections {
		selection[section.ID] = section.DefaultIncluded
	}
	return selection
}

func allAdoptSections() map[string]bool {
	selection := map[string]bool{}
	for _, section := range adoptSections {
		selection[section.ID] = true
	}
	return selection
}

func parseAdoptSections(input string) (map[string]bool, error) {
	selection := map[string]bool{}
	valid := adoptSectionLookup()
	for _, part := range strings.Split(input, ",") {
		id := strings.ToLower(strings.TrimSpace(part))
		if id == "" {
			continue
		}
		if id == "annotation" {
			id = "annotations"
		}
		if id == "all" {
			for _, section := range adoptSections {
				selection[section.ID] = true
			}
			continue
		}
		if _, ok := valid[id]; !ok {
			return nil, fmt.Errorf("unknown adoption prompt section %q; use schema, annotations, example, hygiene, drift, or ci", id)
		}
		selection[id] = true
	}
	if len(selection) == 0 {
		return nil, fmt.Errorf("pass at least one section")
	}
	return selection, nil
}

func adoptSectionLookup() map[string]adoptSection {
	lookup := make(map[string]adoptSection, len(adoptSections))
	for _, section := range adoptSections {
		lookup[section.ID] = section
	}
	return lookup
}

func printAdoptPrompt(out io.Writer, selection map[string]bool) error {
	fmt.Fprintln(out, "--- BEGIN GHOSTABLE ADOPTION PROMPT ---")
	fmt.Fprint(out, renderAdoptPrompt(selection))
	fmt.Fprintln(out, "--- END GHOSTABLE ADOPTION PROMPT ---")
	return nil
}

func renderAdoptPrompt(selection map[string]bool) string {
	var builder strings.Builder
	builder.WriteString(strings.Join([]string{
		"You are helping finish Ghostable adoption in this repository.",
		"",
		"Primary goal:",
		"Turn the project's environment variables into a maintainable Ghostable env contract:",
		"validation rules, key annotations, hygiene recommendations, and safe agent guidance.",
		"",
		"Hard rules:",
		"- Do not print, reveal, summarize, or copy secret values.",
		"- Do not use `--show-values`.",
		"- Do not write files or mutate Ghostable state until you present a plan and get approval.",
		"- Prefer `ghostable ... --json` commands for inspection.",
		"- Use Ghostable as the source of truth for stored environment state.",
		"- Treat `.env` and `.env.*` files as local-only unless the repository explicitly documents otherwise.",
		"",
		"Start with these commands:",
		"- `ghostable status --json`",
		"- `ghostable env list --json`",
		"- `ghostable review --env-only --json`",
		"- `ghostable example generate --dry-run --json`",
		"- `ghostable agent capabilities --json`",
		"",
		"If `ghostable review --env-only --json` fails because the target directory is not a git repository",
		"or Ghostable cannot infer a base ref, report that limitation and continue with other read-only checks.",
	}, "\n"))
	builder.WriteString("\n\n")

	additionalCommands := []string{}
	if selection["schema"] {
		additionalCommands = append(additionalCommands, "`ghostable validate --env <env> --json`")
	}
	if selection["hygiene"] {
		additionalCommands = append(additionalCommands, "`ghostable hygiene report --env <env> --unused --json`")
	}
	if len(additionalCommands) > 0 {
		builder.WriteString("Run these additional commands when relevant:\n")
		for _, command := range additionalCommands {
			builder.WriteString("- ")
			builder.WriteString(command)
			builder.WriteString("\n")
		}
		builder.WriteString("\n")
	}

	builder.WriteString(strings.Join([]string{
		"Inspect the repository directly for:",
		"- environment variable references in code",
		"- framework conventions",
		"- package scripts",
		"- deployment files",
		"- CI workflow files",
		"- existing `.env.example`",
		"- existing `AGENTS.md`",
		"- existing `.ghostable/schema.yaml` or `.ghostable/schemas/*.yaml`",
		"- existing `.ghostable/hygiene.yaml`",
	}, "\n"))
	builder.WriteString("\n\n")

	for _, section := range adoptSections {
		if !selection[section.ID] {
			continue
		}
		builder.WriteString(section.Body)
		builder.WriteString("\n\n")
	}

	builder.WriteString(strings.Join([]string{
		"Return a concise adoption plan with:",
		"- commands run",
		"- findings by selected section",
		"- recommended changes",
		"- confidence level for each recommendation",
		"- files or Ghostable records that would change",
		"- open questions",
		"",
		"Before making changes:",
		"- Ask for approval.",
		"- Explain which files or Ghostable records will change.",
		"",
		"After approved changes:",
		"- Run `ghostable validate --env <env> --json`.",
		"- Run `ghostable review --env-only --json`.",
		"- Run `ghostable example generate --dry-run --json`.",
		"- Summarize what changed without exposing secrets.",
	}, "\n"))
	builder.WriteString("\n")
	return builder.String()
}
