<h1>New {{ ucfirst($submission->inquiry_type) }} inquiry</h1>
<p><strong>Name:</strong> {{ $submission->name }}</p>
<p><strong>Email:</strong> {{ $submission->email }}</p>
@if ($submission->phone)<p><strong>Phone:</strong> {{ $submission->phone }}</p>@endif
@if ($submission->organization)<p><strong>Organization:</strong> {{ $submission->organization }}</p>@endif
<p><strong>Message:</strong></p>
<p>{{ $submission->message }}</p>
