package manifest

import (
	"bufio"
	"fmt"
	"io"
	"sort"
	"strings"

	"github.com/ghostable-dev/beta/internal/domain"
)

func New(projectID string, name string, envs []domain.Environment) domain.ProjectManifest {
	environments := make(map[string]domain.Environment, len(envs))
	for _, env := range envs {
		if env.Name == "" {
			continue
		}
		if env.Type == "" {
			env.Type = inferType(env.Name)
		}
		environments[env.Name] = env
	}

	return domain.ProjectManifest{
		Schema:       domain.ProjectSchema,
		ID:           projectID,
		Name:         name,
		ActivityMode: domain.DefaultActivity,
		AuditEnvs:    []string{"production", "staging"},
		Environments: environments,
		ScanLevel:    "standard",
		ScanIgnores: []string{
			".git/**",
			"node_modules/**",
			"vendor/**",
			"dist/**",
			"build/**",
			".ghostable/environments/**/values/**",
		},
	}
}

func Read(reader io.Reader) (domain.ProjectManifest, error) {
	result := domain.ProjectManifest{
		Environments: make(map[string]domain.Environment),
	}

	scanner := bufio.NewScanner(reader)
	section := ""
	currentEnv := ""
	inScan := false
	inScanIgnores := false

	for scanner.Scan() {
		raw := scanner.Text()
		line := stripComment(raw)
		if strings.TrimSpace(line) == "" {
			continue
		}

		indent := leadingSpaces(line)
		trimmed := strings.TrimSpace(line)

		if indent == 0 {
			inScan = false
			inScanIgnores = false
			currentEnv = ""

			switch {
			case strings.HasPrefix(trimmed, "schema:"):
				result.Schema = scalarValue(trimmed)
			case strings.HasPrefix(trimmed, "id:"):
				result.ID = scalarValue(trimmed)
			case strings.HasPrefix(trimmed, "name:"):
				result.Name = scalarValue(trimmed)
			case strings.HasPrefix(trimmed, "language:"):
				result.Language = scalarValue(trimmed)
			case strings.HasPrefix(trimmed, "framework:"):
				result.Framework = scalarValue(trimmed)
			case strings.HasPrefix(trimmed, "packageManager:"):
				result.PackageManager = scalarValue(trimmed)
			case strings.HasPrefix(trimmed, "package_manager:"):
				result.PackageManager = scalarValue(trimmed)
			case strings.HasPrefix(trimmed, "deployTarget:"):
				result.DeployTarget = scalarValue(trimmed)
			case strings.HasPrefix(trimmed, "deploy_target:"):
				result.DeployTarget = scalarValue(trimmed)
			case strings.HasPrefix(trimmed, "environments:"):
				section = "environments"
			case strings.HasPrefix(trimmed, "activity:"):
				section = "activity"
			case strings.HasPrefix(trimmed, "scan:"):
				section = "scan"
				inScan = true
			default:
				section = ""
			}
			continue
		}

		if section == "environments" && indent == 2 && strings.HasSuffix(trimmed, ":") {
			currentEnv = strings.TrimSuffix(trimmed, ":")
			currentEnv = readScalar(currentEnv)
			if currentEnv != "" {
				result.Environments[currentEnv] = domain.Environment{
					Name: currentEnv,
					Type: inferType(currentEnv),
				}
			}
			continue
		}

		if section == "environments" && currentEnv != "" && indent >= 4 && strings.HasPrefix(trimmed, "type:") {
			env := result.Environments[currentEnv]
			env.Type = scalarValue(trimmed)
			if env.Type == "" {
				env.Type = inferType(env.Name)
			}
			result.Environments[currentEnv] = env
			continue
		}

		if section == "activity" && indent == 2 && strings.HasPrefix(trimmed, "mode:") {
			result.ActivityMode = scalarValue(trimmed)
			continue
		}

		if section == "activity" && indent == 2 && strings.HasPrefix(trimmed, "auditEnvironments:") {
			result.AuditEnvs = nil
			continue
		}

		if section == "activity" && indent >= 4 && strings.HasPrefix(trimmed, "- ") {
			result.AuditEnvs = append(result.AuditEnvs, readScalar(strings.TrimSpace(strings.TrimPrefix(trimmed, "- "))))
			continue
		}

		if inScan && indent == 2 && strings.HasPrefix(trimmed, "ignores:") {
			inScanIgnores = true
			result.ScanIgnores = nil
			continue
		}

		if inScan && indent == 2 && strings.HasPrefix(trimmed, "level:") {
			result.ScanLevel = scanLevel(scalarValue(trimmed))
			continue
		}

		if inScanIgnores && indent >= 4 && strings.HasPrefix(trimmed, "- ") {
			result.ScanIgnores = append(result.ScanIgnores, readScalar(strings.TrimSpace(strings.TrimPrefix(trimmed, "- "))))
		}
	}

	if err := scanner.Err(); err != nil {
		return result, err
	}
	if result.Schema == "" {
		result.Schema = domain.ProjectSchema
	}
	if result.ActivityMode == "" {
		result.ActivityMode = domain.DefaultActivity
	}
	if result.ScanLevel == "" {
		result.ScanLevel = "standard"
	}
	if len(result.AuditEnvs) == 0 {
		result.AuditEnvs = []string{"production", "staging"}
	}
	if result.Environments == nil {
		result.Environments = make(map[string]domain.Environment)
	}

	return result, nil
}

func Write(writer io.Writer, project domain.ProjectManifest) error {
	if project.Schema == "" {
		project.Schema = domain.ProjectSchema
	}

	lines := []string{
		fmt.Sprintf("schema: %s", yamlScalar(project.Schema)),
		fmt.Sprintf("id: %s", yamlScalar(project.ID)),
		fmt.Sprintf("name: %s", yamlScalar(project.Name)),
	}

	if project.Language != "" {
		lines = append(lines, fmt.Sprintf("language: %s", yamlScalar(project.Language)))
	}
	if project.Framework != "" {
		lines = append(lines, fmt.Sprintf("framework: %s", yamlScalar(project.Framework)))
	}
	if project.PackageManager != "" {
		lines = append(lines, fmt.Sprintf("packageManager: %s", yamlScalar(project.PackageManager)))
	}
	if project.DeployTarget != "" {
		lines = append(lines, fmt.Sprintf("deployTarget: %s", yamlScalar(project.DeployTarget)))
	}

	if project.ActivityMode != "" || len(project.AuditEnvs) > 0 {
		lines = append(lines, "activity:")
		if project.ActivityMode != "" {
			lines = append(lines, fmt.Sprintf("  mode: %s", yamlScalar(project.ActivityMode)))
		}
		if len(project.AuditEnvs) > 0 {
			lines = append(lines, "  auditEnvironments:")
			for _, env := range project.AuditEnvs {
				lines = append(lines, fmt.Sprintf("    - %s", yamlScalar(env)))
			}
		}
	}

	envNames := make([]string, 0, len(project.Environments))
	for name := range project.Environments {
		envNames = append(envNames, name)
	}
	sort.Strings(envNames)

	lines = append(lines, "environments:")
	for _, name := range envNames {
		env := project.Environments[name]
		lines = append(lines, fmt.Sprintf("  %s:", yamlScalar(name)))
		lines = append(lines, fmt.Sprintf("    type: %s", yamlScalar(env.Type)))
	}

	if project.ScanLevel != "" || len(project.ScanIgnores) > 0 {
		lines = append(lines, "scan:")
		if project.ScanLevel != "" {
			lines = append(lines, fmt.Sprintf("  level: %s", yamlScalar(scanLevel(project.ScanLevel))))
		}
		if len(project.ScanIgnores) > 0 {
			lines = append(lines, "  ignores:")
			for _, pattern := range project.ScanIgnores {
				lines = append(lines, fmt.Sprintf("    - %s", yamlScalar(pattern)))
			}
		}
	}

	_, err := io.WriteString(writer, strings.Join(lines, "\n")+"\n")
	return err
}

func scanLevel(value string) string {
	level := strings.ToLower(strings.TrimSpace(value))
	switch level {
	case "relaxed", "standard", "strict":
		return level
	default:
		return "standard"
	}
}

func inferType(name string) string {
	switch strings.ToLower(strings.TrimSpace(name)) {
	case "local":
		return "local"
	case "dev", "development":
		return "development"
	case "preview":
		return "preview"
	case "stage", "staging":
		return "staging"
	case "prod", "production":
		return "production"
	case "default":
		return "local"
	default:
		return "custom"
	}
}

func stripComment(line string) string {
	inSingle := false
	inDouble := false
	for i, r := range line {
		switch r {
		case '\'':
			if !inDouble {
				inSingle = !inSingle
			}
		case '"':
			if !inSingle {
				inDouble = !inDouble
			}
		case '#':
			if !inSingle && !inDouble && (i == 0 || line[i-1] == ' ') {
				return line[:i]
			}
		}
	}
	return line
}

func scalarValue(line string) string {
	_, value, ok := strings.Cut(line, ":")
	if !ok {
		return ""
	}
	return readScalar(strings.TrimSpace(value))
}

func readScalar(value string) string {
	value = strings.TrimSpace(value)
	if len(value) >= 2 {
		if value[0] == '\'' && value[len(value)-1] == '\'' {
			return strings.ReplaceAll(value[1:len(value)-1], "''", "'")
		}
		if value[0] == '"' && value[len(value)-1] == '"' {
			return strings.ReplaceAll(value[1:len(value)-1], `\"`, `"`)
		}
	}
	return value
}

func yamlScalar(value string) string {
	if value == "" {
		return `""`
	}

	plain := true
	for _, r := range value {
		if !(r == '-' || r == '_' || r == '.' || r == '/' || r == '*' || r == '@' || r == ':' || r == ' ' || r >= '0' && r <= '9' || r >= 'A' && r <= 'Z' || r >= 'a' && r <= 'z') {
			plain = false
			break
		}
	}

	if plain && !strings.HasPrefix(value, " ") && !strings.HasSuffix(value, " ") && !strings.Contains(value, ": ") && !strings.HasPrefix(value, "- ") && !strings.Contains(value, "#") {
		return value
	}

	return "'" + strings.ReplaceAll(value, "'", "''") + "'"
}

func leadingSpaces(line string) int {
	count := 0
	for _, r := range line {
		if r != ' ' {
			break
		}
		count++
	}
	return count
}
