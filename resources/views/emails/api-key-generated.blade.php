<x-mail::message>
Hello {{ $userName }}!

Your new Api Key is {{ $apiKey }}.

Please use this key to authenticate to our API and perform requests.

Thanks,<br>
The {{ config('app.name') }} Team
</x-mail::message>
