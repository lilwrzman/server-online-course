<x-mail::message>
Hello, {{$fullname}}!

Thanks for verify your email. <br>
Ready to begin?

<x-mail::button :url="$loginUrl">
Login Now!
</x-mail::button>

Thanks, {{$fullname}}<br>
{{ config('app.name') }}
</x-mail::message>
