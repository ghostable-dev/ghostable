package app

import (
	"context"
	"fmt"
	"os"
	"os/exec"
	"path/filepath"
	"runtime"
	"strings"
	"time"

	"github.com/ghostable-dev/ghostable/v3/internal/cli"
	"github.com/ghostable-dev/ghostable/v3/internal/domain"
	"github.com/ghostable-dev/ghostable/v3/internal/store"
)

func (r *Runner) runSetup(args []string) error {
	fs := newFlagSet("setup", r.errOut)
	var envNames cli.Strings
	name := fs.String("name", "", "Project name")
	fs.Var(&envNames, "env", "Initial environment name; may be repeated or comma-separated")
	deviceName := fs.String("device-name", "", "Device label")
	platform := fs.String("platform", "", "Device platform label")
	language := fs.String("language", "", "Project language hint")
	framework := fs.String("framework", "", "Project framework hint")
	packageManager := fs.String("package-manager", "", "Project package manager hint")
	deployTarget := fs.String("deploy-target", "", "Project deployment target hint")
	activityMode := fs.String("activity-mode", domain.DefaultActivity, "Signed activity mode: off, minimal, or full")
	force := fs.Bool("force", false, "Overwrite an existing Ghostable manifest")
	seedDotenv := fs.Bool("seed-dotenv", false, "Import values from .env into the default environment")
	noSeedDotenv := fs.Bool("no-seed-dotenv", false, "Do not import values from .env during setup")
	createExample := fs.Bool("create-example", false, "Create .env.example after importing .env")
	noCreateExample := fs.Bool("no-create-example", false, "Do not create .env.example during setup")
	exampleValues := fs.String("example-values", exampleValuesNonSensitive, "Example value mode: blank, non-sensitive, or all")
	agentInstructions := fs.Bool("agent-instructions", false, "Add Ghostable guidance to AGENTS.md")
	noAgentInstructions := fs.Bool("no-agent-instructions", false, "Do not add Ghostable guidance to AGENTS.md")
	adoptPrompt := fs.Bool("adopt-prompt", false, "Print a Ghostable adoption prompt after setup")
	noAdoptPrompt := fs.Bool("no-adopt-prompt", false, "Do not print a Ghostable adoption prompt after setup")
	fs.Bool("no-metadata", false, "Deprecated; metadata prompts are not used by this client")
	jsonOut := fs.Bool("json", false, "Print setup result as JSON")
	if _, err := cli.Parse(fs, args, cli.BoolFlags("force", "seed-dotenv", "no-seed-dotenv", "create-example", "no-create-example", "agent-instructions", "no-agent-instructions", "adopt-prompt", "no-adopt-prompt", "no-metadata", "json")); err != nil {
		return err
	}
	if *seedDotenv && *noSeedDotenv {
		return fmt.Errorf("pass --seed-dotenv or --no-seed-dotenv, not both")
	}
	if *createExample && *noCreateExample {
		return fmt.Errorf("pass --create-example or --no-create-example, not both")
	}
	if *agentInstructions && *noAgentInstructions {
		return fmt.Errorf("pass --agent-instructions or --no-agent-instructions, not both")
	}
	if *adoptPrompt && *noAdoptPrompt {
		return fmt.Errorf("pass --adopt-prompt or --no-adopt-prompt, not both")
	}
	if *jsonOut && *adoptPrompt {
		return fmt.Errorf("pass --adopt-prompt without --json; adoption prompts are plain text output")
	}
	selectedExampleValueMode, err := normalizeExampleValueMode(*exampleValues)
	if err != nil {
		return err
	}

	if !*force {
		manifestPath := filepath.Join(mustGetwd(), ".ghostable", "ghostable.yaml")
		if _, err := os.Stat(manifestPath); err == nil {
			return fmt.Errorf("Ghostable is already initialized; pass --force to replace the local manifest")
		} else if !os.IsNotExist(err) {
			return err
		}
	}

	defaultName := filepath.Base(mustGetwd())
	projectName, err := r.ask("Project name", *name, defaultName, "name")
	if err != nil {
		return err
	}

	if len(envNames) == 0 {
		envNames = append(envNames, domain.DefaultEnvName)
	}

	device, err := r.ask("Device label", *deviceName, defaultDeviceName(), "device-name")
	if err != nil {
		return err
	}

	envs := make([]domain.Environment, 0, len(envNames))
	for _, name := range envNames {
		name = strings.TrimSpace(name)
		if name == "" {
			continue
		}
		envs = append(envs, domain.Environment{Name: name, Type: environmentType(name)})
	}

	dotenvSeed, err := r.resolveDefaultDotenvSeed(envs, *jsonOut, *seedDotenv, *noSeedDotenv)
	if err != nil {
		return err
	}

	r.printProgress(!*jsonOut, "Creating project identity and environment keys")
	repo, created, err := store.Setup(".", store.SetupOptions{
		Name:           projectName,
		Environments:   envs,
		DeviceName:     device,
		Platform:       *platform,
		Language:       *language,
		Framework:      *framework,
		PackageManager: *packageManager,
		DeployTarget:   *deployTarget,
		ActivityMode:   *activityMode,
		Force:          *force,
	})
	if err != nil {
		return err
	}

	var seedResult *store.PushResult
	if dotenvSeed != nil && len(dotenvSeed.values) > 0 {
		r.printProgress(!*jsonOut, fmt.Sprintf("Encrypting %d variable%s from %s", len(dotenvSeed.values), plural(len(dotenvSeed.values)), dotenvSeed.file))
		result, err := repo.PutVariablesWithMetadataOrdered(domain.DefaultEnvName, dotenvSeed.values, dotenvSeed.keys, store.PutOptions{Reason: "Seeded from .env during setup"})
		if err != nil {
			return err
		}
		result.File = dotenvSeed.file
		seedResult = &result
	}

	setupExampleOptions := setupExampleOptions{
		Create:    *createExample,
		NoCreate:  *noCreateExample,
		ValueMode: selectedExampleValueMode,
		JSON:      *jsonOut,
	}
	considerSetupExample, err := r.shouldConsiderSetupExample(repo, dotenvSeed, seedResult, setupExampleOptions)
	if err != nil {
		return err
	}
	setupAgentOptions := setupAgentInstructionsOptions{
		Add:   *agentInstructions,
		NoAdd: *noAgentInstructions,
		JSON:  *jsonOut,
	}
	setupAdoptOptions := setupAdoptPromptOptions{
		Print:   *adoptPrompt,
		NoPrint: *noAdoptPrompt,
		JSON:    *jsonOut,
	}
	considerSetupAgentInstructions := r.shouldConsiderSetupAgentInstructions(setupAgentOptions)
	var setupExampleResult *setupExampleCreationResult
	var setupAgentInstructionsResult *setupAgentInstructionsResult
	if *jsonOut {
		if considerSetupExample && *createExample {
			result, err := createSetupExample(repo, dotenvSeed, setupExampleOptions)
			if err != nil {
				return err
			}
			setupExampleResult = &result
		}
		if considerSetupAgentInstructions && *agentInstructions {
			result, err := writeSetupAgentInstructions()
			if err != nil {
				return err
			}
			setupAgentInstructionsResult = &result
		}
		return printJSON(r.out, setupResultPayload(repo, created, seedResult, setupExampleResult, setupAgentInstructionsResult))
	}

	r.printSetupResult(repo, dotenvSeed, seedResult, considerSetupExample)
	if considerSetupExample {
		if _, err := r.finishSetupExample(repo, dotenvSeed, setupExampleOptions); err != nil {
			return err
		}
	}
	if considerSetupAgentInstructions {
		if _, err := r.finishSetupAgentInstructions(setupAgentOptions); err != nil {
			return err
		}
	}
	if err := r.finishSetupAdoptPrompt(setupAdoptOptions); err != nil {
		return err
	}
	return nil
}

func setupResultPayload(repo store.Repository, created bool, seedResult *store.PushResult, setupExampleResult *setupExampleCreationResult, setupAgentInstructionsResult *setupAgentInstructionsResult) map[string]interface{} {
	payload := map[string]interface{}{
		"project":      jsonProjectManifestFromDomain(repo.Manifest),
		"manifestPath": repo.ManifestPath,
		"keyPath":      repo.KeyPath(),
		"deviceId":     repo.DeviceID(),
		"created":      created,
	}
	if seedResult != nil {
		payload["seededFrom"] = seedResult
	}
	if setupExampleResult != nil {
		payload["example"] = setupExampleResult
	}
	if setupAgentInstructionsResult != nil {
		payload["agentInstructions"] = setupAgentInstructionsResult
	}
	return payload
}

type jsonProjectManifest struct {
	Schema         string                        `json:"schema"`
	ID             string                        `json:"id"`
	Name           string                        `json:"name"`
	Language       string                        `json:"language"`
	Framework      string                        `json:"framework"`
	PackageManager string                        `json:"packageManager"`
	DeployTarget   string                        `json:"deployTarget"`
	ActivityMode   string                        `json:"activityMode"`
	AuditEnvs      []string                      `json:"auditEnvs"`
	Environments   map[string]domain.Environment `json:"environments"`
	ScanLevel      string                        `json:"scanLevel"`
	ScanIgnores    []string                      `json:"scanIgnores"`
}

type jsonDeviceRecord struct {
	Schema        string                 `json:"schema"`
	ID            string                 `json:"id"`
	Name          string                 `json:"name,omitempty"`
	Platform      string                 `json:"platform,omitempty"`
	Status        string                 `json:"status"`
	CreatedAt     string                 `json:"createdAt"`
	SigningKey    domain.PublicKeyRecord `json:"signingKey"`
	EncryptionKey domain.PublicKeyRecord `json:"encryptionKey"`
}

type jsonAccessRequest struct {
	Schema      string `json:"schema"`
	ProjectID   string `json:"projectId"`
	ID          string `json:"id"`
	DeviceID    string `json:"deviceId"`
	Environment string `json:"environment"`
	Role        string `json:"role"`
	Reason      string `json:"reason,omitempty"`
	CreatedAt   string `json:"createdAt"`
}

type jsonAccessRequestEntry struct {
	Request     jsonAccessRequest `json:"request"`
	Device      jsonDeviceRecord  `json:"device"`
	AccessState string            `json:"accessState"`
}

type jsonInvalidAccessRequestEntry struct {
	Request jsonAccessRequest `json:"request"`
	Error   string            `json:"error"`
}

type jsonAccessRequestList struct {
	Valid   []jsonAccessRequestEntry        `json:"valid"`
	Invalid []jsonInvalidAccessRequestEntry `json:"invalid"`
}

func jsonProjectManifestFromDomain(project domain.ProjectManifest) jsonProjectManifest {
	environments := map[string]domain.Environment{}
	for name, env := range project.Environments {
		environments[name] = env
	}
	return jsonProjectManifest{
		Schema:         project.Schema,
		ID:             project.ID,
		Name:           project.Name,
		Language:       project.Language,
		Framework:      project.Framework,
		PackageManager: project.PackageManager,
		DeployTarget:   project.DeployTarget,
		ActivityMode:   project.ActivityMode,
		AuditEnvs:      append([]string{}, project.AuditEnvs...),
		Environments:   environments,
		ScanLevel:      project.ScanLevel,
		ScanIgnores:    append([]string{}, project.ScanIgnores...),
	}
}

func jsonDeviceRecordFromDomain(device domain.DeviceRecord) jsonDeviceRecord {
	return jsonDeviceRecord{
		Schema:        device.Schema,
		ID:            device.ID,
		Name:          device.Name,
		Platform:      device.Platform,
		Status:        device.Status,
		CreatedAt:     device.CreatedAt,
		SigningKey:    device.SigningKey,
		EncryptionKey: device.EncryptionKey,
	}
}

func jsonDeviceRecordsFromDomain(devices []domain.DeviceRecord) []jsonDeviceRecord {
	result := make([]jsonDeviceRecord, 0, len(devices))
	for _, device := range devices {
		result = append(result, jsonDeviceRecordFromDomain(device))
	}
	return result
}

func jsonAccessRequestFromDomain(request domain.AccessRequest) jsonAccessRequest {
	return jsonAccessRequest{
		Schema:      request.Schema,
		ProjectID:   request.ProjectID,
		ID:          request.ID,
		DeviceID:    request.DeviceID,
		Environment: request.Environment,
		Role:        request.Role,
		Reason:      request.Reason,
		CreatedAt:   request.CreatedAt,
	}
}

func jsonAccessRequestListFromStore(requests store.AccessRequestList) jsonAccessRequestList {
	result := jsonAccessRequestList{
		Valid:   make([]jsonAccessRequestEntry, 0, len(requests.Valid)),
		Invalid: make([]jsonInvalidAccessRequestEntry, 0, len(requests.Invalid)),
	}
	for _, entry := range requests.Valid {
		result.Valid = append(result.Valid, jsonAccessRequestEntry{
			Request:     jsonAccessRequestFromDomain(entry.Request),
			Device:      jsonDeviceRecordFromDomain(entry.Device),
			AccessState: entry.AccessState,
		})
	}
	for _, entry := range requests.Invalid {
		result.Invalid = append(result.Invalid, jsonInvalidAccessRequestEntry{
			Request: jsonAccessRequestFromDomain(entry.Request),
			Error:   entry.Error,
		})
	}
	return result
}

func (r *Runner) printSetupResult(repo store.Repository, dotenvSeed *defaultDotenvSeed, seedResult *store.PushResult, deferSeedNext bool) {
	fmt.Fprintln(r.out, strings.TrimRight(renderGhostableBanner(), "\n"))
	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, "------------------------------------------------------------")
	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, success(fmt.Sprintf("Initialized Ghostable for %s.", repo.Manifest.Name)))
	fmt.Fprintf(r.out, "%s %s\n", warn("Manifest:"), repo.ManifestPath)
	fmt.Fprintf(r.out, "%s %s\n", warn("Local key:"), repo.KeyPath())
	if seedResult != nil {
		fmt.Fprintln(r.out, success(fmt.Sprintf("Imported %d variables from %s into default.", len(dotenvSeed.values), dotenvSeed.file)))
		if !deferSeedNext {
			fmt.Fprintln(r.out, warn("Next: review with `ghostable env diff --env default --file .env`."))
		}
	} else if dotenvSeed != nil {
		fmt.Fprintln(r.out, warn(fmt.Sprintf("No variables found in %s.", dotenvSeed.file)))
		fmt.Fprintln(r.out, warn("Next: add variables with `ghostable env push --env default --file .env`."))
	} else {
		fmt.Fprintln(r.out, warn("Next: add variables with `ghostable env push --env default --file .env`."))
	}
}

type setupExampleOptions struct {
	Create    bool
	NoCreate  bool
	ValueMode string
	JSON      bool
}

type setupExampleCreationResult struct {
	File          string   `json:"file"`
	ValueMode     string   `json:"valueMode"`
	Keys          []string `json:"keys"`
	KeptValues    int      `json:"keptValues"`
	BlankedValues int      `json:"blankedValues"`
	Written       bool     `json:"written"`
}

func (r *Runner) shouldConsiderSetupExample(repo store.Repository, dotenvSeed *defaultDotenvSeed, seedResult *store.PushResult, options setupExampleOptions) (bool, error) {
	if dotenvSeed == nil || seedResult == nil || len(dotenvSeed.values) == 0 {
		return false, nil
	}
	missing, err := setupExampleFileMissing(repo)
	if err != nil || !missing {
		return false, err
	}
	if options.JSON {
		return options.Create, nil
	}
	if options.Create {
		return true, nil
	}
	if options.NoCreate {
		return r.interactive, nil
	}
	return r.interactive, nil
}

func setupExampleFileMissing(repo store.Repository) (bool, error) {
	path, err := resolveEnvFileSavePath(repo.Root, ".env.example")
	if err != nil {
		return false, err
	}
	if _, err := os.Stat(path); err == nil {
		return false, nil
	} else if os.IsNotExist(err) {
		return true, nil
	} else {
		return false, err
	}
}

func (r *Runner) finishSetupExample(repo store.Repository, dotenvSeed *defaultDotenvSeed, options setupExampleOptions) (*setupExampleCreationResult, error) {
	if options.NoCreate {
		r.printSkippedSetupExample()
		return nil, nil
	}

	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, warn("No .env.example file was found."))
	shouldCreate := options.Create
	if !shouldCreate {
		label := "Create .env.example from the imported keys? Sensitive-looking values will be blank."
		confirmed, err := r.prompts.Confirm(label, true)
		if err != nil {
			return nil, err
		}
		r.printPromptAnswer(label, yesNo(confirmed))
		shouldCreate = confirmed
	}
	if !shouldCreate {
		r.printSkippedSetupExample()
		return nil, nil
	}

	result, err := createSetupExample(repo, dotenvSeed, options)
	if err != nil {
		return nil, err
	}
	r.printCreatedSetupExample(result)
	return &result, nil
}

func createSetupExample(repo store.Repository, dotenvSeed *defaultDotenvSeed, options setupExampleOptions) (setupExampleCreationResult, error) {
	_, result, err := generateExampleFile(repo, exampleGenerateOptions{
		File:         ".env.example",
		Environments: []string{domain.DefaultEnvName},
		Replace:      true,
		Write:        true,
		ValueMode:    options.ValueMode,
	})
	if err != nil {
		return setupExampleCreationResult{}, err
	}
	kept, blanked := setupExampleValueCounts(dotenvSeed, result.Keys, options.ValueMode)
	return setupExampleCreationResult{
		File:          result.File,
		ValueMode:     result.ValueMode,
		Keys:          append([]string{}, result.Keys...),
		KeptValues:    kept,
		BlankedValues: blanked,
		Written:       result.Written,
	}, nil
}

func setupExampleValueCounts(dotenvSeed *defaultDotenvSeed, keys []string, valueMode string) (int, int) {
	kept := 0
	blanked := 0
	for _, key := range keys {
		input, ok := dotenvSeed.values[key]
		if !ok {
			blanked++
			continue
		}
		switch valueMode {
		case exampleValuesAll:
			if input.Value == "" {
				blanked++
			} else {
				kept++
			}
		case exampleValuesBlank:
			blanked++
		default:
			if input.Value != "" && exampleVariableValueAllowed(domain.Variable{Key: key, Value: input.Value}, valueMode) {
				kept++
			} else {
				blanked++
			}
		}
	}
	return kept, blanked
}

func (r *Runner) printCreatedSetupExample(result setupExampleCreationResult) {
	fmt.Fprintln(r.out, success(fmt.Sprintf("Created .env.example with %s.", keyCountText(len(result.Keys)))))
	switch result.ValueMode {
	case exampleValuesAll:
		fmt.Fprintf(r.out, "%s\n", success(fmt.Sprintf("Kept example values for %s.", keyCountText(result.KeptValues))))
		if result.BlankedValues > 0 {
			fmt.Fprintf(r.out, "%s\n", warn(fmt.Sprintf("Left %s blank because their imported values were empty.", keyCountText(result.BlankedValues))))
		}
	case exampleValuesBlank:
		fmt.Fprintf(r.out, "%s\n", warn(fmt.Sprintf("Blanked %d value%s.", result.BlankedValues, plural(result.BlankedValues))))
	default:
		fmt.Fprintf(r.out, "%s\n", success(fmt.Sprintf("Kept example values for %d non-sensitive key%s.", result.KeptValues, plural(result.KeptValues))))
		fmt.Fprintf(r.out, "%s\n", warn(fmt.Sprintf("Blanked %d sensitive-looking value%s.", result.BlankedValues, plural(result.BlankedValues))))
	}
	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, warn("Next: review with `ghostable env diff --env default --file .env`."))
}

func (r *Runner) printSkippedSetupExample() {
	fmt.Fprintln(r.out, warn("Skipped .env.example."))
	fmt.Fprintln(r.out, warn("You can create one later with `ghostable example generate`."))
}

type setupAgentInstructionsOptions struct {
	Add   bool
	NoAdd bool
	JSON  bool
}

type setupAgentInstructionsResult struct {
	File    string `json:"file"`
	Written bool   `json:"written"`
}

func (r *Runner) shouldConsiderSetupAgentInstructions(options setupAgentInstructionsOptions) bool {
	if options.JSON {
		return options.Add
	}
	if options.Add {
		return true
	}
	if options.NoAdd {
		return r.interactive && !agentInstructionsManagedBlockExists("AGENTS.md")
	}
	return r.interactive && !agentInstructionsManagedBlockExists("AGENTS.md")
}

func (r *Runner) finishSetupAgentInstructions(options setupAgentInstructionsOptions) (*setupAgentInstructionsResult, error) {
	if options.NoAdd {
		r.printSkippedSetupAgentInstructions()
		return nil, nil
	}
	shouldAdd := options.Add
	if !shouldAdd {
		label := "Add Ghostable guidance to AGENTS.md for AI coding assistants?"
		confirmed, err := r.prompts.Confirm(label, true)
		if err != nil {
			return nil, err
		}
		r.printPromptAnswer(label, yesNo(confirmed))
		shouldAdd = confirmed
	}
	if !shouldAdd {
		r.printSkippedSetupAgentInstructions()
		return nil, nil
	}
	if err := r.runAgentInit(nil); err != nil {
		return nil, err
	}
	return &setupAgentInstructionsResult{File: "AGENTS.md", Written: true}, nil
}

func writeSetupAgentInstructions() (setupAgentInstructionsResult, error) {
	if err := writeAgentInstructionsFile("AGENTS.md"); err != nil {
		return setupAgentInstructionsResult{}, err
	}
	return setupAgentInstructionsResult{File: "AGENTS.md", Written: true}, nil
}

func (r *Runner) printSkippedSetupAgentInstructions() {
	fmt.Fprintln(r.out, warn("Skipped AGENTS.md."))
	fmt.Fprintln(r.out, warn("You can add it later with `ghostable agent init`."))
}

type setupAdoptPromptOptions struct {
	Print   bool
	NoPrint bool
	JSON    bool
}

func (r *Runner) finishSetupAdoptPrompt(options setupAdoptPromptOptions) error {
	if options.JSON || options.NoPrint {
		return nil
	}

	shouldPrint := options.Print
	if !shouldPrint {
		if !r.interactive {
			return nil
		}
		fmt.Fprintln(r.out)
		label := "Generate a Ghostable adoption prompt for your AI coding assistant now?"
		confirmed, err := r.prompts.Confirm(label, true)
		if err != nil {
			return err
		}
		r.printPromptAnswer(label, yesNo(confirmed))
		shouldPrint = confirmed
	}
	if !shouldPrint {
		fmt.Fprintln(r.out, "You can generate it later with `ghostable adopt`.")
		return nil
	}

	selection := defaultAdoptSections()
	if r.interactive {
		var err error
		selection, err = r.promptAdoptSections()
		if err != nil {
			return err
		}
	}
	return printAdoptPrompt(r.out, selection)
}

type defaultDotenvSeed struct {
	file   string
	values map[string]store.VariablePutInput
	keys   []string
}

func (r *Runner) resolveDefaultDotenvSeed(envs []domain.Environment, jsonOut bool, seedDotenv bool, noSeedDotenv bool) (*defaultDotenvSeed, error) {
	if noSeedDotenv || !setupHasDefaultEnvironment(envs) {
		return nil, nil
	}
	if seedDotenv {
		return readDefaultDotenvSeed()
	}
	return r.promptDefaultDotenvSeed(envs, jsonOut)
}

func (r *Runner) promptDefaultDotenvSeed(envs []domain.Environment, jsonOut bool) (*defaultDotenvSeed, error) {
	if jsonOut || !r.interactive || !setupHasDefaultEnvironment(envs) {
		return nil, nil
	}

	seed, err := readDefaultDotenvSeed()
	if err != nil || seed == nil {
		return seed, err
	}

	useDotenv, err := r.prompts.Confirm("A .env file was detected in this project directory. Use it as the starting values for the default environment?", true)
	if err != nil {
		return nil, err
	}
	if !useDotenv {
		return nil, nil
	}

	return seed, nil
}

func readDefaultDotenvSeed() (*defaultDotenvSeed, error) {
	path := filepath.Join(mustGetwd(), ".env")
	info, err := os.Stat(path)
	if err != nil {
		if os.IsNotExist(err) {
			return nil, nil
		}
		return nil, err
	}
	if !info.Mode().IsRegular() {
		return nil, nil
	}

	values, keys, err := readDotenvVariableInputsAndKeys(path)
	if err != nil {
		return nil, err
	}
	return &defaultDotenvSeed{file: ".env", values: values, keys: keys}, nil
}

func setupHasDefaultEnvironment(envs []domain.Environment) bool {
	for _, env := range envs {
		if env.Name == domain.DefaultEnvName {
			return true
		}
	}
	return false
}

func (r *Runner) runStatus(args []string) error {
	fs := newFlagSet("status", r.errOut)
	jsonOut := fs.Bool("json", false, "Print local status as JSON")
	if _, err := cli.Parse(fs, args, cli.BoolFlags("json")); err != nil {
		return err
	}

	repo, err := r.openRepo()
	if err != nil {
		return err
	}

	devices, _ := repo.Devices()
	accessRequests, _ := repo.ListAccessRequests(false)
	envs := repo.Environments()
	counts := map[string]int{}
	for _, env := range envs {
		values, err := repo.ReadVariables(env.Name)
		if err == nil {
			counts[env.Name] = len(values)
		}
	}

	payload := map[string]interface{}{
		"project":        jsonProjectManifestFromDomain(repo.Manifest),
		"root":           repo.Root,
		"manifestPath":   repo.ManifestPath,
		"keyPath":        repo.KeyPath(),
		"deviceId":       repo.DeviceID(),
		"devices":        jsonDeviceRecordsFromDomain(devices),
		"valueCounts":    counts,
		"accessRequests": jsonAccessRequestListFromStore(accessRequests),
	}
	if *jsonOut {
		return printJSON(r.out, payload)
	}

	r.printStatus(repo, envs, devices, counts, accessRequests)
	return nil
}

func (r *Runner) printStatus(repo store.Repository, envs []domain.Environment, devices []domain.DeviceRecord, counts map[string]int, accessRequests store.AccessRequestList) {
	totalValues := 0
	for _, env := range envs {
		totalValues += counts[env.Name]
	}
	pendingRequests := len(accessRequests.Valid)

	fmt.Fprintln(r.out, strings.TrimRight(renderGhostableBanner(), "\n"))
	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, "------------------------------------------------------------")
	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, warn("Ghostable status"))
	r.printStatusField("Project", repo.Manifest.Name)
	r.printStatusField("Root", repo.Root)
	r.printStatusField("Manifest", repo.ManifestPath)
	r.printStatusField("Local key", repo.KeyPath())
	r.printStatusField("Device", statusLocalDevice(repo, devices))
	if stack := statusStack(repo.Manifest); stack != "" {
		r.printStatusField("Stack", stack)
	}
	if repo.Manifest.DeployTarget != "" {
		r.printStatusField("Deploy target", repo.Manifest.DeployTarget)
	}
	if repo.Manifest.ActivityMode != "" {
		r.printStatusField("Activity", repo.Manifest.ActivityMode)
	}

	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, warn("Inventory"))
	printStatusInventoryRow(r, "Environments", fmt.Sprintf("%d", len(envs)))
	printStatusInventoryRow(r, "Devices", fmt.Sprintf("%d", len(devices)))
	printStatusInventoryRow(r, "Values", fmt.Sprintf("%d", totalValues))
	printStatusInventoryRow(r, "Requests", fmt.Sprintf("%d pending", pendingRequests))

	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, warn("Environments"))
	printStatusEnvironmentRows(r, envs, counts)

	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, warn("Devices"))
	printStatusDeviceRows(r, repo, devices)

	if len(accessRequests.Valid) > 0 || len(accessRequests.Invalid) > 0 {
		fmt.Fprintln(r.out)
		fmt.Fprintln(r.out, warn("Pending requests"))
		printAccessRequestRows(r.out, accessRequests.Valid, false)
		if len(accessRequests.Invalid) > 0 {
			fmt.Fprintln(r.out)
			fmt.Fprintln(r.out, warn("Ignored requests:"))
			for _, entry := range accessRequests.Invalid {
				fmt.Fprintf(r.out, "  %s  %s\n", statusShortID(entry.Request.ID), danger(entry.Error))
			}
		}
	}
}

func (r *Runner) printStatusField(label string, value string) {
	fmt.Fprintf(r.out, "%s  %s\n", warn(fmt.Sprintf("%-13s", label)), success(value))
}

func printStatusInventoryRow(r *Runner, label string, value string) {
	fmt.Fprintf(r.out, "  %s  %s\n", warn(fmt.Sprintf("%-12s", label)), success(value))
}

func printStatusEnvironmentRows(r *Runner, envs []domain.Environment, counts map[string]int) {
	if len(envs) == 0 {
		fmt.Fprintln(r.out, "  "+muted("none"))
		return
	}

	nameWidth := len("Name")
	typeWidth := len("Type")
	for _, env := range envs {
		nameWidth = max(nameWidth, len(env.Name))
		typeWidth = max(typeWidth, len(env.Type))
	}

	fmt.Fprintln(r.out, warn(fmt.Sprintf("  %-*s  %-*s  %s", nameWidth, "Name", typeWidth, "Type", "Values")))
	fmt.Fprintln(r.out, muted(fmt.Sprintf("  %-*s  %-*s  %s", nameWidth, strings.Repeat("-", nameWidth), typeWidth, strings.Repeat("-", typeWidth), "------")))
	for _, env := range envs {
		fmt.Fprintf(r.out, "  %s  %-*s  %s\n",
			coloredCell(env.Name, nameWidth, success),
			typeWidth,
			env.Type,
			success(fmt.Sprintf("%d", counts[env.Name])),
		)
	}
}

func printStatusDeviceRows(r *Runner, repo store.Repository, devices []domain.DeviceRecord) {
	if len(devices) == 0 {
		fmt.Fprintln(r.out, "  "+muted("none"))
		return
	}

	nameWidth := len("Name")
	platformWidth := len("Platform")
	statusWidth := len("Status")
	for _, device := range devices {
		nameWidth = max(nameWidth, len(statusDeviceName(device)))
		platformWidth = max(platformWidth, len(statusDevicePlatform(device)))
		statusWidth = max(statusWidth, len(deviceStatusDisplay(device.Status)))
	}

	fmt.Fprintln(r.out, warn(fmt.Sprintf("  %-*s  %-*s  %-*s  %-7s  %s", nameWidth, "Name", platformWidth, "Platform", statusWidth, "Status", "Current", "ID")))
	fmt.Fprintln(r.out, muted(fmt.Sprintf("  %-*s  %-*s  %-*s  %-7s  %s", nameWidth, strings.Repeat("-", nameWidth), platformWidth, strings.Repeat("-", platformWidth), statusWidth, strings.Repeat("-", statusWidth), "-------", "--")))
	for _, device := range devices {
		current := ""
		if device.ID == repo.DeviceID() {
			current = "yes"
		}
		status := deviceStatusDisplay(device.Status)
		fmt.Fprintf(r.out, "  %s  %-*s  %s  %s  %s\n",
			coloredCell(statusDeviceName(device), nameWidth, success),
			platformWidth,
			statusDevicePlatform(device),
			coloredCell(status, statusWidth, deviceStatusColor),
			coloredCell(current, 7, currentColor),
			muted(statusShortID(device.ID)),
		)
	}
}

func statusLocalDevice(repo store.Repository, devices []domain.DeviceRecord) string {
	for _, device := range devices {
		if device.ID == repo.DeviceID() {
			return fmt.Sprintf("%s (%s)", statusDeviceName(device), statusShortID(device.ID))
		}
	}
	return repo.DeviceID()
}

func statusStack(project domain.ProjectManifest) string {
	parts := []string{}
	if project.Language != "" {
		parts = append(parts, project.Language)
	}
	if project.Framework != "" {
		parts = append(parts, project.Framework)
	}
	if project.PackageManager != "" {
		parts = append(parts, project.PackageManager)
	}
	return strings.Join(parts, " / ")
}

func statusDeviceName(device domain.DeviceRecord) string {
	name := strings.TrimSpace(terminalSafeText(device.Name))
	if name == "" {
		return "Unnamed device"
	}
	return name
}

func statusDevicePlatform(device domain.DeviceRecord) string {
	platform := strings.TrimSpace(terminalSafeText(device.Platform))
	if platform == "" {
		return "-"
	}
	return platform
}

func statusShortID(id string) string {
	if len(id) > 18 {
		return id[:18] + "..."
	}
	return id
}

func defaultDeviceName() string {
	for _, candidate := range defaultDeviceNameCandidates() {
		if name := normalizeDefaultDeviceName(candidate); name != "" {
			return name
		}
	}
	return domain.DefaultDeviceName
}

func defaultDeviceNameCandidates() []string {
	candidates := []string{}
	if runtime.GOOS == "darwin" {
		candidates = append(candidates, macOSComputerName())
	}
	if host, err := os.Hostname(); err == nil {
		candidates = append(candidates, host)
	}
	return candidates
}

func macOSComputerName() string {
	ctx, cancel := context.WithTimeout(context.Background(), 500*time.Millisecond)
	defer cancel()

	output, err := exec.CommandContext(ctx, "/usr/sbin/scutil", "--get", "ComputerName").Output()
	if err != nil {
		return ""
	}
	return string(output)
}

func normalizeDefaultDeviceName(value string) string {
	name := strings.TrimSpace(strings.ReplaceAll(value, "\x00", ""))
	if len(name) > len(".local") && strings.EqualFold(name[len(name)-len(".local"):], ".local") {
		name = name[:len(name)-len(".local")]
	}
	return strings.TrimSpace(name)
}

func mustGetwd() string {
	wd, err := os.Getwd()
	if err != nil {
		return "."
	}
	return wd
}

func environmentType(name string) string {
	switch strings.ToLower(name) {
	case "local", "default":
		return "local"
	case "dev", "development":
		return "development"
	case "preview":
		return "preview"
	case "stage", "staging":
		return "staging"
	case "prod", "production":
		return "production"
	default:
		return "custom"
	}
}
