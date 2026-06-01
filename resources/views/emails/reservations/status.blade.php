<x-mail::message>
# Reservation {{ $statusLabel }}

Hello {{ $reservation->guest_name }},

{{ $statusMessage }}

<x-mail::panel>
**Reference Code:** {{ $reservation->reference_code }}

**Boarding House:** {{ $boardingHouse?->name ?? 'Boarding house not available' }}

**Status:** {{ $statusLabel }}

**Preferred Move-in Date:** {{ optional($reservation->preferred_move_in_date)->format('M d, Y') }}
</x-mail::panel>

@if ($reservation->owner_response)
**Owner Response:**

{{ $reservation->owner_response }}
@endif

Please keep your reference code. You can use it together with your email address to track your reservation status.

<x-mail::button :url="url('/track-reservation')">
Track Reservation
</x-mail::button>

Thanks,  
{{ config('app.name', 'E-BoardMate') }}
</x-mail::message>