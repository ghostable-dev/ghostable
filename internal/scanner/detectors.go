package scanner

import "regexp"

type detectorDefinition struct {
	kind       string
	confidence string
	pattern    string
}

type compiledDetector struct {
	kind       string
	confidence string
	expr       *regexp.Regexp
}

// Detector definitions are kept in scan order so adding coverage is one focused edit.
var detectorDefinitions = []detectorDefinition{
	{kind: "AWS access key", confidence: "high", pattern: `\b(A3T[A-Z0-9]|AKIA|ASIA)[A-Z0-9]{16}\b`},
	{kind: "Anthropic API key", confidence: "high", pattern: `\bsk-ant-[A-Za-z0-9_-]{20,}\b`},
	{kind: "OpenRouter API key", confidence: "high", pattern: `\bsk-or-v1-[A-Za-z0-9_-]{20,}\b`},
	{kind: "OpenAI API key", confidence: "high", pattern: `\bsk-(?:proj-)?[A-Za-z0-9_-]{32,}\b`},
	{kind: "Google API key", confidence: "high", pattern: `\bAIza[0-9A-Za-z_-]{35}\b`},
	{kind: "Google AI auth key", confidence: "high", pattern: `\bAQ\.[0-9A-Za-z_-]{20,}\b`},
	{kind: "Hugging Face token", confidence: "high", pattern: `\bhf_[A-Za-z0-9]{30,}\b`},
	{kind: "Groq API key", confidence: "high", pattern: `\bgsk_[A-Za-z0-9]{32,}\b`},
	{kind: "Perplexity API key", confidence: "high", pattern: `\bpplx-[A-Za-z0-9]{32,}\b`},
	{kind: "Replicate API token", confidence: "high", pattern: `\br8_[A-Za-z0-9]{37}\b`},
	{kind: "xAI API key", confidence: "high", pattern: `\bxai-[A-Za-z0-9_-]{20,}\b`},
	{kind: "Cerebras API key", confidence: "high", pattern: `\bcsk[-_][A-Za-z0-9_-]{20,}\b`},
	{kind: "GitHub token", confidence: "high", pattern: `\bgh[pousr]_[A-Za-z0-9_]{30,}\b`},
	{kind: "Slack token", confidence: "high", pattern: `\bxox[baprs]-[A-Za-z0-9-]{20,}\b`},
	{kind: "Stripe live secret", confidence: "high", pattern: `\bsk_live_[A-Za-z0-9]{20,}\b`},
	{kind: "Private key", confidence: "high", pattern: `-----BEGIN (?:RSA |DSA |EC |OPENSSH |PGP )?PRIVATE KEY-----`},
	{kind: "JWT", confidence: "medium", pattern: `\beyJ[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\.[A-Za-z0-9_-]{10,}\b`},
}

var detectors = compileDetectors(detectorDefinitions)

func compileDetectors(definitions []detectorDefinition) []compiledDetector {
	detectors := make([]compiledDetector, 0, len(definitions))
	for _, definition := range definitions {
		detectors = append(detectors, compiledDetector{
			kind:       definition.kind,
			confidence: definition.confidence,
			expr:       regexp.MustCompile(definition.pattern),
		})
	}
	return detectors
}
