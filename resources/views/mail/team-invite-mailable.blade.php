<x-mail::message>
# You're invited to join {{ $team->name }}

{{ $team->owner->name }} has invited you to collaborate on their team in Ghostable.

<x-mail::button url='{{ url("/invite/{$invite->token}") }}'>
Accept Invitation
</x-mail::button>

This invitation was sent to {{ $invite->email }} and will expire in 7 days.

If you don’t want to join, you can ignore this message.

Thanks,<br>
The Ghostable Team
</x-mail::message>
