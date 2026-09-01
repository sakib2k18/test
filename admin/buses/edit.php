<?php
/**
 * ADMIN - BUSES : UPDATE
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();
require_once __DIR__ . '/_validate.php';

$pageTitle    = 'Edit bus';
$pageSubtitle = 'Update an existing bus record';
$adminSection = 'buses';

/* The id may arrive from the URL (GET) or from the form (POST). */
$id      = (int) ($_SERVER['REQUEST_METHOD'] === 'POST' ? post('id', 0) : get('id', 0));
$current = $id > 0 ? fetchOne('SELECT * FROM buses WHERE id = ?', [$id]) : null;

if (!$current) {
    setFlash('error', 'That bus does not exist any more.');
    redirect('admin/buses/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf('admin/buses/edit.php?id=' . $id);

    $input = [
        'bus_name'       => post('bus_name'),
        'reg_number'     => post('reg_number'),
        'model'          => post('model'),
        'capacity'       => post('capacity'),
        'bus_type'       => post('bus_type'),
        'status'         => post('status'),
        'driver_name'    => post('driver_name'),
        'driver_contact' => post('driver_contact'),
        'description'    => post('description'),
    ];

    /* The current record is excluded from the "unique plate" check. */
    $errors = validateBusInput($input, $id);

    $newImage    = null;
    $removeImage = isset($_POST['remove_image']);

    if (!$errors && isset($_FILES['image'])) {
        $newImage = uploadBusImage($_FILES['image'], $uploadError);
        if ($uploadError !== null) {
            $errors[] = $uploadError;
        }
    }

    if ($errors) {
        deleteBusImage($newImage);
        keepErrors($errors);
        keepOld(array_merge($_POST, ['facilities' => $_POST['facilities'] ?? []]));
        setFlash('error', 'The bus could not be updated. Please check the form.');
        redirect('admin/buses/edit.php?id=' . $id);
    }

    /* Decide which filename ends up in the database. */
    $imageToStore = $current['image'];
    if ($newImage !== null) {
        $imageToStore = $newImage;
    } elseif ($removeImage) {
        $imageToStore = null;
    }

    try {
        q(
            'UPDATE buses
                SET bus_name = ?, reg_number = ?, model = ?, capacity = ?, bus_type = ?,
                    status = ?, driver_name = ?, driver_contact = ?, description = ?, image = ?
              WHERE id = ?',
            [
                $input['bus_name'],
                $input['reg_number'],
                $input['model'] !== '' ? $input['model'] : null,
                (int) $input['capacity'],
                $input['bus_type'],
                $input['status'],
                $input['driver_name'] !== ''    ? $input['driver_name']    : null,
                $input['driver_contact'] !== '' ? $input['driver_contact'] : null,
                $input['description'] !== ''    ? $input['description']    : null,
                $imageToStore,
                $id,
            ]
        );

        saveBusFacilities($id, (array) ($_POST['facilities'] ?? []));

        /* Only delete the old file once the update really succeeded. */
        if (($newImage !== null || $removeImage) && !isBlank($current['image'])) {
            deleteBusImage($current['image']);
        }

        logActivity('Bus updated', 'Updated bus "' . $input['bus_name'] . '" (' . $input['reg_number'] . ').');
        setFlash('success', 'Bus "' . $input['bus_name'] . '" was updated successfully.');
        redirect('admin/buses/index.php');

    } catch (PDOException $ex) {
        deleteBusImage($newImage);
        error_log('[BUS EDIT] ' . $ex->getMessage());

        keepErrors([
            $ex->getCode() === '23000'
                ? 'Another bus is already registered with this registration number.'
                : 'The bus could not be updated because of a database error.',
        ]);
        keepOld($_POST);
        setFlash('error', 'The bus could not be updated.');
        redirect('admin/buses/edit.php?id=' . $id);
    }
}

/* ---------------- Render the form filled with the stored values ---------- */
$errors        = takeErrors();
$allFacilities = fetchAll('SELECT id, name FROM facilities ORDER BY name');

$storedFacilities = array_map(
    'intval',
    array_column(fetchAll('SELECT facility_id FROM bus_facilities WHERE bus_id = ?', [$id]), 'facility_id')
);

/* After a failed submit the visitor's own values are shown again. */
$hasOld = !empty($_SESSION['old']);

$bus = [
    'id'             => $id,
    'bus_name'       => $hasOld ? old('bus_name')       : $current['bus_name'],
    'reg_number'     => $hasOld ? old('reg_number')     : $current['reg_number'],
    'model'          => $hasOld ? old('model')          : (string) $current['model'],
    'capacity'       => $hasOld ? old('capacity')       : $current['capacity'],
    'bus_type'       => $hasOld ? old('bus_type')       : $current['bus_type'],
    'status'         => $hasOld ? old('status')         : $current['status'],
    'driver_name'    => $hasOld ? old('driver_name')    : (string) $current['driver_name'],
    'driver_contact' => $hasOld ? old('driver_contact') : (string) $current['driver_contact'],
    'description'    => $hasOld ? old('description')    : (string) $current['description'],
    'image'          => $current['image'],
];

$selectedFacilities = $hasOld
    ? array_map('intval', (array) old('facilities', []))
    : $storedFacilities;

$formAction  = url('admin/buses/edit.php?id=' . $id);
$submitLabel = 'Update bus';
$isEdit      = true;

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Edit <?= e($current['bus_name']) ?></h1>
        <p>Registration <?= e($current['reg_number']) ?> &middot; added on <?= e(dateText($current['created_at'])) ?>.</p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--outline" href="<?= e(url('admin/buses/view.php?id=' . $id)) ?>"><?= icon('eye') ?> View</a>
        <a class="btn btn--outline" href="<?= e(url('admin/buses/index.php')) ?>">Back to buses</a>
    </div>
</div>

<?php
require __DIR__ . '/_form.php';
clearOld();
require_once __DIR__ . '/../../includes/admin_footer.php';
?>
