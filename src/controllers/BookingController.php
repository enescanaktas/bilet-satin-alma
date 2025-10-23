<?php
namespace ECA\Controllers;

use Dompdf\Dompdf;

class BookingController {
    public function buy($trip_id=null) {
        require_once __DIR__ . '/../security.php';
        require_login();
    require_once __DIR__ . '/../db.php';
    $db = get_db();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../security.php';
            // removed temporary debug logging (cleanup) — do not log POST payloads in production
            if (!validate_csrf($_POST['csrf'] ?? '')) { $error = 'Invalid CSRF token'; }
            $trip_id = intval($_POST['trip_id'] ?? 0);
            $seat = intval($_POST['seat_number'] ?? 0);
            $coupon = trim($_POST['coupon'] ?? '');
            // basic validation
            if ($seat <= 0) { $error = 'Geçersiz koltuk numarası'; }
            // require_login ensures user is logged-in; use helper to get id
            $user_id = current_user_id();
            // begin transaction using PDO so PDO's transaction state is consistent
            $db->beginTransaction();
            try {
                $stmt = $db->prepare('SELECT * FROM trips WHERE id = ?');
                $stmt->execute([$trip_id]);
                $trip = $stmt->fetch(\PDO::FETCH_ASSOC);
                if (!$trip) { throw new \Exception('Sefer bulunamadı'); }
                if ($seat < 1 || $seat > intval($trip['total_seats'])) { throw new \Exception('Koltuk numarası sefer aralığında değil'); }
                // check seat availability
                $stmt = $db->prepare('SELECT COUNT(*) as cnt FROM bookings WHERE trip_id = ? AND seat_number = ? AND cancelled = 0');
                $stmt->execute([$trip_id, $seat]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($row['cnt'] > 0) { throw new \Exception('Koltuk dolu'); }
                $price = $trip['price'];
                $c = null;
                if ($coupon) {
                    $cstmt = $db->prepare('SELECT * FROM coupons WHERE code = ? AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)');
                    $cstmt->execute([$coupon]);
                    $c = $cstmt->fetch(\PDO::FETCH_ASSOC);
                    if ($c && ($c['usage_limit'] == 0 || $c['used_count'] < $c['usage_limit'])) {
                        $price = intval($price * (100 - $c['percent']) / 100);
                    } else { throw new \Exception('Kupon geçersiz veya limit dolu'); }
                }
                $ustmt = $db->prepare('SELECT credit FROM users WHERE id = ?');
                $ustmt->execute([$user_id]);
                $u = $ustmt->fetch(\PDO::FETCH_ASSOC);
                if ($u['credit'] < $price) { throw new \Exception('Yetersiz bakiye'); }
                $gender = in_array(strval($_POST['gender'] ?? ''), ['male','female']) ? $_POST['gender'] : '';
                $istmt = $db->prepare('INSERT INTO bookings (user_id, trip_id, seat_number, price_paid, coupon_code, passenger_gender) VALUES (?,?,?,?,?,?)');
                $istmt->execute([$user_id, $trip_id, $seat, $price, $coupon, $gender]);
                $db->prepare('UPDATE users SET credit = credit - ? WHERE id = ?')->execute([$price, $user_id]);
                if (!empty($c)) { $db->prepare('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?')->execute([$c['id']]); }
                $db->commit();
                header('Location: /mybookings'); exit;
            } catch (\Exception $e) {
                // only rollback if a transaction is active to avoid "There is no active transaction"
                if ($db->inTransaction()) { $db->rollBack(); }
                $error = $e->getMessage();
            }
        }
        // load trip for GET
        if ($trip_id) {
            require_once __DIR__ . '/../db.php';
            $db = get_db();
            $stmt = $db->prepare('SELECT * FROM trips WHERE id = ?');
            $stmt->execute([$trip_id]);
            $trip = $stmt->fetch(\PDO::FETCH_ASSOC);
            // calculate occupied seats and map booking info (user_id, passenger_gender) per seat
            $stmt = $db->prepare('SELECT seat_number, user_id, passenger_gender FROM bookings WHERE trip_id = ? AND cancelled = 0');
            $stmt->execute([$trip_id]);
            $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $occupied = array_column($rows,'seat_number');
            $bookings_map = [];
            foreach ($rows as $r) {
                $bookings_map[(int)$r['seat_number']] = ['user_id' => $r['user_id'], 'gender' => $r['passenger_gender'] ?? ''];
            }
        }
        require __DIR__ . '/../../views/bookings/buy.php';
    }



    // Quick AJAX JSON buy: expects POST JSON or form with trip_id, seat_number, csrf
    public function quickBuy() {
        header('Content-Type: application/json');
        require_once __DIR__ . '/../security.php';
        require_login();
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { echo json_encode(['ok'=>false,'error'=>'method']); exit; }
        $trip_id = intval($_POST['trip_id'] ?? ($_GET['trip_id'] ?? 0));
        $seat = intval($_POST['seat_number'] ?? ($_GET['seat_number'] ?? 0));
        if (!validate_csrf($_POST['csrf'] ?? '')) { echo json_encode(['ok'=>false,'error'=>'csrf']); exit; }
        require_once __DIR__ . '/../db.php'; $db = get_db();
        $user_id = current_user_id();
        if ($seat < 1) { echo json_encode(['ok'=>false,'error'=>'invalid_seat']); exit; }
        try {
            // start transaction via PDO
            $db->beginTransaction();
            $stmt = $db->prepare('SELECT * FROM trips WHERE id = ?'); $stmt->execute([$trip_id]); $trip = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$trip) throw new \Exception('Sefer bulunamadı');
            if ($seat < 1 || $seat > intval($trip['total_seats'])) throw new \Exception('Invalid seat');
            $check = $db->prepare('SELECT COUNT(*) as cnt FROM bookings WHERE trip_id = ? AND seat_number = ? AND cancelled = 0'); $check->execute([$trip_id,$seat]); $row=$check->fetch(\PDO::FETCH_ASSOC);
            if ($row['cnt'] > 0) throw new \Exception('Koltuk dolu');
            $price = $trip['price'];
            $gender = in_array(strval($_POST['gender'] ?? ''), ['male','female']) ? $_POST['gender'] : '';
            $db->prepare('INSERT INTO bookings (user_id, trip_id, seat_number, price_paid, passenger_gender) VALUES (?,?,?,?,?)')->execute([$user_id,$trip_id,$seat,$price,$gender]);
            $db->prepare('UPDATE users SET credit = credit - ? WHERE id = ?')->execute([$price,$user_id]);
            $db->commit();
            echo json_encode(['ok'=>true,'seat'=>$seat,'price'=>$price]); exit;
        } catch (\Exception $e) {
            if ($db->inTransaction()) { try { $db->rollBack(); } catch (\Exception $__e) {} }
            echo json_encode(['ok'=>false,'error'=>$e->getMessage()]); exit;
        }
    }

    public function myBookings() {
    require_once __DIR__ . '/../security.php';
    require_login();
    require_once __DIR__ . '/../db.php';
    $db = get_db();
        // if firma-admin, show bookings for trips of their firm; otherwise show user's own bookings
        if (current_user_role() === 'firma-admin' && !empty($_SESSION['firm_id'])) {
            $stmt = $db->prepare('SELECT b.*, t.departure, t.arrival, t.departure_time, t.firm_id, u.username FROM bookings b JOIN trips t ON t.id = b.trip_id LEFT JOIN users u ON u.id = b.user_id WHERE t.firm_id = ?');
            $stmt->execute([$_SESSION['firm_id']]);
        } else {
            $stmt = $db->prepare('SELECT b.*, t.departure, t.arrival, t.departure_time, u.username FROM bookings b JOIN trips t ON t.id = b.trip_id LEFT JOIN users u ON u.id = b.user_id WHERE b.user_id = ?');
            $stmt->execute([$_SESSION['user_id']]);
        }
        $bookings = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        require __DIR__ . '/../../views/bookings/mybookings.php';
    }

    public function cancel($booking_id) {
    require_once __DIR__ . '/../db.php';
    $db = get_db();
        // allow POST cancel with CSRF
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_once __DIR__ . '/../security.php'; require_login();
            if (!validate_csrf($_POST['csrf'] ?? '')) { header('Location: /mybookings'); exit; }
            $booking_id = intval($_POST['id'] ?? $booking_id);
        }
        $stmt = $db->prepare('SELECT b.*, t.departure_time, t.firm_id FROM bookings b JOIN trips t ON t.id = b.trip_id WHERE b.id = ?');
        $stmt->execute([$booking_id]);
        $b = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$b) { header('Location: /mybookings'); exit; }
        require_once __DIR__ . '/../security.php';
        require_login();
        // only owner or admin can cancel
        $uid = current_user_id();
        $role = current_user_role();
        // allow firma-admin to cancel bookings for their firm's trips
        if ($uid != $b['user_id'] && $role !== 'admin' && !($role === 'firma-admin' && !empty($_SESSION['firm_id']) && $_SESSION['firm_id'] == ($b['firm_id'] ?? null))) { header('Location: /mybookings'); exit; }
        $departure = strtotime($b['departure_time']);
        if ($departure - time() < 3600) { $error = 'Sefere 1 saatten az kaldığı için iptal yapılamaz'; header('Location: /mybookings'); exit; }
        // transactionally cancel and refund
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE bookings SET cancelled = 1, cancelled_at = CURRENT_TIMESTAMP WHERE id = ?')->execute([$booking_id]);
            $db->prepare('UPDATE users SET credit = credit + ? WHERE id = ?')->execute([$b['price_paid'], $b['user_id']]);
            audit_log('booking.cancel', ['booking_id'=>$booking_id,'refund'=>$b['price_paid']]);
            $db->commit();
        } catch (\Exception $e) {
            $db->rollBack();
        }
        header('Location: /mybookings'); exit;
    }

    // PDF generation endpoint (simple)
    public function ticketPdf($booking_id) {
        require_once __DIR__ . '/../db.php';
        $db = get_db();
        $stmt = $db->prepare('SELECT b.*, t.departure, t.arrival, t.departure_time, t.firm_id FROM bookings b JOIN trips t ON t.id = b.trip_id WHERE b.id = ?');
        $stmt->execute([$booking_id]);
        $b = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$b) { header('Location: /mybookings'); exit; }
        // render template
        $booking = $b;
        // fetch username
        $ust = $db->prepare('SELECT username FROM users WHERE id = ?'); $ust->execute([$booking['user_id']]); $urow = $ust->fetch(\PDO::FETCH_ASSOC);
        $username = $urow['username'] ?? 'Misafir';
        require_once __DIR__ . '/../security.php';
        // only owner, admin, or firma-admin of firm can view PDF
        if (!isset($_SESSION['user_id'])) { /* allow Dompdf stream to handle redirect */ }
        $role = current_user_role();
        $uid = current_user_id();
        if ($uid != $booking['user_id'] && $role !== 'admin' && !($role === 'firma-admin' && !empty($_SESSION['firm_id']) && $_SESSION['firm_id'] == ($booking['firm_id'] ?? null))) { header('Location: /mybookings'); exit; }
        ob_start();
        require __DIR__ . '/../../views/bookings/ticket.php';
        $html = ob_get_clean();
        $dompdf = new Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4','portrait');
        $dompdf->render();
        $dompdf->stream("ticket_$booking_id.pdf", ['Attachment' => 0]);
    }
}
