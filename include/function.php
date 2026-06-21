<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

// FORGOT PASSWORD
// send email to users for OTP code
function sendEmail($to, $subject, $message) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'arynjee@gmail.com'; 
        $mail->Password = 'vipz fbir pvpf gkgp'; 
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;

        $mail->setFrom('arynjee@gmail.com', 'Root Flower');
        $mail->addAddress($to);

        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->send();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// NOTIFICATION
// create notification table
if (!function_exists('createNotification')) {
function createNotification($email, $title, $message, $type, $action) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "RootFlower";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    $sql = "INSERT INTO notification_table (email, title, message, type, action) VALUES ('$email', '$title', '$message', '$type', '$action')";
    
    $conn->query($sql);
    $conn->close();
}

function getUserNotifications($email) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "RootFlower";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    $sql = "SELECT * FROM notification_table WHERE email = '$email' ORDER BY created_at DESC LIMIT 10";
    
    $result = $conn->query($sql);
    $notifications = [];
    
    while($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    $conn->close();
    return $notifications;
}

function getNotificationCount($email) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "RootFlower";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    $sql = "SELECT COUNT(*) as count FROM notification_table WHERE email = '$email'";
    
    $result = $conn->query($sql);
    $count = $result->fetch_assoc()['count'];
    
    $conn->close();
    return $count;
}

function createAdminNotification($title, $message, $type, $action) {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "RootFlower";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    $sql = "INSERT INTO notification_table (email, title, message, type, action) VALUES ('admin@swin.edu.my', '$title', '$message', '$type', '$action')";
    
    $conn->query($sql);
    $conn->close();
}

function getAdminNotifications() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "RootFlower";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    $sql = "SELECT * FROM notification_table WHERE email = 'admin@swin.edu.my' ORDER BY created_at DESC LIMIT 10";
    
    $result = $conn->query($sql);
    $notifications = [];
    
    while($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    
    $conn->close();
    return $notifications;
}

function getAdminNotificationCount() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "RootFlower";
    
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    $sql = "SELECT COUNT(*) as count FROM notification_table WHERE email = 'admin@swin.edu.my'";
    
    $result = $conn->query($sql);
    $count = $result->fetch_assoc()['count'];
    
    $conn->close();
    return $count;
}
}

// WORKSHOP
// workshop data
$workshops = [
    'handtied-bouquet' => [
        'title' => 'Handtied Bouquet',
        'description' => 'Master the art of creating beautiful handtied bouquets in our comprehensive 2-day workshop.',
        'image' => 'images/workshop/workshop1.jpg',
        'duration' => '2 Days  |  5 Classes  |  10 Hours Total',
        'venue' => 'Root Flower Studio, BDC Kuching',
        'price' => 'RM 800',
        'template' => 'flexible_dates', 
        'schedule' => [
            'Day 1' => [
                'Spiral Handtied-Round Layers & Classic Layers',
                'Single Stalk Bouquet'
            ],
            'Day 2' => [
                'Russian Bouquet',
                'Korean Bouquet',
                'Mix Flower Bouquet'
            ]
        ],
        'dates' => [
            'August 2025' => ['2025-08-01', '2025-08-02'],
            'September 2025' => ['2025-09-01', '2025-09-02'],
            'October 2025' => ['2025-10-01', '2025-10-02'],
            'December 2025' => ['2025-12-25', '2025-12-26'],
            'January 2026' => ['2026-01-10', '2026-01-11']
        ],
        'timeslots' =>[
            'Day 1' => ['09:00-14:00', '13:00-17:00'],
            'Day 2' => ['09:00-16:00', '13:00-20:00']
        ]
    ],
    
    'florist-to-be1' => [
        'title' => 'Florist To Be 1',
        'description' => 'Learn new bouquet curating skills with our 1st version of the all-inclusive florist workshop.',
        'image' => 'images/workshop/workshop2.jpg',
        'duration' => '4 Days | 9 Classes | 18 Hours Total',
        'venue' => 'Root Flower Studio, BDC Kuching',
        'price' => 'RM 1500',
        'template' => 'flexible_dates',
        'schedule' => [
            'Day 1' => [
                'Spiral Handtied-Round Layers & Classic Layers',
                'Single Stalk Bouquet'
            ],
            'Day 2' => [
                'Russian Bouquet',
                'Korean Bouquet',
                'Mix Flower Bouquet'
            ],
            'Day 3' => [
                'Bridal Bouquet',
                'Boutineer & Centerpiece'
            ],
            'Day 4' => [
                'Flower Basket',
                'Flower Stand'
            ]
        ],
        'dates' => [
            'August 2025' => ['2025-08-05', '2025-08-06', '2025-08-07', '2025-08-08'],
            'September 2025' => ['2025-09-04', '2025-09-05', '2025-09-06', '2025-09-07'],
            'October 2025' => ['2025-10-06', '2025-10-07', '2025-10-08', '2025-10-09'],
            'December 2025' => ['2025-12-21', '2025-12-22', '2025-12-23', '2025-12-24'],
            'January 2026' => ['2026-01-12', '2026-01-13', '2026-01-14', '2026-01-15']
        ],
        'timeslots' =>[
            'Day 1' => ['08:00-12:00', '09:00-14:00', '13:00-17:00'],
            'Day 2' => ['08:00-15:00', '10:00-17:00', '13:00-20:00'],
            'Day 3' => ['08:00-12:00', '09:00-14:00', '13:00-17:00'],
            'Day 4' => ['08:00-12:00', '09:00-14:00', '13:00-17:00']
        ]
    ],
    
    'florist-to-be2' => [
        'title' => 'Florist To Be 2',
        'description' => 'Learn new bouquet curating skills with our 2nd version of the all-inclusive florist workshop.',
        'image' => 'images/workshop/workshop3.jpg',
        'duration' => '4 Days  |  9 Classes  |  18 Hours Total',
        'venue' => 'Root Flower Studio, BDC Kuching',
        'price' => 'RM 1500',
        'template' => 'flexible_dates',
        'schedule' => [
            'Day 1' => [
                'Korean Bouquet',
                'Spiral Handtied-Classic Layers'
            ],
            'Day 2' => [
                'Russian Bouquet',
                'Flower Box',
                'Mix Flower Bouquet'
            ],
            'Day 3' => [
                'Natural Flower Bouquet',
                'Bridal Bouquet'
            ],
            'Day 4' => [
                'Boutineer',
                'Flower Basket'
            ]
        ],
        'dates' => [
            'August 2025' => ['2025-08-13', '2025-08-14', '2025-08-15', '2025-08-16'],
            'September 2025' => ['2025-09-15', '2025-09-16', '2025-09-17', '2025-09-18'],
            'October 2025' => ['2025-10-13', '2025-10-14', '2025-10-15', '2025-10-16'],
            'December 2025' => ['2025-12-27', '2025-12-28', '2025-12-29', '2025-12-30'],
            'January 2026' => ['2026-01-16', '2026-01-17', '2026-01-18', '2026-01-19']
        ],
        'timeslots' =>[
            'Day 1' => ['08:00-12:00', '09:00-14:00', '13:00-17:00'],
            'Day 2' => ['08:00-15:00', '08:00-12:00', '13:00-20:00'],
            'Day 3' => ['08:00-12:00', '09:00-14:00', '13:00-17:00'],
            'Day 4' => ['08:00-12:00', '09:00-14:00', '13:00-17:00'],
        ]
    ],
    
    'hobby-class' => [
        'title' => 'Hobby Class',
        'description' => 'Adopt a new skill taught by our professional florists via our chill and relaxing hobby class.',
        'image' => 'images/workshop/workshop4.jpg',
        'duration' => '1 Day  |  2 Classes  |  4 Hours Total',
        'venue' => 'Trevi Cafe, Saradise Kuching',
        'price' => 'RM 200',
        'template' => 'fixed_dates', 
        'fixed_sessions' => [
            '30 August 2025' => [
                'dates' => ['2025-08-30'], 
                'sessions' => ['Mix Flowers Bouquet', 'Flowers Basket']
            ],
            '13 September 2025' => [
                'dates' => ['2025-09-13'],
                'sessions' => ['Mix Flowers Bouquet', 'Centerpiece']
            ],
            '18 October 2025' => [
                'dates' => ['2025-10-18'],
                'sessions' => ['Mix Flowers Bouquet', 'Flowers Box']
            ],
            '19 December 2025' => [
                'dates' => ['2025-12-19'],
                'sessions' => ['Mix Flowers Bouquet', 'Flower Stand']
            ],  
            '20 January 2026' => [
                'dates' => ['2026-01-20'],
                'sessions' => ['Mix Flowers Bouquet', 'Flowers Basket']
            ]
        ],
        'timeslots' =>[
            'Single Day'=>['08:00-12:00', '09:00-14:00', '13:00-17:00']
        ]
    ]
];

// get workshop type
function getWorkshopDurationType($workshop) {
    if ($workshop['template'] === 'fixed_dates'){
        return 'single';
    }

    $dayCount = count($workshop['schedule']);
    switch($dayCount){
        case 1: 
            return 'single';
        case 2: 
            return 'two_day';
        case 4: 
            return 'four_day';
        default: 
            return 'single';
    }
}

// flexible dates template
function renderFlexibleDatesTemplate($workshop, $workshopKey, $position) {
    $isLeft = $position % 2 == 0; 
    
    ob_start();
    ?>
    <div class="workshop-section <?php echo $isLeft ? 'workshop-left' : 'workshop-right'; ?> mb-5">
        <div class="container-fluid">
            <div class="row align-items-center g-4 g-lg-5">
                <!-- image -->
                <div class="col-lg-6 <?php echo $isLeft ? '' : 'order-lg-2'; ?>">
                    <div class="workshop-image">
                        <img src="<?php echo $workshop['image']; ?>" alt="<?php echo $workshop['title']; ?>" class="img-fluid shadow object-fit-cover">
                    </div>
                </div>
                
                <!-- content -->
                <div class="col-lg-6 <?php echo $isLeft ? '' : 'order-lg-1'; ?>">
                    <div class="content-text h-100 p-3 p-lg-4 <?php echo $isLeft ? 'text-start' : 'text-end'; ?>">
                        <h2 class="fw-bold mb-3"><?php echo $workshop['title']; ?></h2>
                        <p class="mb-4 text-muted description"><?php echo $workshop['description']; ?></p>

                        <div class="workshop-details">
                            <div class="detail-item mb-4 <?php echo $isLeft ? 'ms-3 ps-3' : 'me-3 pe-3'; ?>">
                                <h5 class="fw-semibold mb-2"><i class="bi bi-calendar-check me-2"></i> Duration</h5>
                                <p class="mb-1"><?php echo $workshop['duration']; ?></p>
                                <p class="fs-6 text-muted mb-0">Venue: <?php echo $workshop['venue']; ?></p>
                                <p class="fs-6 text-muted"><?php echo $workshop['price']; ?></p>
                            </div>

                            <div class="detail-item mb-4 <?php echo $isLeft ? 'ms-3 ps-3' : 'me-3 pe-3'; ?>">
                                <h5 class="fw-semibold mb-2"><i class="bi bi-clock me-2"></i> Class Schedule</h5>
                                <div class="schedule-days p-3 rounded-2">
                                    <?php foreach ($workshop['schedule'] as $day => $sessions): ?>
                                        <h6 class="fw-semibold"><?php echo $day; ?></h6>
                                        <ul class="list-unstyled <?php echo $isLeft ? 'ms-3' : 'me-3'; ?>">
                                            <?php foreach ($sessions as $session): ?>
                                                <li><i class="bi bi-check-circle-fill me-2"></i><?php echo $session; ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="detail-item mb-4 <?php echo $isLeft ? 'ms-3 ps-3' : 'me-3 pe-3'; ?>">
                                <h5 class="fw-semibold mb-3"><i class="bi bi-calendar-month me-2"></i> Class Dates</h5>
                                <div class="table-responsive">
                                    <table class="dates-table table table-sm text-start mb-0">
                                        <thead>
                                            <tr>
                                                <th>Month</th>
                                                <?php foreach (array_keys($workshop['schedule']) as $day): ?>
                                                    <th><?php echo $day; ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($workshop['dates'] as $month => $dates): ?>
                                                <tr>
                                                    <td class="month-cell"><?php echo $month; ?></td>
                                                    <?php foreach ($dates as $date): ?>
                                                        <td><?php echo date('D, d M', strtotime($date)); ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <a href="workshop_reg.php?workshop=<?php echo $workshopKey; ?>" class="btn btn-primary <?php echo $isLeft ? 'ms-3' : 'me-3'; ?>">Sign Up Today</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// fixed dates template
function renderFixedDatesTemplate($workshop, $workshopKey, $position) {
    $isLeft = $position % 2 == 0; 
    
    ob_start();
    ?>
    <div class="workshop-section <?php echo $isLeft ? 'workshop-left' : 'workshop-right'; ?> mb-5">
        <div class="container-fluid">
            <div class="row align-items-center g-4 g-lg-5">
                <!-- image -->
                <div class="col-lg-6 <?php echo $isLeft ? '' : 'order-lg-2'; ?>">
                    <div class="workshop-image">
                        <img src="<?php echo $workshop['image']; ?>" alt="<?php echo $workshop['title']; ?>" class="img-fluid shadow object-fit-cover">
                    </div>
                </div>
                
                <!-- content -->
                <div class="col-lg-6 <?php echo $isLeft ? '' : 'order-lg-1'; ?>">
                    <div class="content-text h-100 p-3 p-lg-4 <?php echo $isLeft ? 'text-start' : 'text-end'; ?>">
                        <h2 class="fw-bold mb-3"><?php echo $workshop['title']; ?></h2>
                        <p class="text-muted mb-4 description"><?php echo $workshop['description']; ?></p>

                        <div class="workshop-details">
                            <div class="detail-item mb-4 <?php echo $isLeft ? 'ms-3 ps-3' : 'me-3 pe-3'; ?>">
                                <h5 class="fw-semibold mb-2"><i class="bi bi-calendar-check me-2"></i> Duration</h5>
                                <p class="mb-1"><?php echo $workshop['duration']; ?></p>
                                <p class="fs-6 text-muted mb-0">Venue: <?php echo $workshop['venue']; ?></p>
                                <p class="fs-6 text-muted"><?php echo $workshop['price']; ?></p>
                            </div>
    
                            <div class="detail-item mb-4 <?php echo $isLeft ? 'ms-3 ps-3' : 'me-3 pe-3'; ?>">
                                <h5 class="fw-semibold mb-3"><i class="bi bi-calendar-month me-2"></i> Class Dates Offered</h5>
                                <div class="table-responsive">
                                    <table class="dates-table table table-sm text-start mb-0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Sessions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($workshop['fixed_sessions'] as $date => $sessionData): ?>
                                                <tr>
                                                    <td class="month-cell text-nowrap"><?php echo $date; ?></td>
                                                    <td><?php echo implode(', ', $sessionData['sessions']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <a href="workshop_reg.php?workshop=<?php echo $workshopKey; ?>" class="btn btn-primary me-3">Sign Up Today</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

function renderWorkshop($workshopKey, $position) {
    global $workshops;
    
    if (!isset($workshops[$workshopKey])){
        return '<div class="alert alert-danger">Workshop not found</div>';
    }
    
    $workshop = $workshops[$workshopKey];
    
    switch($workshop['template']){
        case 'flexible_dates':
            return renderFlexibleDatesTemplate($workshop, $workshopKey, $position);
        case 'fixed_dates':
            return renderFixedDatesTemplate($workshop, $workshopKey, $position);
        default:
            return renderFlexibleDatesTemplate($workshop, $workshopKey, $position);
    }
}

function getWorkshopImageByTitle($workshopTitle) {
    global $workshops;
    
    foreach ($workshops as $workshop) {
        if ($workshop['title'] === $workshopTitle) {
            return $workshop['image'] ?? 'images/default-workshop.jpg';
        }
    }
    return 'images/default-workshop.jpg';
}
?>
