<?php
include 'db.php';
$result = mysqli_query($conn, 'DESCRIBE orders');
while ($row = mysqli_fetch_assoc($result)) {
    print_r($row);
}
