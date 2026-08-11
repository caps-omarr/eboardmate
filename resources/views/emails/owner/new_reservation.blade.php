<x-mail::message>
# Hello {{ $boardingHouse->owner->name }},

You have received a new reservation request for **{{ $boardingHouse->name }}**.

### Reservation Details:
* **Reference Code:** {{ $reservation->reference_code }}
* **Guest Name:** {{ $reservation->guest_name }}
* **Guest Phone:** {{ $reservation->guest_phone }}
* **Guest Email:** {{ $reservation->guest_email }}
* **Preferred Move-in Date:** {{ \Carbon\Carbon::parse($reservation->preferred_move_in_date)->format('F j, Y') }}

### Message from Guest:
> {{ $reservation->message ?? 'No additional message provided.' }}

Please log in to your E-BoardMate dashboard to review, approve, or reject this request.

<x-mail::button :url="route('owner.login')">
Log In to Dashboard
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>