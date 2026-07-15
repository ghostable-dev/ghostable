package app

import (
	"fmt"
	"os"
	"runtime"
	"strings"

	"github.com/ghostable-dev/ghostable/internal/cli"
)

var resolveEnvShellCommand = defaultEnvShellCommand

func (r *Runner) runEnvShell(args []string) error {
	if len(args) > 0 && isHelpArg(args[0]) {
		r.printEnvShellHelp()
		return nil
	}

	fs := newFlagSet("env shell", r.errOut)
	env := fs.String("env", "", "Environment name")
	var only cli.Strings
	fs.Var(&only, "only", "Only inject these keys; may be repeated or comma-separated")
	inherit := fs.Bool("inherit", true, "Inherit the current process environment")
	noInherit := fs.Bool("no-inherit", false, "Run with only Ghostable values and minimal system env")
	strict := fs.Bool("strict", false, "Validate injected values and fail when requested keys are missing")
	inheritProvided := hasFlag(args, "inherit")
	noInheritProvided := hasFlag(args, "no-inherit")
	positionals, err := cli.Parse(fs, args, cli.BoolFlags("inherit", "no-inherit", "strict"))
	if err != nil {
		return err
	}
	if inheritProvided && noInheritProvided {
		return fmt.Errorf("pass either --inherit or --no-inherit, not both")
	}
	if len(positionals) > 0 {
		return fmt.Errorf("usage: ghostable env shell --env <ENV> [options]")
	}

	repo, err := r.openRepo()
	if err != nil {
		return err
	}
	selected, err := r.selectEnvironment(repo, *env)
	if err != nil {
		return err
	}
	if err := r.requireProtectedEnvironmentAccess(repo, selected, protectedOperationEnvRun); err != nil {
		return err
	}
	command, err := resolveEnvShellCommand()
	if err != nil {
		return err
	}

	return r.runCommandWithEnvironment(repo, envRunRequest{
		Environment: selected,
		Only:        only,
		Inherit:     *inherit && !*noInherit,
		Strict:      *strict,
		Command:     command,
	})
}

func (r *Runner) printEnvShellHelp() {
	fmt.Fprintln(r.out, "Usage: ghostable env shell --env <ENV> [options]")
	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, "Open a shell with decrypted environment values without writing a .env file.")
	fmt.Fprintln(r.out)
	fmt.Fprintln(r.out, warn("Options:"))
	fmt.Fprintln(r.out, "  --env <ENV>       Environment name")
	fmt.Fprintln(r.out, "  --only <KEYS>     Only inject these keys; may be repeated or comma-separated")
	fmt.Fprintln(r.out, "  --inherit         Inherit the current process environment (default)")
	fmt.Fprintln(r.out, "  --no-inherit      Run with only Ghostable values and minimal system env")
	fmt.Fprintln(r.out, "  --strict          Validate injected values and fail when requested keys are missing")
}

func defaultEnvShellCommand() ([]string, error) {
	if runtime.GOOS == "windows" {
		if shell := strings.TrimSpace(os.Getenv("COMSPEC")); shell != "" {
			return []string{shell}, nil
		}
		return []string{"cmd"}, nil
	}
	if shell := strings.TrimSpace(os.Getenv("SHELL")); shell != "" {
		return []string{shell}, nil
	}
	return []string{"/bin/sh"}, nil
}
