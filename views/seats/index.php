<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Seat - Bilet Satın Alma</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Select Your Seat</h1>
            <div class="user-info">
                <span>Welcome, <?= htmlspecialchars($username) ?></span>
                <a href="/logout" class="btn btn-secondary">Logout</a>
            </div>
        </div>
        
        <?php if (App\Session::has('error')): ?>
            <div class="error-message">
                <?= htmlspecialchars(App\Session::get('error')) ?>
                <?php App\Session::delete('error'); ?>
            </div>
        <?php endif; ?>
        
        <div class="legend">
            <div class="legend-item">
                <span class="legend-color available"></span>
                <span>Available</span>
            </div>
            <div class="legend-item">
                <span class="legend-color male"></span>
                <span>Male Passenger</span>
            </div>
            <div class="legend-item">
                <span class="legend-color female"></span>
                <span>Female Passenger</span>
            </div>
        </div>
        
        <form method="POST" action="/booking/create" id="bookingForm">
            <?= App\CSRF::field() ?>
            
            <div class="seats-container">
                <?php 
                $rows = ['A', 'B', 'C'];
                foreach ($rows as $row):
                    echo '<div class="seat-row">';
                    echo '<div class="row-label">' . $row . '</div>';
                    
                    foreach ($seats as $seat):
                        if (substr($seat['seat_number'], 0, 1) !== $row) continue;
                        
                        $isAvailable = $seat['is_available'];
                        $gender = $seat['passenger_gender'] ?? null;
                        
                        $class = 'seat';
                        if (!$isAvailable) {
                            $class .= ' booked';
                            if ($gender === 'male') {
                                $class .= ' male';
                            } elseif ($gender === 'female') {
                                $class .= ' female';
                            }
                        }
                        
                        echo '<div class="' . $class . '">';
                        if ($isAvailable):
                ?>
                            <input type="radio" 
                                   id="seat_<?= $seat['id'] ?>" 
                                   name="seat_id" 
                                   value="<?= $seat['id'] ?>" 
                                   required>
                            <label for="seat_<?= $seat['id'] ?>">
                                <?= htmlspecialchars($seat['seat_number']) ?>
                            </label>
                <?php else: ?>
                            <span class="seat-unavailable">
                                <?= htmlspecialchars($seat['seat_number']) ?>
                            </span>
                <?php 
                        endif;
                        echo '</div>';
                    endforeach;
                    echo '</div>';
                endforeach;
                ?>
            </div>
            
            <div class="booking-form">
                <h2>Passenger Information</h2>
                
                <div class="form-group">
                    <label for="passenger_name">Passenger Name</label>
                    <input type="text" id="passenger_name" name="passenger_name" required>
                </div>
                
                <div class="form-group">
                    <label for="passenger_gender">Gender</label>
                    <select id="passenger_gender" name="passenger_gender" required>
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Book Seat</button>
            </div>
        </form>
    </div>
</body>
</html>
