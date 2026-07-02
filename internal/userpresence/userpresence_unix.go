//go:build !windows && !darwin

package userpresence

import (
	"fmt"
	"os/exec"
)

func verifyPlatformUserPresence(request Request) error {
	sudoPath, err := exec.LookPath("sudo")
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

func sudoPrompt(request Request) string {
	return confirmationMessage(request) + " Password: "
}
