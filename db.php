<?php

class PersonalWorkOffTracker {
    private $conn;

    public function __construct() {
        $this->conn = new PDO('mysql:host=localhost;dbname=work_off_tracker', 'root', 'root');
    }

    public function addRecord($arrived_at, $leaved_at) {
        $arrived_at_dt = new DateTime($arrived_at);
        $leaved_at_dt = new DateTime($leaved_at);

        $interval = $arrived_at_dt->diff($leaved_at_dt);
        $hours = $interval->h + ($interval->days * 24);
        $minutes = $interval->i;

        $required_work_off = sprintf('%02d:%02d:00', $hours, $minutes);

        $debt_hours = 0;
        $debt_minutes = 0;
        if ($hours < 9) {
            $debt_hours = 9 - $hours;
            $debt_minutes = 60 - $minutes;
            if ($debt_minutes == 60) {
                $debt_minutes = 0;
            }
        }

        $sql = "INSERT INTO vaqt (arrived_at, leaved_at, required_work_off, debt_hours, debt_minutes) VALUES (:arrived_at, :leaved_at, :required_work_off, :debt_hours, :debt_minutes)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':arrived_at', $arrived_at);
        $stmt->bindParam(':leaved_at', $leaved_at);
        $stmt->bindParam(':required_work_off', $required_work_off);
        $stmt->bindParam(':debt_hours', $debt_hours);
        $stmt->bindParam(':debt_minutes', $debt_minutes);

        $stmt->execute();
    }

    public function fetchRecords($page_id) {
        $offset = ($page_id - 1) * 5;
        $sql = "SELECT * FROM vaqt ORDER BY id ASC LIMIT $offset, 5";
        $result = $this->conn->query($sql);
        $total_hours = 0;
        $total_minutes = 0;
        $total_debt_hours = 0;
        $total_debt_minutes = 0;

        if ($result->rowCount() > 0) {
            echo '<form action="index.php" method="post">';
            echo '<table class="table table-striped">';
            echo '<thead class="table-dark"><tr><th>#</th><th>Arrived at</th><th>Leaved at</th><th>Required work off</th><th>Worked off</th></tr></thead>';
            echo '<tbody>';
            while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
                $worked_off_class = $row["worked_off"] ? 'class="worked-off"' : '';
                echo "<tr $worked_off_class>";
                echo '<td>' . $row["id"] . '</td>';
                echo '<td>' . $row["arrived_at"] . '</td>';
                echo '<td>' . $row["leaved_at"] . '</td>';
                echo '<td>' . $row["required_work_off"] . '</td>';
                if ($row["worked_off"]) {
                    echo '<td><button type="button" class="btn btn-success btn-sm" disabled>Done</button></td>';
                } else {
                    echo '<td><button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#confirmModal" data-id="' . $row["id"] . '">Done</button></td>';
                }
                echo '</tr>';

                if (!$row["worked_off"]) {
                    list($hours, $minutes, $seconds) = explode(':', $row["required_work_off"]);
                    $total_hours += (int)$hours;
                    $total_minutes += (int)$minutes;
                    $total_debt_hours += (int)$row["debt_hours"];
                    $total_debt_minutes += (int)$row["debt_minutes"];
                }
            }
            $total_hours += floor($total_minutes / 60);
            $total_minutes = $total_minutes % 60;
            $total_debt_hours += floor($total_debt_minutes / 60);
            $total_debt_minutes = $total_debt_minutes % 60;

            echo '<tr><td colspan="4" class="text-end fw-bold">Total work off hours</td><td>' . $total_hours . ' hours and ' . $total_minutes . ' min.</td></tr>';
            echo '<tr><td colspan="4" class="text-end fw-bold">Total debt hours</td><td>' . $total_debt_hours . ' hours and ' . $total_debt_minutes . ' min.</td></tr>';
            echo '</tbody>';
            echo '</table>';
            echo '<button type="submit" name="export" class="btn btn-primary">Export as CSV</button>';
            echo '</form>';
        }
    }

    public function updateWorkedOff($id) {
        $sql = "UPDATE vaqt SET worked_off = 1 WHERE id = :id";
        $stmt = $this->conn->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
    }

    public function exportCSV() {
        $sql = "SELECT * FROM vaqt";
        $result = $this->conn->query($sql);

        $filename = "work_off_report_" . date('Ymd') . ".csv";
        $file = fopen('php://output', 'w');

        $header = array("ID", "Arrived At", "Leaved At", "Required Work Off", "Worked Off", "Debt Hours", "Debt Minutes");
        fputcsv($file, $header);

        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($file, $row);
        }

        fclose($file);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '";');

        exit();
    }

    public function getTotalPages($records_per_page) {
        $sql = "SELECT COUNT(*) as total FROM vaqt";
        $result = $this->conn->query($sql);
        $row = $result->fetch(PDO::FETCH_ASSOC);
        return ceil($row['total'] / $records_per_page);
    }
}

?>
