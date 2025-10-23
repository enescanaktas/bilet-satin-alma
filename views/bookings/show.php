<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation - Bilet Satın Alma</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="confirmation-box">
            <div class="success-icon">✓</div>
            <h1>Booking Confirmed!</h1>
            
            <div class="booking-details">
                <h2>Booking Details</h2>
                <div class="detail-row">
                    <strong>Booking Code:</strong>
                    <span class="booking-code"><?= htmlspecialchars($booking['booking_code']) ?></span>
                </div>
                <div class="detail-row">
                    <strong>Passenger Name:</strong>
                    <span><?= htmlspecialchars($booking['passenger_name']) ?></span>
                </div>
                <div class="detail-row">
                    <strong>Gender:</strong>
                    <span><?= htmlspecialchars($booking['passenger_gender']) ?></span>
                </div>
                <div class="detail-row">
                    <strong>Seat Number:</strong>
                    <span><?= htmlspecialchars($booking['seat_number']) ?></span>
                </div>
                <div class="detail-row">
                    <strong>Booking Date:</strong>
                    <span><?= htmlspecialchars($booking['created_at']) ?></span>
                </div>
            </div>
            
            <div class="actions">
                <a href="/booking/<?= htmlspecialchars($booking['booking_code']) ?>/pdf" class="btn btn-primary">
                    Download PDF Ticket
                </a>
                <a href="/seats" class="btn btn-secondary">Book Another Seat</a>
            </div>
        </div>
    </div>
</body>
</html>
