<?php

namespace App\Controllers;

use App\Session;
use App\CSRF;
use App\Models\Seat;
use App\Models\Booking;
use Dompdf\Dompdf;
use Dompdf\Options;

class BookingController
{
    public function create(): void
    {
        if (!Session::has('user_id')) {
            header('Location: /login');
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /seats');
            exit;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        
        if (!CSRF::validateToken($csrfToken)) {
            Session::set('error', 'Invalid CSRF token');
            header('Location: /seats');
            exit;
        }

        $seatId = $_POST['seat_id'] ?? null;
        $passengerName = $_POST['passenger_name'] ?? '';
        $passengerGender = $_POST['passenger_gender'] ?? '';

        if (!$seatId || !$passengerName || !$passengerGender) {
            Session::set('error', 'All fields are required');
            header('Location: /seats');
            exit;
        }

        $seatModel = new Seat();
        $seat = $seatModel->findById($seatId);

        if (!$seat || !$seat['is_available']) {
            Session::set('error', 'Selected seat is not available');
            header('Location: /seats');
            exit;
        }

        $bookingModel = new Booking();
        $userId = Session::get('user_id');
        $bookingCode = $bookingModel->create($seatId, $passengerName, $passengerGender, $userId);
        
        $seatModel->markAsBooked($seatId);

        header('Location: /booking/' . $bookingCode);
        exit;
    }

    public function show(string $code): void
    {
        if (!Session::has('user_id')) {
            header('Location: /login');
            exit;
        }

        $bookingModel = new Booking();
        $booking = $bookingModel->findByCode($code);

        if (!$booking) {
            http_response_code(404);
            echo "Booking not found";
            exit;
        }

        require __DIR__ . '/../../views/bookings/show.php';
    }

    public function generatePDF(string $code): void
    {
        if (!Session::has('user_id')) {
            header('Location: /login');
            exit;
        }

        $bookingModel = new Booking();
        $booking = $bookingModel->findByCode($code);

        if (!$booking) {
            http_response_code(404);
            echo "Booking not found";
            exit;
        }

        // Generate PDF
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        
        $dompdf = new Dompdf($options);
        
        $html = $this->generateTicketHTML($booking);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $dompdf->stream('ticket_' . $code . '.pdf', ['Attachment' => true]);
    }

    private function generateTicketHTML(array $booking): string
    {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; }
        .ticket { border: 2px solid #333; padding: 30px; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #333; }
        .info { margin: 20px 0; }
        .info-row { margin: 10px 0; font-size: 14px; }
        .info-row strong { display: inline-block; width: 150px; }
        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
        .barcode { text-align: center; margin: 20px 0; font-size: 24px; font-weight: bold; letter-spacing: 3px; }
    </style>
</head>
<body>
    <div class="ticket">
        <div class="header">
            <h1>Bilet Satın Alma</h1>
            <p>Siber Vatan Ticket Service</p>
        </div>
        <div class="info">
            <div class="info-row"><strong>Booking Code:</strong> ' . htmlspecialchars($booking['booking_code']) . '</div>
            <div class="info-row"><strong>Passenger Name:</strong> ' . htmlspecialchars($booking['passenger_name']) . '</div>
            <div class="info-row"><strong>Gender:</strong> ' . htmlspecialchars($booking['passenger_gender']) . '</div>
            <div class="info-row"><strong>Seat Number:</strong> ' . htmlspecialchars($booking['seat_number']) . '</div>
            <div class="info-row"><strong>Booking Date:</strong> ' . htmlspecialchars($booking['created_at']) . '</div>
        </div>
        <div class="barcode">
            ' . htmlspecialchars($booking['booking_code']) . '
        </div>
        <div class="footer">
            <p>Please present this ticket at the entrance</p>
            <p>Thank you for choosing Siber Vatan!</p>
        </div>
    </div>
</body>
</html>';
    }
}
