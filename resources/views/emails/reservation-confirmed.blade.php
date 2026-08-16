<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Booking confirmed</title>
</head>
<body style="font-family: Arial, sans-serif; color: #1b1b18;">
    <h1>Your booking is confirmed</h1>

    <p>Hi {{ $reservation->user->name }},</p>

    <p>
        Thank you for booking with BookMyHotel. Your reservation
        <strong>{{ $reservation->reference }}</strong> at
        <strong>{{ $reservation->hotel->name }}</strong> is confirmed.
    </p>

    <ul>
        <li>Check-in: {{ $reservation->check_in->toFormattedDateString() }}</li>
        <li>Check-out: {{ $reservation->check_out->toFormattedDateString() }}</li>
        <li>Guests: {{ $reservation->guests }}</li>
        <li>Total paid: {{ number_format((float) $reservation->total_amount, 2) }} AED</li>
    </ul>

    <p>We look forward to welcoming you.</p>
</body>
</html>
