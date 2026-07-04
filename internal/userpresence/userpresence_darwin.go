//go:build darwin

package userpresence

import (
	"fmt"
	"os"
	"os/exec"
	"strconv"
)

const osascriptExecutable = "/usr/bin/osascript"

func verifyPlatformUserPresence(request Request) error {
	if info, err := os.Stat(osascriptExecutable); err != nil || info.IsDir() || info.Mode()&0o111 == 0 {
		return fmt.Errorf("osascript was not found; macOS biometric confirmation is unavailable")
	}

	cmd := exec.Command(osascriptExecutable, "-l", "JavaScript", "-e", macOSBiometricVerificationScript(confirmationMessage(request)))
	cmd.Stdin = request.In
	cmd.Stdout = request.Out
	cmd.Stderr = request.ErrOut
	return cmd.Run()
}

func macOSBiometricVerificationScript(message string) string {
	return `
ObjC.import('LocalAuthentication')
ObjC.import('Foundation')
ObjC.import('stdlib')

const context = $.LAContext.alloc.init
const policy = $.LAPolicyDeviceOwnerAuthenticationWithBiometrics
const reason = $(` + strconv.Quote(message) + `)
const canEvaluateError = Ref()

if (!context.canEvaluatePolicyError(policy, canEvaluateError)) {
    if (canEvaluateError[0]) {
        console.log(ObjC.unwrap(canEvaluateError[0].localizedDescription))
    } else {
        console.log('Biometric verification is unavailable.')
    }
    $.exit(2)
}

let completed = false
let verified = false
let failure = ''

context.evaluatePolicyLocalizedReasonReply(policy, reason, (success, error) => {
    verified = success
    if (!success && error) {
        failure = ObjC.unwrap(error.localizedDescription)
    }
    completed = true
})

const runLoop = $.NSRunLoop.currentRunLoop
while (!completed) {
    runLoop.runUntilDate($.NSDate.dateWithTimeIntervalSinceNow(0.1))
}

if (verified) {
    $.exit(0)
}
if (failure) {
    console.log(failure)
}
$.exit(1)
`
}
