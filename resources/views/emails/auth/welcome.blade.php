<x-mail::message>
# Welcome to {{ config('app.name') }}!

Hi {{ $notifiable->firstname }},

We're thrilled to welcome you to our community! Your account has been successfully created, and we're excited to have you on board.

<x-mail::panel>
Here's what you can do next:
* Visit your dashboard to get started
* Complete your profile information
* Explore our exclusive features and services
</x-mail::panel>

@if(!$notifiable->hasVerifiedEmail())
<x-mail::button :url="$verificationUrl" style="background-color: #007BFF; color: white; padding: 10px 20px; border-radius: 5px; text-decoration: none;">
Verify Email Address
</x-mail::button>
@endif

If you have any questions or need assistance, feel free to reach out to our support team at <a href="mailto:support@{{ config('app.domain') }}">support@{{ config('app.domain') }}</a>.

Thanks,<br>
The {{ config('app.name') }} Team

<x-slot:footer>
<p style="font-size: 12px; color: #6c757d; text-align: center;">
    {{ $theme['footer'] ?? ('© ' . date('Y') . ' ' . config('app.name') . '. All rights reserved.') }}
</p>
</x-slot:footer>
</x-mail::message>