<div class="mx-auto py-12 px-10 max-w-3xl dark">
    <flux:accordion>
        
        <flux:accordion.item>
            <flux:accordion.heading>Is Ghostable secure?</flux:accordion.heading>
            <flux:accordion.content>
                Yes. Ghostable uses a <strong>Zero-Knowledge</strong> architecture — all environment data is encrypted locally
                before it ever leaves your machine. Only ciphertext is stored on Ghostable’s servers, and your encryption
                keys never leave your device. This means it is <em>mathematically impossible</em> for Ghostable to decrypt
                your data, even if we wanted to.
            </flux:accordion.content>
        </flux:accordion.item>

        <flux:accordion.item>
            <flux:accordion.heading>How do I share environment files with my team?</flux:accordion.heading>
            <flux:accordion.content>
                Ghostable makes sharing environment files effortless and secure.  
                When you push an update with the CLI, it’s encrypted locally using your team’s master seed key before leaving your machine.
                Teammates with access to the same team key can pull and decrypt it locally—no plaintext ever touches Ghostable’s servers.
                Everyone always gets the latest version automatically, without emailing .env files or dropping them in Slack.
            </flux:accordion.content>
        </flux:accordion.item>

        <flux:accordion.item>
            <flux:accordion.heading>What kind of validation does Ghostable support?</flux:accordion.heading>
            <flux:accordion.content>
                Validation now runs entirely in your local CLI — nothing ever leaves your machine.  
                You can define simple rules for required keys, allowed values, and formats like URLs, emails, or booleans.  
                The CLI checks your environment against these rules before a push or deploy, helping you catch issues early while keeping Ghostable fully zero-knowledge.
            </flux:accordion.content>
        </flux:accordion.item>

        <flux:accordion.item>
            <flux:accordion.heading>Can I enforce rules across all environments?</flux:accordion.heading>
            <flux:accordion.content>
                Yes. You can define rules at the project level and apply them across all linked environments. Ghostable ensures consistency for staging, production, and beyond.
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
                Access is scoped by organization and project roles. You control who can read, write, or validate environments using fine-grained permissions for each collaborator.
            </flux:accordion.content>
          </flux:accordion.item>

        <flux:accordion.item>
            <flux:accordion.heading>Does Ghostable integrate with CI/CD?</flux:accordion.heading>
            <flux:accordion.content>
                Yes. You can run validations as part of your CI pipeline to ensure environments meet your rules before deploying. CLI and API integrations are available.
            </flux:accordion.content>
        </flux:accordion.item>
        
    </flux:accordion>
    
    
</div>