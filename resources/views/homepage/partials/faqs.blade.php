<div class="mx-auto py-12 px-10 max-w-3xl dark">
    <flux:accordion>
        
        <flux:accordion.item>
            <flux:accordion.heading>Is Ghostable secure?</flux:accordion.heading>
            <flux:accordion.content>
                Yes. All environment data is encrypted using AES-256-GCM — an industry-standard cipher trusted by governments and cloud providers. Secrets are protected in transit and at rest, and access is controlled with fine-grained permissions and full audit trails.
            </flux:accordion.content>
        </flux:accordion.item>

        <flux:accordion.item>
            <flux:accordion.heading>How do I share environment files with my team?</flux:accordion.heading>
            <flux:accordion.content>
                Ghostable lets you push environment files to your team or organization using our CLI. Teammates can pull the latest version based on their project permissions—no email, Slack threads, or Notion links required.
            </flux:accordion.content>
        </flux:accordion.item>

        <flux:accordion.item>
            <flux:accordion.heading>What kind of validation does Ghostable support?</flux:accordion.heading>
            <flux:accordion.content>
                You can define validation rules for required keys, allowed values, formats (like valid URLs or booleans), and custom constraints. Ghostable validates every environment before deploy or merge to catch missing or misconfigured variables early.
            </flux:accordion.content>
        </flux:accordion.item>

        <flux:accordion.item>
            <flux:accordion.heading>Can I enforce rules across all environments?</flux:accordion.heading>
            <flux:accordion.content>
                Yes. You can define rules at the team or project level and apply them across all linked environments. Ghostable ensures consistency for staging, production, and beyond.
            </flux:accordion.content>
        </flux:accordion.item>

        <flux:accordion.item>
            <flux:accordion.heading>Do you support version history?</flux:accordion.heading>
            <flux:accordion.content>
                Absolutely. Every change is versioned automatically, so you can review who changed what—and when. You can also roll back to a previous version at any time.
            </flux:accordion.content>
        </flux:accordion.item>

        <flux:accordion.item>
            <flux:accordion.heading>How is access managed?</flux:accordion.heading>
            <flux:accordion.content>
                Access is scoped by team and project roles. You control who can read, write, or validate environments using fine-grained permissions for each collaborator.
            </flux:accordion.content>
          </flux:accordion.item>

        <flux:accordion.item>
            <flux:accordion.heading>Does Ghostable integrate with CI/CD?</flux:accordion.heading>
            <flux:accordion.content>
                Yes. You can run validations as part of your CI pipeline to ensure environments meet your rules before deploying. CLI and API integrations are available.
            </flux:accordion.content>
        </flux:accordion.item>

        <flux:accordion.item>
            <flux:accordion.heading>What happens if a validation fails?</flux:accordion.heading>
            <flux:accordion.content>
                If an environment fails validation, it won’t be pushed until the issues are resolved. Ghostable provides detailed feedback so you can fix it quickly and confidently.
            </flux:accordion.content>
        </flux:accordion.item>
        
    </flux:accordion>
</div>