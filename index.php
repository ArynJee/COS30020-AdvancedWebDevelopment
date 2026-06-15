<?php
session_start();

$servername = "localhost";
$username = "root";
$password = "";

try{
    $conn = new mysqli($servername, $username, $password);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "CREATE DATABASE IF NOT EXISTS RootFlower";
    if(!$conn->query($sql)){
        throw new Exception("Database creation failed: " . $conn->error);
    }

    if(!$conn->select_db("RootFlower")){
        throw new Exception("Database selection failed: " . $conn->error);
    };

    // user table
    $sql_user = "CREATE TABLE IF NOT EXISTS user_table(
        email VARCHAR(50) NOT NULL PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        dob DATE NULL,
        gender VARCHAR(6) NOT NULL,
        hometown VARCHAR(50) NOT NULL,
        profile_image VARCHAR(100) NULL
    )";

    // account table
    $sql_account = "CREATE TABLE IF NOT EXISTS account_table(
        email VARCHAR(50) NOT NULL,
        password VARCHAR(255) NOT NULL,
        type VARCHAR(5) NOT NULL,
        otp_code VARCHAR(6) NULL,
        otp_expiry DATETIME NULL,
        PRIMARY KEY (email), 
        FOREIGN KEY (email) REFERENCES user_table(email) ON DELETE CASCADE ON UPDATE CASCADE
    )";

    // flower table
    $sql_flower = "CREATE TABLE IF NOT EXISTS flower_table(
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(50) NOT NULL,
        Scientific_Name VARCHAR(50) NOT NULL,
        Common_Name VARCHAR(50) NOT NULL,
        plants_image VARCHAR(100) NULL,
        description VARCHAR(100) NULL,
        description_extracted TEXT NULL
    ) AUTO_INCREMENT=1000";

    // workshop table
    $sql_workshop = "CREATE TABLE IF NOT EXISTS workshop_table(
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(50) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        workshop_title VARCHAR(100) NOT NULL,
        workshop_schedule_type VARCHAR(20) NOT NULL,
        workshop_date VARCHAR(100),
        workshop_time VARCHAR(100),
        attendees INT NOT NULL,
        registration_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        contact_number VARCHAR(15) NOT NULL,
        status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending'
    ) AUTO_INCREMENT=1000";

    // student work table
    $sql_studentwork = "CREATE TABLE IF NOT EXISTS studentwork_table(
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(50) NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL, 
        workshop_title VARCHAR(50) NOT NULL,
        media_files TEXT NOT NULL, 
        description TEXT NULL,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'
    ) AUTO_INCREMENT=1000";

    // notification table
    $sql_notification = "CREATE TABLE IF NOT EXISTS notification_table(
        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(50) NOT NULL,
        title VARCHAR(100) NOT NULL,
        message TEXT NOT NULL,
        type ENUM('workshop', 'student_work') NOT NULL,
        action ENUM('updated', 'approved', 'rejected') NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) AUTO_INCREMENT=1000";

    // create tables
    $tables = [$sql_user, $sql_account, $sql_flower, $sql_workshop, $sql_studentwork, $sql_notification];
    foreach ($tables as $tableSql) {
        if (!$conn->query($tableSql)) {
            throw new Exception("Table creation failed: " . $conn->error);
        }
    }

    // populate user table
    $check_user = "SELECT COUNT(*) AS count FROM user_table";
    $result = $conn->query($check_user);
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $users = ["INSERT INTO user_table (email, first_name, last_name, dob, gender, hometown, profile_image) VALUES ('admin@swin.edu.my', 'Admin', 'Admin', '2000-01-01', 'Female', 'Kuching', NULL)",
        "INSERT INTO user_table (email, first_name, last_name, dob, gender, hometown, profile_image) VALUES ('arynjee@gmail.com', 'Aryn', 'Jee', '2005-06-09', 'Female', 'Kuching', 'profile_images/profilepic.jpg')",
        "INSERT INTO user_table (email, first_name, last_name, dob, gender, hometown, profile_image) VALUES ('getosuguru@gmail.com', 'Geto', 'Suguru', '2005-02-03', 'Male', 'Kuching', 'profile_images/boys.jpg')",
        "INSERT INTO user_table (email, first_name, last_name, dob, gender, hometown, profile_image) VALUES ('maxmic@gmail.com', 'Michael', 'Wong', '2005-05-03', 'Male', 'Kuching', 'profile_images/boys.jpg')",
        "INSERT INTO user_table (email, first_name, last_name, dob, gender, hometown, profile_image) VALUES ('janiceliong@gmail.com', 'Janice', 'Liong', '2005-07-01', 'Female', 'Kuching', 'profile_images/girl.png')"];
        foreach ($users as $sql) {
            $conn->query($sql);
        }
        $result->free();
    }

    // populate account table
    $check_account = "SELECT COUNT(*) AS count FROM account_table";
    $result = $conn->query($check_account);
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $account_data = [
            ['admin@swin.edu.my', 'admin', 'admin'],
            ['arynjee@gmail.com', 'Aryn569@', 'user'],
            ['getosuguru@gmail.com', 'Geto123@', 'user'],
            ['maxmic@gmail.com', 'Mic123@', 'user'],
            ['janiceliong@gmail.com', 'Janice123@', 'user']
        ];
        
        foreach ($account_data as $data) {
            $email = $data[0];
            $plain_password = $data[1];
            $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
            $type = $data[2];
            
            $sql = "INSERT INTO account_table (email, password, type, otp_code, otp_expiry) VALUES ('$email', '$hashed_password', '$type', NULL, NULL)";
            $conn->query($sql);
        }
        $result->free();
    }

    // populate flower table
    $check_flower = "SELECT COUNT(*) AS count FROM flower_table";
    $result = $conn->query($check_flower);
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $flowers = ["INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Aryn', 'Jee', 'arynjee@gmail.com', 'Rosa rubiginosa', 'Sweet Briar Rose', 'images/flower_images/rose.jpg', 'flower_description/rose.pdf', 'The Sweet Briar Rose is known for its delightful fragrance and beautiful pink blossoms. This deciduous shrub typically grows 2-3 meters tall and produces attractive red hips in autumn. It thrives in well-drained soil and full sunlight, making it perfect for cottage gardens. The flowers bloom from late spring to early summer, attracting various pollinators.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Aryn', 'Jee', 'arynjee@gmail.com', 'Tulipa gesneriana', 'Common Tulip', 'images/flower_images/tulip.jpg', 'flower_description/tulip.pdf', 'Tulips are among the most popular spring-blooming bulbs worldwide. They feature cup-shaped flowers in virtually every color except true blue. These perennial plants grow from bulbs and typically reach 10-70 cm in height. Tulips prefer cool climates and well-drained soil. They symbolize perfect love and are often associated with the Netherlands.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Aryn', 'Jee', 'arynjee@gmail.com', 'Lilium candidum', 'Madonna Lily', 'images/flower_images/lily.jpg', 'flower_description/lily.pdf', 'The Madonna Lily is renowned for its pure white, trumpet-shaped flowers and sweet fragrance. This perennial bulb grows up to 1.2 meters tall and blooms in early summer. It has historical significance in various cultures and religions. The plant prefers full sun to partial shade and well-drained soil. Madonna Lilies are often used in wedding bouquets.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Aryn', 'Jee', 'arynjee@gmail.com', 'Orchis mascula', 'Early Purple Orchid', 'images/flower_images/orchid.jpeg', 'flower_description/orchid.pdf', 'The Early Purple Orchid is a stunning wildflower found across Europe. It produces spikes of purple flowers with distinctive spotted leaves in spring. This terrestrial orchid grows 20-60 cm tall in grasslands and woodlands. The flowers have a faint honey-like scent and are pollinated by bees. Conservation efforts are important for this beautiful species.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Geto', 'Suguru', 'getosuguru@gmail.com', 'Helianthus annuus', 'Sunflower', 'images/flower_images/sunflower.jpg', 'flower_description/sunflower.pdf', 'Sunflowers are iconic annual plants known for their large, bright yellow flower heads. They can grow up to 3 meters tall and follow the sun throughout the day. The flowers produce edible seeds rich in nutrients. Sunflowers thrive in full sun and well-drained soil. They are cultivated worldwide for their oil, seeds, and ornamental value.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Geto', 'Suguru', 'getosuguru@gmail.com', 'Lavandula angustifolia', 'English Lavender', 'images/flower_images/lavender.jpg', 'flower_description/lavendar.pdf', 'English Lavender is famous for its fragrant purple flowers and silvery-green foliage. This perennial shrub grows 30-90 cm tall and blooms from late spring to early summer. The flowers are used in perfumes, sachets, and culinary applications. Lavender prefers full sun and well-drained alkaline soil. It attracts bees and butterflies to the garden.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Geto', 'Suguru', 'getosuguru@gmail.com', 'Dianthus caryophyllus', 'Carnation', 'images/flower_images/carnation.jpg', 'flower_description/carnation.pdf', 'Carnations are popular cut flowers known for their ruffled petals and clove-like scent. They come in various colors including pink, red, white, and yellow. These perennial plants grow 30-60 cm tall and bloom from spring to summer. Carnations symbolize love and fascination. They prefer full sun and well-drained neutral to alkaline soil.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Geto', 'Suguru', 'getosuguru@gmail.com', 'Narcissus poeticus', 'Poets Narcissus', 'images/flower_images/narcissus.jpg', 'flower_description/narcissus.pdf', 'The Poets Narcissus features white petals with a small red-rimmed yellow cup. This spring-blooming bulb grows 30-40 cm tall and naturalizes well in gardens. The flowers have a strong, sweet fragrance. Daffodils are deer-resistant and easy to grow in most soils. They symbolize rebirth and new beginnings in many cultures.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Janice', 'Liong', 'janiceliong@gmail.com', 'Iris germanica', 'Bearded Iris', 'images/flower_images/iris.jpg', 'flower_description/iris.pdf', 'Bearded Irises are known for their spectacular flowers with fuzzy beard-like patches on falls. They come in countless colors and patterns, blooming in late spring. These rhizomatous perennials grow 30-120 cm tall. Irises prefer full sun and well-drained soil. The fleur-de-lis symbol is derived from the iris flower.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Janice', 'Liong', 'janiceliong@gmail.com', 'Paeonia lactiflora', 'Chinese Peony', 'images/flower_images/peony.jpg', 'flower_description/peony.pdf', 'Chinese Peonies are beloved for their large, fragrant flowers in shades of pink, white, and red. These herbaceous perennials grow 60-100 cm tall and bloom in late spring. Peonies can live for decades with proper care. They prefer full sun to light shade and rich, well-drained soil. In Chinese culture, peonies symbolize wealth and honor.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Janice', 'Liong', 'janiceliong@gmail.com', 'Chrysanthemum morifolium', 'Florists Chrysanthemum', 'images/flower_images/chrysanthemum.jpg', 'flower_description/chrysanthemum.pdf', 'Chrysanthemums are popular fall-blooming flowers with diverse flower forms and colors. These perennial plants grow 30-90 cm tall and bloom from late summer to frost. Mums prefer full sun and well-drained soil. They are important in Asian cultures, symbolizing longevity and happiness. Chrysanthemums are also used in herbal teas.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Janice', 'Liong', 'janiceliong@gmail.com', 'Hibiscus rosa-sinensis', 'Chinese Hibiscus', 'images/flower_images/hibiscus.jpg', 'flower_description/hibiscus.pdf', 'Chinese Hibiscus features large, trumpet-shaped flowers in vibrant colors like red, pink, and yellow. This tropical evergreen shrub can grow 2.5-5 meters tall. It blooms throughout the year in warm climates. Hibiscus prefers full sun and moist, well-drained soil. The flowers are used to make hibiscus tea and have medicinal properties.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Janice', 'Liong', 'janiceliong@gmail.com', 'Primula vulgaris', 'Common Primrose', 'images/flower_images/primrose.jpg', 'flower_description/primrose.pdf', 'The Common Primrose is one of the first flowers to bloom in spring, bearing pale yellow flowers with darker centers. This perennial grows 10-15 cm tall in woodland settings. Primroses prefer partial shade and moist, humus-rich soil. They naturalize well and attract early pollinators. In folklore, primroses are associated with protection and love.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Janice', 'Liong', 'janiceliong@gmail.com', 'Digitalis purpurea', 'Foxglove', 'images/flower_images/foxglove.jpg', 'flower_description/foxgloves.pdf', 'Foxgloves produce tall spikes of tubular, spotted flowers in shades of purple, pink, and white. This biennial plant grows 1-2 meters tall in its second year. Foxgloves prefer partial shade and moist, well-drained soil. All parts of the plant are toxic if ingested. The plant is source of digitalis, used in heart medications.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Michael', 'Wong', 'maxmic@gmail.com', 'Myosotis scorpioides', 'Forget-me-not', 'images/flower_images/forgetmenot.jpg', 'flower_description/forgetmenot.pdf', 'Forget-me-nots feature clusters of small, sky-blue flowers with yellow centers. These perennial plants grow 15-30 cm tall and spread gradually. They bloom from spring to early summer in moist, shady locations. Forget-me-nots symbolize true love and remembrance. The plants self-seed readily and attract early-season pollinators.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Michael', 'Wong', 'maxmic@gmail.com', 'Viola tricolor', 'Wild Pansy', 'images/flower_images/pansy.jpg', 'flower_description/pansy.pdf', 'Wild Pansies display charming faces in purple, yellow, and white combinations. These annual or short-lived perennial plants grow 15-30 cm tall. They bloom from spring through summer in cool weather. Pansies prefer full sun to partial shade and moist, well-drained soil. The flowers are edible and often used as garnishes.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Michael', 'Wong', 'maxmic@gmail.com', 'Delphinium elatum', 'Alpine Delphinium', 'images/flower_images/alpine.jpg', 'flower_description/alpine.pdf', 'Alpine Delphiniums produce tall spikes of blue, purple, or white flowers. These perennial plants can reach 1-2 meters in height. They bloom in early to mid-summer and prefer full sun. Delphiniums need rich, well-drained soil and protection from strong winds. The flowers are toxic if ingested but make excellent cut flowers.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Michael', 'Wong', 'maxmic@gmail.com', 'Aquilegia vulgaris', 'European Columbine', 'images/flower_images/columbine.jpg', 'flower_description/columbine.pdf', 'European Columbines feature unique spurred flowers in various colors including blue, purple, and pink. This perennial grows 40-80 cm tall and blooms in late spring. Columbines prefer partial shade and moist, well-drained soil. The flowers attract hummingbirds and bees. Columbines self-seed readily in suitable conditions.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Michael', 'Wong', 'maxmic@gmail.com', 'Gypsophila paniculata', 'Babys Breath', 'images/flower_images/babybreath.jpg', 'flower_description/babybreath.pdf', 'Babys Breath produces clouds of tiny white flowers on branching stems. This perennial plant grows 60-90 cm tall and blooms in summer. It prefers full sun and well-drained, alkaline soil. The flowers are commonly used as filler in floral arrangements. Babys Breath symbolizes purity and innocence in flower language.')",
        "INSERT INTO flower_table (first_name, last_name, email, Scientific_Name, Common_Name, plants_image, description, description_extracted) VALUES ('Michael', 'Wong', 'maxmic@gmail.com', 'Echinacea purpurea', 'Purple Coneflower', 'images/flower_images/coneflower.jpg', 'flower_description/coneflower.pdf', 'Purple Coneflowers feature daisy-like flowers with raised central cones. This hardy perennial grows 60-120 cm tall and blooms from summer to fall. The flowers attract butterflies and bees to the garden. Echinacea prefers full sun and well-drained soil. The plant has medicinal properties and is used in herbal remedies.')"
    ];
    foreach ($flowers as $sql) {
            $conn->query($sql);
        }
        $result->free();
    }

    // populate workshop table
    $check_workshop = "SELECT COUNT(*) AS count FROM workshop_table";
    $result = $conn->query($check_workshop);
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
    $workshops = [
        "INSERT INTO workshop_table (email, first_name, last_name, workshop_title, workshop_schedule_type, workshop_date, workshop_time, attendees, registration_date, contact_number, status) VALUES ('arynjee@gmail.com', 'Aryn', 'Jee', 'Florist To Be 1', 'flexible_dates', 'August 2025 (2025-08-05, 2025-08-06, 2025-08-07, 2025-08-08)', '13:00-17:00, 08:00-15:00, 13:00-17:00, 09:00-14:00', 3, '2025-08-01 00:00:00', '0182464426', 'approved')",
        "INSERT INTO workshop_table (email, first_name, last_name, workshop_title, workshop_schedule_type, workshop_date, workshop_time, attendees, registration_date, contact_number, status) VALUES ('arynjee@gmail.com', 'Aryn', 'Jee', 'Florist To Be 2', 'flexible_dates', 'January 2026 (2026-01-16, 2026-01-17, 2026-01-18, 2026-01-19)', '08:00-12:00, 08:00-12:00, 09:00-14:00, 09:00-14:00', 3, '2025-12-01 00:00:00', '0182464426', 'pending')",
        "INSERT INTO workshop_table (email, first_name, last_name, workshop_title, workshop_schedule_type, workshop_date, workshop_time, attendees, registration_date, contact_number, status) VALUES ('getosuguru@gmail.com', 'Geto', 'Suguru', 'Florist To Be 2', 'flexible_dates', 'September 2025 (2025-09-15, 2025-09-16, 2025-09-17, 2025-09-18)', '08:00-12:00, 10:00-17:00, 08:00-12:00, 13:00-17:00', 1, '2025-08-30 00:00:00', '0123456789', 'approved')",
        "INSERT INTO workshop_table (email, first_name, last_name, workshop_title, workshop_schedule_type, workshop_date, workshop_time, attendees, registration_date, contact_number, status) VALUES ('maxmic@gmail.com', 'Michael', 'Wong', 'Handtied Bouquet', 'flexible_dates', 'September 2025 (2025-09-01, 2025-09-02)', '09:00-14:00, 13:00-20:00', 2, '2025-08-15 00:00:00', '0123456789', 'approved')",
        "INSERT INTO workshop_table (email, first_name, last_name, workshop_title, workshop_schedule_type, workshop_date, workshop_time, attendees, registration_date, contact_number, status) VALUES ('janiceliong@gmail.com', 'Janice', 'Liong', 'Hobby Class', 'fixed_dates', '20 January 2026', '08:00-12:00', 4, '2025-12-30 00:00:00', '0123456789', 'approved')",
        "INSERT INTO workshop_table (email, first_name, last_name, workshop_title, workshop_schedule_type, workshop_date, workshop_time, attendees, registration_date, contact_number, status) VALUES ('janiceliong@gmail.com', 'Janice', 'Liong', 'Handtied Bouquet', 'flexible_dates', 'December 2025 (2025-12-25, 2025-12-26)', '09:00-14:00, 13:00-20:00', 2, '2025-08-15 00:00:00', '0123456789', 'rejected')"
    ];
    foreach ($workshops as $sql) {
            $conn->query($sql);
        }
        $result->free();
    }

    // populate student work table
    $check_studentwork = "SELECT COUNT(*) AS count FROM studentwork_table";
    $result = $conn->query($check_studentwork);
    $row = $result->fetch_assoc();
    if ($row['count'] == 0) {
        $student_works = [
            "INSERT INTO studentwork_table (email, first_name, last_name, workshop_title, media_files, description, status) VALUES ('arynjee@gmail.com', 'Aryn', 'Jee', 'Florist To Be 1', '[\"images/studentworks/works-pic1.jpg\", \"images/studentworks/works-pic2.jpg\"]', 'My first floral arrangement using roses and baby breath. Learned basic color coordination.', 'approved')",
            "INSERT INTO studentwork_table (email, first_name, last_name, workshop_title, media_files, description, status) VALUES ('arynjee@gmail.com', 'Aryn', 'Jee', 'Florist To Be 1', '[\"images/studentworks/works-pic4.jpg\", \"images/studentworks/works-video4.mp4\"]', 'Another floral arrangement from the workshop. Learned basic color coordination with roses.', 'approved')",
            "INSERT INTO studentwork_table (email, first_name, last_name, workshop_title, media_files, description, status) VALUES ('getosuguru@gmail.com', 'Geto', 'Suguru', 'Florist To Be 2', '[\"images/studentworks/works-pic3.jpg\"]', 'Created this bouquet for my mother birthday. Used lilies and carnations with fern accents.', 'approved')",
            "INSERT INTO studentwork_table (email, first_name, last_name, workshop_title, media_files, description, status) VALUES ('maxmic@gmail.com', 'Michael', 'Wong', 'Handtied Bouquet', '[\"images/studentworks/works-video2.mp4\", \"images/studentworks/works-pic10.jpg\", \"images/studentworks/works-pic6.jpg\"]', 'Bridal bouquet and centerpiece designs for spring wedding. Focused on peonies and eucalyptus.', 'approved')",
            "INSERT INTO studentwork_table (email, first_name, last_name, workshop_title, media_files, description, status) VALUES ('janiceliong@gmail.com', 'Janice', 'Liong', 'Hobby Class', '[\"images/studentworks/works-video3.mp4\"]', 'Autumn wreath with dried flowers, pine cones, and berries. Perfect for fall decoration.', 'approved')",
            "INSERT INTO studentwork_table (email, first_name, last_name, workshop_title, media_files, description, status) VALUES ('janiceliong@gmail.com', 'Janice', 'Liong', 'Hobby Class', '[\"images/studentworks/works-pic5.jpg\", \"images/studentworks/works-pic6.jpg\"]', 'Worked with fresh flowers and soap flowers. Lovely experience.', 'approved')",
            "INSERT INTO studentwork_table (email, first_name, last_name, workshop_title, media_files, description, status) VALUES ('getosuguru@gmail.com', 'Geto', 'Suguru', 'Florist To Be 2', '[\"images/studentworks/works-pic7.jpg\", \"images/studentworks/works-pic8.jpg\"]', 'Worked with fresh flowers and soap flowers. Lovely experience.', 'approved')",
            "INSERT INTO studentwork_table (email, first_name, last_name, workshop_title, media_files, description, status) VALUES ('maxmic@gmail.com', 'Michael', 'Wong', 'Handtied Bouquet', '[\"images/studentworks/works-pic9.jpg\", \"images/studentworks/works-pic10.jpg\"]', 'Bridal bouquet and centerpiece designs for spring wedding. Focused on peonies and eucalyptus.', 'approved')",
        ];
        
        foreach ($student_works as $sql) {
            $conn->query($sql);
        }
        $result->free();
    }

    $conn->close();
} catch (Exception $e) {
    error_log("Error initializing database: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8"/>
    <meta name="author" content="Aryn Mei Wei JEE"/>
    <meta name="description" content="Home Page"/>
    <meta name="keywords" content="Root Flower, floral class, Kuching-based"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/styles.css"/>

    <!--bootstrap framework CSS-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous"/>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.css">

    <title>Root Flower Home Page</title>
</head>

<body class="no-header">
<article>
    <!-- autoplay slides every 5 secs no pause -->
    <div id="auto-carousel" class="carousel slide" data-bs-ride="carousel" data-bs-interval="5000" data-bs-pause="false">
    
        <!-- carousel indicators -->
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#auto-carousel" data-bs-slide-to="0" class="active" aria-current="true" aria-label="Flower Bouquet 1"></button>
            <button type="button" data-bs-target="#auto-carousel" data-bs-slide-to="1" aria-label="Flower Bouquet 2"></button>
            <button type="button" data-bs-target="#auto-carousel" data-bs-slide-to="2" aria-label="Flower Bouquet 3"></button>
        </div>
        
        <!-- carousel images -->
        <div class="carousel-inner">
            <div class="carousel-item active index-item">
                <img src="images/carousel-1.jpg" class="w-100 index-img" alt="Mixed Flower Bouquet">
                <div class="gradient-overlay"></div>
            </div>
            <div class="carousel-item index-item">
                <img src="images/carousel-2.jpg" class="d-block w-100 index-img" alt="Small Pink Mixed Flower Bouquet">
                <div class="gradient-overlay"></div>
            </div>
            <div class="carousel-item index-item">
                <img src="images/carousel-3.jpg" class="d-block w-100 index-img" alt="Large Bright Orange Bouquet">
                <div class="gradient-overlay"></div>
            </div>
        </div>

        <div class="carousel-caption top-0 mt-5">
            <h1 class="mt-5 display-3 fw-bolder text-capitalize">Welcome to Root Flower</h1>
            <p class="fs-5">Dedicated to making pretty flower bouquets for all sorts of occasion, from graduation to Valentines.</p>
            <a href="login.php" class="btn btn-primary btn-lg border-0 mt-4 me-5">Login</a>
            <a href="registration.php" class="btn btn-primary btn-lg border-0 mt-4">Sign Up</a>
        </div>
    </div>

    <!-- introduction -->
    <div class="introduction-container my-5 py-5">
        <p class="text-center px-5">"ROOT" — a vital part of a plant, interconnected with the plant, providing nutrients and enabling the sapling to grow and bear fruit.</p>
        <p class="text-center px-5">Through "THE ROOT Flower & Gift," we hope you can convey your thoughts to your loved ones.</p>
    </div>

    <hr>

    <div class="break-container my-5">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4 mb-5 mx-3">
            <div class="col">
                <img src="images/bouquet-icon.png" alt="Bouquet Icon" class="break-icon d-block mx-auto mb-3">
                <p class="text-center pt-2">Luxurious modern design</p>
            </div>

            <div class="col">
                <img src="images/flower-icon.png" alt="Flower Icon" class="break-icon d-block mx-auto mb-3">
                <p class="text-center pt-2">Artisan curated & handcrafted</p>
            </div>

            <div class="col">
                <img src="images/delivery-icon.png" alt="Delivery Icon" class="break-icon d-block mx-auto mb-3">
                <p class="text-center pt-2">Delivery across Malaysia</p>
            </div>

            <div class="col">
                <img src="images/giftcard-icon.png" alt="Gift Card Icon" class="break-icon d-block mx-auto mb-3">
                <p class="text-center pt-2">Free gift card</p>
            </div>
        </div>
    </div>

    <hr>
    
    <!-- products -->
    <section class="pb-5">
        <h2 class="index-product-title text-center fw-bolder fs-1">Products</h2>

        <!-- top -->
        <div class="container-fluid g-0">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="index-product-section position-relative overflow-visible d-block no-repeat">
                        <img src="images/index-product-valentines.jpg" alt="Valentines Bouquet">
                        <div class="index-product-overlay position-absolute d-flex flex-column justify-content-center">
                            <h6 class="text-uppercase">Valentines-special edition</h6>
                            <h2 class="fw-bold">Valentines</h2>
                            <p>Celebrate the romantic festive with your beloved.</p>
                            <a href="main_menu.php" class="text-decoration-none">EXPLORE NOW</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="index-product-section position-relative overflow-hidden">
                        <img src="images/index-product-graduation.jpg" alt="Graduation Bouquet">
                        <div class="index-product-overlay position-absolute d-flex flex-column justify-content-center">
                            <h6 class="text-uppercase">Ceremonial</h6>
                            <h2 class="fw-bold">Graduation</h2>
                            <p>Beautiful flowers to congratulate a graduating person.</p>
                            <a href="main_menu.php" class="text-decoration-none">EXPLORE NOW</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- mid -->
        <div class="container-fluid g-0 my-3">
            <div class="index-product-section position-relative overflow-hidden">
            <img src="images/index-product-daily.jpg" alt="Daily everyday flowers">
            <div class="index-product-overlay position-absolute d-flex flex-column justify-content-center">
                <h6 class="text-uppercase">EVERYDAY FLOWERS</h6>
                <h2 class="fw-bold">DAILY</h2>
                <p>"Just because" flowers of all kinds prepared to be wrapped and gifted.</p>
                <a href="main_menu.php" class="text-decoration-none">EXPLORE NOW</a>
            </div>
            </div>
        </div>

        <!-- bottom -->
        <div class="container-fluid g-0 lower-container mb-5 pb-5">
            <div class="row g-3">
                <!-- right section -->
                <div class="col-md-6">
                    <div class="index-product-section position-relative overflow-hidden">
                        <img src="images/index-product-flowerstand.jpg" alt="Flower stands for Grand-openings">
                        <div class="index-product-overlay position-absolute d-flex flex-column justify-content-center">
                            <h6 class="text-uppercase">Grand-openings</h6>
                            <h2 class="fw-bold">Flower Stands</h2>
                            <p>Send a flower stand to congratulate the opening of a business now.</p>
                            <a href="main_menu.php" class="text-decoration-none">EXPLORE NOW</a>
                        </div>
                    </div>
                </div>
                
                <!-- right section -->
                <div class="col-md-6">
                    <div class="index-product-section position-relative overflow-hidden">
                        <img src="images/index-product-cny.jpg" alt="Chinese New Year Flowers">
                        <div class="index-product-overlay position-absolute d-flex flex-column justify-content-center">
                            <h6 class="text-uppercase">Festive / seasonal</h6>
                            <h2 class="fw-bold">Chinese New Year</h2>
                            <p>Add life and colors to the corners of your home by ordering a CNY flower deco now.</p>
                            <a href="main_menu.php" class="text-decoration-none">EXPLORE NOW</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- cta section: redirect to identify page -->
    <section class="cta-identifier mx-5 mb-5">
        <div class="container mx-auto">
            <div class="row align-items-center g-5">

                <div class="col-lg-6">
                    <span class="cta-eyebrow d-inline-block fw-bold text-uppercase mb-2">Flower Identifier</span>
                    <h2 class="cta-heading fw-bold mb-2">Curious About a Flower?</h2>
                    <p class="cta-text mb-5">Not sure what flower you're looking at? Upload a photo and let our AI do the work — instantly identifying the species, name, and key details of any bloom you encounter.</p>
                    <a href="<?php echo isset($_SESSION['user']) ? 'identify.php' : 'login.php'; ?>" class="cta-btn d-inline-flex align-items-center text-decoration-none rounded-3 px-4 py-2 fw-medium">
                        Identify a Flower
                        <i class="bi bi-arrow-right ms-2"></i>
                    </a>
                </div>

                <div class="col-lg-6">
                    <div class="cta-image-grid d-grid gap-4">
                        <img src="images/carousel-1.jpg" alt="Flower bouquet" class="cta-img cta-img-tall w-100 h-100 object-fit-cover rounded-2">
                        <img src="images/valentine-bouquet1.jpg" alt="Valentine bouquet" class="cta-img w-100 h-100 object-fit-cover rounded-2">
                        <img src="images/graduation-bouquet1.jpg" alt="Graduation bouquet" class="cta-img w-100 h-100 object-fit-cover rounded-2">
                    </div>
                </div>

            </div>
        </div>
    </section>
</article>

<!-- footer -->
<?php include "include/footer.php" ?>

<!-- bootstrap framework Javascript-->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script> 
</body>
</html>

