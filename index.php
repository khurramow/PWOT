<?php
require 'db.php';

$tracker = new PersonalWorkOffTracker();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['update'])) {
        $id = $_POST['worked_off'];
        $tracker->updateWorkedOff($id);
    } else {
        $arrived_at = $_POST['arrived_at'];
        $leaved_at = $_POST['leaved_at'];
        $tracker->addRecord($arrived_at, $leaved_at);
    }
}

$page = isset($_GET['page']) ? $_GET['page'] : 1;
$total_pages = $tracker->getTotalPages(5);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PWOT - Personal Work Off Tracker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container mt-5">
    <h1 class="text-center">PWOT - Personal Work Off Tracker</h1>

    <div class="form-container mb-4 float-end">
        <form action="index.php" method="post" class="row g-3">
            <div class="col-md-6">
                <label for="arrived_at" class="form-label">Arrived At</label>
                <input type="datetime-local" id="arrived_at" name="arrived_at" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label for="leaved_at" class="form-label">Leaved At</label>
                <input type="datetime-local" id="leaved_at" name="leaved_at" class="form-control" required>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Submit</button>
            </div>
        </form>
    </div>

    <?php
    $tracker->fetchRecords($page);
    ?>

    <nav aria-label="Page navigation example">
        <ul class="pagination">
            <li class="page-item <?php if ($page <= 1) { echo 'disabled'; } ?>">
                <a class="page-link" href="<?php if ($page > 1) { echo '?page=' . ($page - 1); } else { echo '#'; } ?>">Previous</a>
            </li>
            <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                <li class="page-item <?php if ($page == $i) { echo 'active'; } ?>">
                    <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?php if ($page >= $total_pages) { echo 'disabled'; } ?>">
                <a class="page-link" href="<?php if ($page < $total_pages) { echo '?page=' . ($page + 1); } else { echo '#'; } ?>">Next</a>
            </li>
        </ul>
    </nav>
</div>

<div class="modal fade" id="confirmModal" tabindex="-1" aria-labelledby="confirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="confirmModalLabel">Confirmation</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to mark this record as worked off?
            </div>
            <div class="modal-footer">
                <form action="" method="post">
                    <input type="hidden" name="worked_off" id="workedOffId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary" name="update">Yes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    var confirmModal = document.getElementById('confirmModal');
    confirmModal.addEventListener('show.bs.modal', function (event) {
        var button = event.relatedTarget;
        var id = button.getAttribute('data-id');
        var modalInput = document.getElementById('workedOffId');
        modalInput.value = id;
    });
</script>
</body>
</html>
