<x-mail::message>
# You're invited to join {{ $organization->name }}

You have been invited to collaborate on **{{ $organization->name }}** in Ghostable.

<x-mail::button :url="$inviteUrl">
Accept Invitation
</x-mail::button>

This invitation was sent to {{ $email }} and will expire in {{ config('platform.invite.expiration_days') }} days.

If you don't want to join, you can ignore this email.

Thanks,<br>
The Ghostable Organization
</x-mail::message>
