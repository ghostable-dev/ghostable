package app

import (
	"encoding/base64"
	"fmt"
	"os"
	"path/filepath"
	"strings"

	"github.com/ghostable-dev/beta/internal/cli"
	"github.com/ghostable-dev/beta/internal/validation"
)

var schemaCommandOptions = []commandOption{
	{Label: "rule", Description: "Add, remove, or update validation rules"},
	{Label: "key", Description: "Rename or remove schema keys"},
	{Label: "file", Description: "Save or delete schema files"},
}

var schemaFileCommandOptions = []commandOption{
	{Label: "save", Description: "Save schema content to disk"},
	{Label: "delete", Description: "Delete a schema file"},
}

var schemaRuleCommandOptions = []commandOption{
	{Label: "add", Description: "Add a rule to a key"},
	{Label: "remove", Description: "Remove a rule from a key"},
	{Label: "update", Description: "Replace a rule on a key"},
}

var schemaKeyCommandOptions = []commandOption{
	{Label: "remove", Description: "Delete a schema key"},
	{Label: "rename", Description: "Rename a schema key"},
}

func (r *Runner) runSchema(args []string) error {
	if len(args) == 0 {
		if !r.interactive {
			r.printSchemaHelp()
			return nil
		}
		selected, err := r.selectCommand("Select a schema command", schemaCommandOptions)
		if err != nil {
			return err
		}
		args = append(args, selected)
	}
	if isHelpArg(args[0]) {
		r.printSchemaHelp()
		return nil
	}

	switch args[0] {
	case "file":
		return r.runSchemaFile(args[1:])
	case "rule":
		return r.runSchemaRule(args[1:])
	case "key":
		return r.runSchemaKey(args[1:])
	default:
		return fmt.Errorf("unknown schema command %q", args[0])
	}
}

func (r *Runner) printSchemaHelp() {
	fmt.Fprintln(r.out, "Usage: ghostable schema <file|rule|key> <command> [options]")
	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, warn("Commands:"))
	printCommandDescriptions(r.out, schemaCommandOptions)
}

func (r *Runner) runSchemaFile(args []string) error {
	if len(args) == 0 {
		if !r.interactive {
			r.printSchemaFileHelp()
			return nil
		}
		selected, err := r.selectCommand("Select a schema file command", schemaFileCommandOptions)
		if err != nil {
			return err
		}
		args = append(args, selected)
	}
	if isHelpArg(args[0]) {
		r.printSchemaFileHelp()
		return nil
	}
	switch args[0] {
	case "save":
		return r.runSchemaFileSave(args[1:])
	case "delete":
		return r.runSchemaFileDelete(args[1:])
	default:
		return fmt.Errorf("unknown schema file command %q", args[0])
	}
}

func (r *Runner) runSchemaFileSave(args []string) error {
	fs := newFlagSet("schema file save", r.errOut)
	file := fs.String("file", "", "Schema file path")
	contentB64 := fs.String("content-base64", "", "UTF-8 schema content encoded as base64")
	jsonOut := fs.Bool("json", false, "Print mutation result as JSON")
	if _, err := cli.Parse(fs, args, cli.BoolFlags("json")); err != nil {
		return err
	}
	if *file == "" || *contentB64 == "" {
		return fmt.Errorf("--file and --content-base64 are required")
	}
	content, err := base64.StdEncoding.DecodeString(*contentB64)
	if err != nil {
		return err
	}
	if err := os.MkdirAll(filepath.Dir(*file), 0o755); err != nil {
		return err
	}
	if err := os.WriteFile(*file, content, 0o644); err != nil {
		return err
	}
	if *jsonOut {
		return printJSON(r.out, map[string]interface{}{"file": *file, "saved": true})
	}
	fmt.Fprintln(r.out, success(fmt.Sprintf("Saved %s.", *file)))
	return nil
}

func (r *Runner) runSchemaFileDelete(args []string) error {
	fs := newFlagSet("schema file delete", r.errOut)
	file := fs.String("file", "", "Schema file path")
	jsonOut := fs.Bool("json", false, "Print mutation result as JSON")
	if _, err := cli.Parse(fs, args, cli.BoolFlags("json")); err != nil {
		return err
	}
	selectedFile, err := r.selectSchemaFile(*file)
	if err != nil {
		return err
	}
	err = os.Remove(selectedFile)
	if err != nil && !os.IsNotExist(err) {
		return err
	}
	if *jsonOut {
		return printJSON(r.out, map[string]interface{}{"file": selectedFile, "deleted": true})
	}
	fmt.Fprintln(r.out, danger(fmt.Sprintf("Deleted %s.", selectedFile)))
	return nil
}

func (r *Runner) printSchemaFileHelp() {
	fmt.Fprintln(r.out, "Usage: ghostable schema file <command> [options]")
	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, warn("Commands:"))
	printCommandDescriptions(r.out, schemaFileCommandOptions)
}

func (r *Runner) runSchemaRule(args []string) error {
	if len(args) == 0 {
		if !r.interactive {
			r.printSchemaRuleHelp()
			return nil
		}
		selected, err := r.selectCommand("Select a schema rule command", schemaRuleCommandOptions)
		if err != nil {
			return err
		}
		args = append(args, selected)
	}
	if isHelpArg(args[0]) {
		r.printSchemaRuleHelp()
		return nil
	}
	fs := newFlagSet("schema rule "+args[0], r.errOut)
	file := fs.String("file", ".ghostable/schema.yaml", "Schema file path")
	key := fs.String("key", "", "Schema key")
	rule := fs.String("rule", "", "Validation rule")
	oldRule := fs.String("old-rule", "", "Existing validation rule")
	newRule := fs.String("new-rule", "", "Replacement validation rule")
	jsonOut := fs.Bool("json", false, "Print mutation result as JSON")
	if _, err := cli.Parse(fs, args[1:], cli.BoolFlags("json")); err != nil {
		return err
	}

	rules, err := loadSchemaRules(*file)
	if err != nil {
		return err
	}
	if err := r.applySchemaRuleCommand(args[0], rules, *key, *rule, *oldRule, *newRule); err != nil {
		return err
	}
	removeEmptySchemaRules(rules)
	if err := validation.WriteRules(*file, rules); err != nil {
		return err
	}
	if *jsonOut {
		return printJSON(r.out, map[string]interface{}{"file": *file, "saved": true})
	}
	fmt.Fprintln(r.out, success(fmt.Sprintf("Updated %s.", *file)))
	return nil
}

func (r *Runner) applySchemaRuleCommand(command string, rules map[string][]validation.Rule, key string, rule string, oldRule string, newRule string) error {
	switch command {
	case "add":
		selectedKey, err := r.selectSchemaKey(rules, key, true)
		if err != nil {
			return err
		}
		selectedRule, err := r.selectSchemaRule(rule)
		if err != nil {
			return err
		}
		rules[selectedKey] = append(rules[selectedKey], parseSchemaRule(selectedRule))
	case "remove":
		selectedKey, err := r.selectSchemaKey(rules, key, false)
		if err != nil {
			return err
		}
		selectedRule, err := r.selectExistingSchemaRule(rules, selectedKey, rule, "rule")
		if err != nil {
			return err
		}
		rules[selectedKey] = removeSchemaRule(rules[selectedKey], selectedRule)
	case "update":
		selectedKey, err := r.selectSchemaKey(rules, key, false)
		if err != nil {
			return err
		}
		selectedOldRule, err := r.selectExistingSchemaRule(rules, selectedKey, oldRule, "old-rule")
		if err != nil {
			return err
		}
		selectedNewRule, err := r.selectSchemaRule(newRule)
		if err != nil {
			return err
		}
		rules[selectedKey] = replaceSchemaRule(rules[selectedKey], selectedOldRule, selectedNewRule)
	default:
		return fmt.Errorf("unknown schema rule command %q", command)
	}
	return nil
}

func removeEmptySchemaRules(rules map[string][]validation.Rule) {
	for key := range rules {
		if len(rules[key]) == 0 {
			delete(rules, key)
		}
	}
}

func (r *Runner) printSchemaRuleHelp() {
	fmt.Fprintln(r.out, "Usage: ghostable schema rule <command> [options]")
	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, warn("Commands:"))
	printCommandDescriptions(r.out, schemaRuleCommandOptions)
}

func (r *Runner) runSchemaKey(args []string) error {
	if len(args) == 0 {
		if !r.interactive {
			r.printSchemaKeyHelp()
			return nil
		}
		selected, err := r.selectCommand("Select a schema key command", schemaKeyCommandOptions)
		if err != nil {
			return err
		}
		args = append(args, selected)
	}
	if isHelpArg(args[0]) {
		r.printSchemaKeyHelp()
		return nil
	}
	fs := newFlagSet("schema key "+args[0], r.errOut)
	file := fs.String("file", ".ghostable/schema.yaml", "Schema file path")
	key := fs.String("key", "", "Schema key")
	oldKey := fs.String("old-key", "", "Existing schema key")
	newKey := fs.String("new-key", "", "Replacement schema key")
	jsonOut := fs.Bool("json", false, "Print mutation result as JSON")
	if _, err := cli.Parse(fs, args[1:], cli.BoolFlags("json")); err != nil {
		return err
	}
	rules, err := loadSchemaRules(*file)
	if err != nil {
		return err
	}
	if err := r.applySchemaKeyCommand(args[0], rules, *key, *oldKey, *newKey); err != nil {
		return err
	}
	if err := validation.WriteRules(*file, rules); err != nil {
		return err
	}
	if *jsonOut {
		return printJSON(r.out, map[string]interface{}{"file": *file, "saved": true})
	}
	fmt.Fprintln(r.out, success(fmt.Sprintf("Updated %s.", *file)))
	return nil
}

func (r *Runner) applySchemaKeyCommand(command string, rules map[string][]validation.Rule, key string, oldKey string, newKey string) error {
	switch command {
	case "remove":
		selectedKey, err := r.selectSchemaKey(rules, key, false)
		if err != nil {
			return err
		}
		delete(rules, selectedKey)
	case "rename":
		selectedOldKey, err := r.selectSchemaKey(rules, oldKey, false)
		if err != nil {
			return err
		}
		selectedNewKey, err := r.ask("New schema key", newKey, "", "new-key")
		if err != nil {
			return err
		}
		rules[selectedNewKey] = rules[selectedOldKey]
		delete(rules, selectedOldKey)
	default:
		return fmt.Errorf("unknown schema key command %q", command)
	}
	return nil
}

func (r *Runner) printSchemaKeyHelp() {
	fmt.Fprintln(r.out, "Usage: ghostable schema key <command> [options]")
	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, warn("Commands:"))
	printCommandDescriptions(r.out, schemaKeyCommandOptions)
}

func (r *Runner) selectSchemaFile(provided string) (string, error) {
	if strings.TrimSpace(provided) != "" {
		return strings.TrimSpace(provided), nil
	}
	if !r.interactive {
		return "", fmt.Errorf("pass --file")
	}

	files := listSchemaFiles()
	if len(files) == 0 {
		return "", fmt.Errorf("no schema files were found")
	}
	return r.prompts.Select("Select a schema file", files, 0)
}

func listSchemaFiles() []string {
	files := []string{}
	if _, err := os.Stat(".ghostable/schema.yaml"); err == nil {
		files = append(files, ".ghostable/schema.yaml")
	}
	if _, err := os.Stat(".ghostable/schema.yml"); err == nil {
		files = append(files, ".ghostable/schema.yml")
	}
	entries, err := os.ReadDir(filepath.Join(".ghostable", "schemas"))
	if err == nil {
		for _, entry := range entries {
			if entry.IsDir() {
				continue
			}
			if strings.HasSuffix(entry.Name(), ".yaml") || strings.HasSuffix(entry.Name(), ".yml") {
				files = append(files, filepath.Join(".ghostable", "schemas", entry.Name()))
			}
		}
	}
	sortStrings(files)
	return files
}

func (r *Runner) selectSchemaKey(rules map[string][]validation.Rule, provided string, allowNew bool) (string, error) {
	if strings.TrimSpace(provided) != "" {
		return strings.TrimSpace(provided), nil
	}
	if !r.interactive {
		return "", fmt.Errorf("pass --key")
	}

	keys := make([]string, 0, len(rules))
	for key := range rules {
		keys = append(keys, key)
	}
	sortStrings(keys)
	if len(keys) == 0 {
		if allowNew {
			return r.ask("Schema key", "", "", "key")
		}
		return "", fmt.Errorf("no schema keys were found")
	}
	if !allowNew {
		return r.prompts.Select("Select a schema key", keys, 0)
	}

	choices := append(keys, "New schema key")
	selected, err := r.prompts.Select("Select a schema key", choices, 0)
	if err != nil {
		return "", err
	}
	if selected == "New schema key" {
		return r.ask("Schema key", "", "", "key")
	}
	return selected, nil
}

func (r *Runner) selectExistingSchemaRule(rules map[string][]validation.Rule, key string, provided string, missingFlag string) (string, error) {
	if strings.TrimSpace(provided) != "" {
		return strings.TrimSpace(provided), nil
	}
	if !r.interactive {
		return "", fmt.Errorf("pass --%s", missingFlag)
	}

	keyRules := rules[key]
	if len(keyRules) == 0 {
		return "", fmt.Errorf("no schema rules were found for %s", key)
	}
	choices := make([]string, 0, len(keyRules))
	for _, rule := range keyRules {
		choices = append(choices, formatSchemaRule(rule))
	}
	return r.prompts.Select("Select a schema rule", choices, 0)
}

func (r *Runner) selectSchemaRule(provided string) (string, error) {
	if strings.TrimSpace(provided) != "" {
		return strings.TrimSpace(provided), nil
	}
	if !r.interactive {
		return "", fmt.Errorf("pass --rule")
	}

	choices := []string{"required", "string", "integer", "numeric", "boolean", "email", "url", "nullable", "starts_with", "ends_with", "regex", "in", "min", "max", "different_from", "custom"}
	selected, err := r.prompts.Select("Select a validation rule", choices, 0)
	if err != nil {
		return "", err
	}
	if selected == "custom" {
		return r.ask("Validation rule", "", "", "rule")
	}
	if schemaRuleNeedsArgument(selected) {
		argument, err := r.ask("Rule argument", "", "", "rule")
		if err != nil {
			return "", err
		}
		return selected + ":" + argument, nil
	}
	return selected, nil
}

func schemaRuleNeedsArgument(rule string) bool {
	switch rule {
	case "starts_with", "ends_with", "regex", "in", "min", "max", "different_from":
		return true
	default:
		return false
	}
}

func loadSchemaRules(file string) (map[string][]validation.Rule, error) {
	if _, err := os.Stat(file); os.IsNotExist(err) {
		return map[string][]validation.Rule{}, nil
	}
	return validation.ParseFile(file)
}

func parseSchemaRule(value string) validation.Rule {
	name, arg, ok := strings.Cut(value, ":")
	if !ok {
		return validation.Rule{Name: strings.TrimSpace(value)}
	}
	return validation.Rule{Name: strings.TrimSpace(name), Argument: strings.TrimSpace(arg)}
}

func removeSchemaRule(rules []validation.Rule, value string) []validation.Rule {
	next := make([]validation.Rule, 0, len(rules))
	for _, rule := range rules {
		if formatSchemaRule(rule) != value {
			next = append(next, rule)
		}
	}
	return next
}

func replaceSchemaRule(rules []validation.Rule, oldValue string, newValue string) []validation.Rule {
	for index, rule := range rules {
		if formatSchemaRule(rule) == oldValue {
			rules[index] = parseSchemaRule(newValue)
		}
	}
	return rules
}

func formatSchemaRule(rule validation.Rule) string {
	if rule.Argument == "" {
		return rule.Name
	}
	return rule.Name + ":" + rule.Argument
}
