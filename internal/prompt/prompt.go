package prompt

import (
	"bufio"
	"errors"
	"fmt"
	"io"
	"os"
	"os/exec"
	"runtime"
	"strconv"
	"strings"

	"github.com/chzyer/readline"
	"github.com/manifoldco/promptui"
	"github.com/manifoldco/promptui/screenbuf"
)

const (
	defaultSelectHelp = "Use arrow keys to move, Enter to select"
	hideCursor        = "\033[?25l"
	showCursor        = "\033[?25h"
)

type Session struct {
	In  io.Reader
	Out io.Writer
}

type SelectOption struct {
	Label       string
	Value       string
	Description string
}

type selectIntroMode int

const (
	selectUsesPromptUIHelp selectIntroMode = iota
	selectRendersIntro
)

type interactiveSelectMenu struct {
	itemCount    int
	defaultIndex int
	render       func(*screenbuf.ScreenBuf, int) error
	value        func(int) string
}

func IsCanceled(err error) bool {
	return errors.Is(err, promptui.ErrInterrupt) || errors.Is(err, promptui.ErrEOF)
}

func New(in io.Reader, out io.Writer) Session {
	return Session{In: in, Out: out}
}

func IsTerminal(file *os.File) bool {
	if file == nil {
		return false
	}

	info, err := file.Stat()
	if err != nil {
		return false
	}

	return info.Mode()&os.ModeCharDevice != 0
}

func (s Session) Ask(label string, defaultValue string) (string, error) {
	return s.askPlain(label, defaultValue)
}

func (s Session) AskHighlighted(label string, defaultValue string) (string, error) {
	return s.askHighlighted(label, defaultValue, true)
}

func (s Session) AskHighlightedTight(label string, defaultValue string) (string, error) {
	return s.askHighlighted(label, defaultValue, false)
}

func (s Session) askHighlighted(label string, defaultValue string, leadingBlank bool) (string, error) {
	if s.usesTerminalIO() && runtime.GOOS != "windows" && os.Getenv("TERM") != "dumb" {
		return s.askInteractiveHighlighted(label, defaultValue, leadingBlank)
	}

	if leadingBlank {
		fmt.Fprintln(s.Out)
	}
	return s.askPlain(label, defaultValue)
}

func (s Session) askPlain(label string, defaultValue string) (string, error) {
	reader := bufio.NewReader(s.In)
	if defaultValue != "" {
		fmt.Fprintf(s.Out, "%s [%s]: ", label, defaultValue)
	} else {
		fmt.Fprintf(s.Out, "%s: ", label)
	}

	value, err := reader.ReadString('\n')
	if err != nil && err != io.EOF {
		return "", err
	}

	value = strings.TrimSpace(value)
	if value == "" {
		return defaultValue, nil
	}

	return value, nil
}

func (s Session) askInteractiveHighlighted(label string, defaultValue string, leadingBlank bool) (string, error) {
	inFile := s.In.(*os.File)
	outFile := s.Out.(*os.File)

	if leadingBlank {
		fmt.Fprintln(s.Out)
	}

	config := &readline.Config{
		Stdin:          inFile,
		Stdout:         bellFilterWriter{out: outFile},
		HistoryLimit:   -1,
		UniqueEditLine: true,
	}
	if err := config.Init(); err != nil {
		return "", err
	}

	rl, err := readline.NewEx(config)
	if err != nil {
		return "", err
	}
	defer rl.Close()

	rl.Write([]byte(hideCursor))
	defer rl.Write([]byte(resetStyle))
	defer rl.Write([]byte(showCursor))

	cur := promptui.NewCursor(defaultValue, promptui.DefaultCursor, defaultValue != "")
	sb := screenbuf.New(rl)
	render := func(value string, final bool) {
		highlightedValue := greenOpen(value)
		if final {
			highlightedValue = green(value)
		}
		sb.Reset()
		_, _ = sb.WriteString(resetStyle + textPromptLine(label, defaultValue, highlightedValue, final))
		_ = sb.Flush()
		if !final {
			_, _ = rl.Write([]byte(greenStart))
		}
	}
	render(cur.Format(), false)

	config.SetListener(func(input []rune, pos int, key rune) ([]rune, int, bool) {
		_, _, keepOn := cur.Listen(input, pos, key)
		render(cur.Format(), false)
		return nil, 0, keepOn
	})

	for {
		_, err = rl.Readline()
		if err != nil {
			sb.Reset()
			_, _ = sb.WriteString("")
			_ = sb.Flush()
			switch {
			case err == readline.ErrInterrupt, err.Error() == "Interrupt":
				return "", promptui.ErrInterrupt
			case err == io.EOF:
				return "", promptui.ErrEOF
			default:
				return "", err
			}
		}
		break
	}

	value := strings.TrimSpace(cur.Get())
	if value == "" {
		value = defaultValue
	}
	render(value, true)
	return value, nil
}

func (s Session) Confirm(label string, defaultValue bool) (bool, error) {
	if s.usesTerminalIO() && runtime.GOOS != "windows" && os.Getenv("TERM") != "dumb" {
		defaultIndex := 1
		if defaultValue {
			defaultIndex = 0
		}
		selected, err := s.Select(label, []string{"Yes", "No"}, defaultIndex)
		if err != nil {
			return false, err
		}
		return selected == "Yes", nil
	}

	defaultText := "y"
	if !defaultValue {
		defaultText = "n"
	}

	for {
		answer, err := s.Ask(fmt.Sprintf("%s (y/n)", label), defaultText)
		if err != nil {
			return false, err
		}

		switch strings.ToLower(strings.TrimSpace(answer)) {
		case "y", "yes":
			return true, nil
		case "n", "no":
			return false, nil
		}

		fmt.Fprintln(s.Out, "Please answer yes or no.")
	}
}

func (s Session) Secret(label string) (string, error) {
	if runtime.GOOS == "windows" {
		return "", fmt.Errorf("secure secret prompts are not available on this platform; pass a file instead")
	}

	file, ok := s.In.(*os.File)
	if !ok || !IsTerminal(file) {
		return "", fmt.Errorf("secure secret prompts require an interactive terminal")
	}

	fmt.Fprintf(s.Out, "%s: ", label)
	disable := exec.Command("stty", "-echo")
	disable.Stdin = file
	if err := disable.Run(); err != nil {
		return "", fmt.Errorf("unable to disable terminal echo")
	}
	defer func() {
		enable := exec.Command("stty", "echo")
		enable.Stdin = file
		_ = enable.Run()
		fmt.Fprintln(s.Out)
	}()

	reader := bufio.NewReader(s.In)
	value, err := reader.ReadString('\n')
	if err != nil && err != io.EOF {
		return "", err
	}

	return strings.TrimRight(value, "\r\n"), nil
}

func (s Session) Select(label string, choices []string, defaultIndex int) (string, error) {
	return s.SelectWithHelp(defaultSelectHelp, label, choices, defaultIndex)
}

func (s Session) SelectWithHelp(help string, label string, choices []string, defaultIndex int) (string, error) {
	return s.selectWithIntro(helpIntro(help), label, choices, defaultIndex, selectUsesPromptUIHelp)
}

func (s Session) SelectWithIntro(intro []string, label string, choices []string, defaultIndex int) (string, error) {
	return s.selectWithIntro(normalizeSelectIntro(intro), label, choices, defaultIndex, selectRendersIntro)
}

func (s Session) SelectOptions(label string, options []SelectOption, defaultIndex int) (string, error) {
	return s.SelectOptionsWithIntro(helpIntro(defaultSelectHelp), label, options, defaultIndex)
}

func (s Session) SelectOptionsWithIntro(intro []string, label string, options []SelectOption, defaultIndex int) (string, error) {
	options, err := normalizeSelectOptions(options)
	if err != nil {
		return "", err
	}
	if defaultIndex < 0 || defaultIndex >= len(options) {
		defaultIndex = 0
	}
	intro = normalizeSelectIntro(intro)

	if s.usesTerminalIO() && runtime.GOOS != "windows" && os.Getenv("TERM") != "dumb" {
		return s.selectInteractiveOptionsWithIntro(intro, label, options, defaultIndex)
	}

	for _, line := range intro {
		if strings.TrimSpace(line) != "" {
			fmt.Fprintln(s.Out, line)
		} else {
			fmt.Fprintln(s.Out)
		}
	}
	fmt.Fprintln(s.Out, label)
	width := selectOptionLabelWidth(options)
	for index, option := range options {
		marker := " "
		if index == defaultIndex {
			marker = "*"
		}
		fmt.Fprintf(s.Out, "  %s %d. %s\n", marker, index+1, formatSelectOption(option, width, false))
	}

	for {
		answer, err := s.Ask("Select", strconv.Itoa(defaultIndex+1))
		if err != nil {
			return "", err
		}

		index, err := strconv.Atoi(answer)
		if err == nil && index >= 1 && index <= len(options) {
			return options[index-1].Value, nil
		}

		for _, option := range options {
			if strings.EqualFold(option.Label, answer) || strings.EqualFold(option.Value, answer) {
				return option.Value, nil
			}
		}

		fmt.Fprintln(s.Out, "Please select one of the numbered choices.")
	}
}

func (s Session) selectWithIntro(intro []string, label string, choices []string, defaultIndex int, mode selectIntroMode) (string, error) {
	if len(choices) == 0 {
		return "", fmt.Errorf("no choices are available")
	}
	if defaultIndex < 0 || defaultIndex >= len(choices) {
		defaultIndex = 0
	}

	if s.usesTerminalIO() && runtime.GOOS != "windows" && os.Getenv("TERM") != "dumb" {
		if mode == selectRendersIntro {
			return s.selectInteractiveWithIntro(intro, label, choices, defaultIndex)
		}
		help := interactiveHelpFromIntro(intro)
		return s.selectInteractive(help, label, choices, defaultIndex)
	}

	for _, line := range intro {
		if strings.TrimSpace(line) != "" {
			fmt.Fprintln(s.Out, line)
		} else {
			fmt.Fprintln(s.Out)
		}
	}
	fmt.Fprintln(s.Out, label)
	for index, choice := range choices {
		marker := " "
		if index == defaultIndex {
			marker = "*"
		}
		fmt.Fprintf(s.Out, "  %s %d. %s\n", marker, index+1, choice)
	}

	for {
		answer, err := s.Ask("Select", strconv.Itoa(defaultIndex+1))
		if err != nil {
			return "", err
		}

		index, err := strconv.Atoi(answer)
		if err == nil && index >= 1 && index <= len(choices) {
			return choices[index-1], nil
		}

		for _, choice := range choices {
			if strings.EqualFold(choice, answer) {
				return choice, nil
			}
		}

		fmt.Fprintln(s.Out, "Please select one of the numbered choices.")
	}
}

func (s Session) selectInteractive(help string, label string, choices []string, defaultIndex int) (string, error) {
	inFile := s.In.(*os.File)
	outFile := s.Out.(*os.File)
	if help == "" {
		help = defaultSelectHelp
	}
	fmt.Fprintln(s.Out)

	prompt := promptui.Select{
		Label:        label,
		Items:        choices,
		Size:         visibleChoiceCount(len(choices)),
		CursorPos:    defaultIndex,
		HideSelected: true,
		Templates: &promptui.SelectTemplates{
			Label:    "{{ . }}",
			Active:   "> {{ . | green }}",
			Inactive: "  {{ . | green }}",
			Selected: "{{ . }}",
			Help:     help,
		},
		Stdin:  inFile,
		Stdout: bellFilterWriter{out: outFile},
	}

	index, _, err := prompt.Run()
	if err != nil {
		return "", err
	}
	if index < 0 || index >= len(choices) {
		return "", fmt.Errorf("no choice was selected")
	}
	return choices[index], nil
}

func (s Session) selectInteractiveOptionsWithIntro(intro []string, label string, options []SelectOption, defaultIndex int) (string, error) {
	return s.runInteractiveSelectMenu(interactiveSelectMenu{
		itemCount:    len(options),
		defaultIndex: defaultIndex,
		render: func(screenBuffer *screenbuf.ScreenBuf, cursor int) error {
			return renderIntroSelectOptions(screenBuffer, intro, label, options, cursor)
		},
		value: func(cursor int) string {
			return options[cursor].Value
		},
	})
}

func (s Session) selectInteractiveWithIntro(intro []string, label string, choices []string, defaultIndex int) (string, error) {
	return s.runInteractiveSelectMenu(interactiveSelectMenu{
		itemCount:    len(choices),
		defaultIndex: defaultIndex,
		render: func(screenBuffer *screenbuf.ScreenBuf, cursor int) error {
			return renderIntroSelect(screenBuffer, intro, label, choices, cursor)
		},
		value: func(cursor int) string {
			return choices[cursor]
		},
	})
}

func (s Session) runInteractiveSelectMenu(menu interactiveSelectMenu) (string, error) {
	inFile := s.In.(*os.File)
	outFile := s.Out.(*os.File)
	cursor := menu.defaultIndex

	var selected bool
	config := &readline.Config{
		Stdin:          inFile,
		Stdout:         bellFilterWriter{out: outFile},
		HistoryLimit:   -1,
		UniqueEditLine: true,
	}
	if err := config.Init(); err != nil {
		return "", err
	}
	config.Stdin = readline.NewCancelableStdin(config.Stdin)

	rl, err := readline.NewEx(config)
	if err != nil {
		return "", err
	}
	defer rl.Close()

	rl.Write([]byte(hideCursor))
	defer rl.Write([]byte(showCursor))

	sb := screenbuf.New(rl)
	render := func() {
		_ = menu.render(sb, cursor)
	}

	config.SetListener(func(_ []rune, _ int, key rune) ([]rune, int, bool) {
		switch key {
		case 0:
		case promptui.KeyEnter:
			selected = true
		case promptui.KeyNext, 'j':
			if cursor < menu.itemCount-1 {
				cursor++
			}
		case promptui.KeyPrev, 'k':
			if cursor > 0 {
				cursor--
			}
		case promptui.KeyForward, 'l':
			cursor += visibleChoiceCount(menu.itemCount)
			if cursor >= menu.itemCount {
				cursor = menu.itemCount - 1
			}
		case promptui.KeyBackward, 'h':
			cursor -= visibleChoiceCount(menu.itemCount)
			if cursor < 0 {
				cursor = 0
			}
		}
		render()
		return nil, 0, true
	})

	for {
		_, err := rl.Readline()
		if err != nil {
			clearIntroSelect(sb)
			switch {
			case err == readline.ErrInterrupt, err.Error() == "Interrupt":
				return "", promptui.ErrInterrupt
			case err == io.EOF:
				return "", promptui.ErrEOF
			default:
				return "", err
			}
		}
		if selected {
			clearIntroSelect(sb)
			return menu.value(cursor), nil
		}
	}
}

func (s Session) usesTerminalIO() bool {
	inFile, inOK := s.In.(*os.File)
	outFile, outOK := s.Out.(*os.File)
	return inOK && outOK && IsTerminal(inFile) && IsTerminal(outFile)
}

func visibleChoiceCount(choiceCount int) int {
	if choiceCount > 8 {
		return 8
	}
	return choiceCount
}

func helpIntro(help string) []string {
	if strings.TrimSpace(help) == defaultSelectHelp {
		return []string{"", help, ""}
	}
	return []string{help}
}

func normalizeSelectIntro(intro []string) []string {
	normalized := make([]string, 0, len(intro)+1)
	for index, line := range intro {
		if strings.TrimSpace(line) == defaultSelectHelp && (len(normalized) == 0 || strings.TrimSpace(normalized[len(normalized)-1]) != "") {
			normalized = append(normalized, "")
		}
		normalized = append(normalized, line)
		if strings.TrimSpace(line) == defaultSelectHelp {
			nextIsBlank := index+1 < len(intro) && strings.TrimSpace(intro[index+1]) == ""
			if !nextIsBlank {
				normalized = append(normalized, "")
			}
		}
	}
	return normalized
}

func interactiveHelpFromIntro(intro []string) string {
	for index, line := range intro {
		if strings.TrimSpace(line) == "" {
			continue
		}
		help := line
		if index > 0 && strings.TrimSpace(intro[index-1]) == "" {
			help = "\n" + help
		}
		if index+1 < len(intro) && strings.TrimSpace(intro[index+1]) == "" {
			help += "\n"
		}
		return help
	}
	return ""
}

func textPromptLine(label string, defaultValue string, value string, answered bool) string {
	prefix := ""
	if defaultValue != "" {
		prefix = fmt.Sprintf("%s [%s]: ", label, defaultValue)
	} else {
		prefix = fmt.Sprintf("%s: ", label)
	}
	if answered {
		prefix = yellow(prefix)
	}
	return prefix + value
}

func renderIntroSelect(sb *screenbuf.ScreenBuf, intro []string, label string, choices []string, cursor int) error {
	visibleCount := visibleChoiceCount(len(choices))
	start := introSelectWindowStart(len(choices), cursor, visibleCount)
	end := start + visibleCount
	if end > len(choices) {
		end = len(choices)
	}

	sb.Reset()
	for _, line := range intro {
		if _, err := sb.WriteString(line); err != nil {
			return err
		}
	}
	if _, err := sb.WriteString(label); err != nil {
		return err
	}
	for index := start; index < end; index++ {
		prefix := "    "
		if index == cursor {
			prefix = "  > "
		}
		if _, err := sb.WriteString(prefix + green(choices[index])); err != nil {
			return err
		}
	}
	return sb.Flush()
}

func renderIntroSelectOptions(sb *screenbuf.ScreenBuf, intro []string, label string, options []SelectOption, cursor int) error {
	visibleCount := visibleChoiceCount(len(options))
	start := introSelectWindowStart(len(options), cursor, visibleCount)
	end := start + visibleCount
	if end > len(options) {
		end = len(options)
	}
	width := selectOptionLabelWidth(options)

	sb.Reset()
	for _, line := range intro {
		if _, err := sb.WriteString(line); err != nil {
			return err
		}
	}
	if _, err := sb.WriteString(label); err != nil {
		return err
	}
	for index := start; index < end; index++ {
		prefix := "    "
		if index == cursor {
			prefix = "  > "
		}
		if _, err := sb.WriteString(prefix + formatSelectOption(options[index], width, true)); err != nil {
			return err
		}
	}
	return sb.Flush()
}

func introSelectWindowStart(choiceCount int, cursor int, visibleCount int) int {
	if visibleCount >= choiceCount {
		return 0
	}
	start := cursor - visibleCount/2
	if start < 0 {
		return 0
	}
	maxStart := choiceCount - visibleCount
	if start > maxStart {
		return maxStart
	}
	return start
}

func clearIntroSelect(sb *screenbuf.ScreenBuf) {
	_ = sb.Clear()
	_ = sb.Flush()
}

const (
	resetStyle  = "\033[0m"
	greenStart  = "\033[32m"
	yellowStart = "\033[33m"
)

func green(value string) string {
	return greenStart + value + resetStyle
}

func greenOpen(value string) string {
	return greenStart + value
}

func yellow(value string) string {
	return yellowStart + value + resetStyle
}

func faint(value string) string {
	return "\033[2m" + value + "\033[0m"
}

func normalizeSelectOptions(options []SelectOption) ([]SelectOption, error) {
	normalized := make([]SelectOption, 0, len(options))
	for _, option := range options {
		option.Label = strings.TrimSpace(option.Label)
		option.Value = strings.TrimSpace(option.Value)
		option.Description = strings.TrimSpace(option.Description)
		if option.Label == "" {
			option.Label = option.Value
		}
		if option.Value == "" {
			option.Value = option.Label
		}
		if option.Label == "" {
			continue
		}
		normalized = append(normalized, option)
	}
	if len(normalized) == 0 {
		return nil, fmt.Errorf("no choices are available")
	}
	return normalized, nil
}

func selectOptionLabelWidth(options []SelectOption) int {
	width := 0
	for _, option := range options {
		if len(option.Label) > width {
			width = len(option.Label)
		}
	}
	return width
}

func formatSelectOption(option SelectOption, labelWidth int, color bool) string {
	padding := ""
	if labelWidth > len(option.Label) {
		padding = strings.Repeat(" ", labelWidth-len(option.Label))
	}
	label := option.Label
	if color {
		label = green(label)
	}
	if option.Description == "" {
		return label
	}

	description := option.Description
	if color {
		description = faint(description)
	}
	return label + padding + "  " + description
}

type bellFilterWriter struct {
	out *os.File
}

func (w bellFilterWriter) Write(value []byte) (int, error) {
	filtered := value
	for _, b := range value {
		if b == '\a' {
			filtered = make([]byte, 0, len(value))
			for _, candidate := range value {
				if candidate != '\a' {
					filtered = append(filtered, candidate)
				}
			}
			break
		}
	}
	_, err := w.out.Write(filtered)
	if err != nil {
		return 0, err
	}
	return len(value), nil
}

func (w bellFilterWriter) Close() error {
	return nil
}
