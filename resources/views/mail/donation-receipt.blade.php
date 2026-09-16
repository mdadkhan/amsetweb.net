<h1>Thank you for supporting AMSET</h1>
<p><strong>Receipt number:</strong> {{ $donation->receipt_number }}</p>
<p><strong>Donor:</strong> {{ $donation->donor_name }}</p>
<p><strong>Amount:</strong> ${{ number_format((float) $donation->amount, 2) }} ({{ str_replace('_', ' ', $donation->frequency) }})</p>
@if ($donation->campaign)<p><strong>Campaign:</strong> {{ $donation->campaign->title }}</p>@endif
<p>This receipt confirms your donation to AMSET. Please retain it for your records.</p>
