//go:build !windows && !darwin

package userpresence

import (
	"fmt"
	"os"
	"os/exec"
)

func verifyPlatformUserPresence(request Request) error {
	sudoPath, err := trustedSudoPath()
	if err != nil {
		return fmt.Errorf("sudo was not found; install sudo with PAM authentication or use a scoped GHOSTABLE_CI_TOKEN automation credential")
	}

	_ = exec.Command(sudoPath, "-k").Run()
	defer exec.Command(sudoPath, "-k").Run()

	cmd := exec.Command(sudoPath, "-p", sudoPrompt(request), "-v")
	cmd.Stdin = request.In
	cmd.Stdout = request.Out
	cmd.Stderr = request.ErrOut
	return cmd.Run()
}

func trustedSudoPath() (string, error) {
	for _, path := range []string{"/usr/bin/sudo", "/bin/sudo"} {
		info, err := os.Stat(path)
		if err == nil && !info.IsDir() && info.Mode()&0o111 != 0 {
			return path, nil
		}
	}
	return "", os.ErrNotExist
}

func sudoPrompt(request Request) string {
	return confirmationMessage(request) + " Password: "
}
