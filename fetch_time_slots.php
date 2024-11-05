<?php
require("./conx/conx_customer.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $selectedDate = $input['selectedDate'];
    $roomId = $input['roomId'];

    try {
        // Fetch available time slots for the selected room
        $stmt_time_slots = $conx_customer->prepare("
            SELECT t.time_start, t.time_end
            FROM time t
            LEFT JOIN res r ON t.time_start = r.res_start_time AND r.res_date = :selectedDate AND r.res_status = 'reserved' AND r.room_id = :roomId
            WHERE r.res_start_time IS NULL
        ");
        $stmt_time_slots->bindParam(':selectedDate', $selectedDate, PDO::PARAM_STR);
        $stmt_time_slots->bindParam(':roomId', $roomId, PDO::PARAM_INT);
        $stmt_time_slots->execute();
        $available_time_slots = $stmt_time_slots->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($available_time_slots);
    } catch (PDOException $e) {
        // Handle database errors
        error_log($e->getMessage());
        echo json_encode([]);
    }
}