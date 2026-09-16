<h1>Your AMSET membership renews soon</h1>
<p>Dear {{ $member->fullName() }},</p>
<p>Your AMSET membership ({{ $member->membership_number }}) is set to expire on {{ $member->expires_at?->toFormattedDateString() }}.</p>
<p>Please visit the membership page on amsetweb.net to renew and continue enjoying member benefits.</p>
