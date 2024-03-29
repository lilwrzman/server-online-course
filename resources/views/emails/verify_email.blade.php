<x-mail::message>
Hello, {{$fullname}}!

Ready to join our Online Course?

<x-mail::button :url="$verificationUrl">
Verify Now!
</x-mail::button>

Thanks, {{$fullname}}<br>
{{ config('app.name') }}
</x-mail::message>
