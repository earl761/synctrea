<x-mail::message>
# Welcome to {{ config('app.name') }}!

Hi {{ $notifiable->firstname }},

We're excited to have you as a member of our community. Your account has been successfully created.

<x-mail::panel>
Here's what you can do next:
* Visit your dashboard to get started
* Complete your profile information
* Explore our features and services
</x-mail::panel>

@if(!$notifiable->hasVerifiedEmail())
Please make sure to verify your email address by clicking the verification link we sent in a separate email.
@endif

If you have any questions or need assistance, feel free to reach out to our support team.

Thanks,<br>
{{ config('app.name') }}

<x-slot:footer>
{{ $theme['footer'] ?? ('© ' . date('Y') . ' ' . config('app.name') . '. All rights reserved.') }}
</x-slot:footer>
</x-mail::message>