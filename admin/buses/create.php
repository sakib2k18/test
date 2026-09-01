<?php
/**
 * ADMIN - BUSES : CREATE
 */
require_once __DIR__ . '/../../includes/init.php';
requireAdmin();
require_once __DIR__ . '/_validate.php';

$pageTitle    = 'Add bus';
$pageSubtitle = 'Create a new bus record';
$adminSection = 'buses';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    requireCsrf('admin/buses/create.php');

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

    $errors     = validateBusInput($input);
    $imageName  = null;

    /* The photo is only stored once the rest of the form is valid. */
    if (!$errors && isset($_FILES['image'])) {
        $imageName = uploadBusImage($_FILES['image'], $uploadError);
        if ($uploadError !== null) {
            $errors[] = $uploadError;
        }
    }

    if ($errors) {
        keepErrors($errors);
        keepOld(array_merge($_POST, ['facilities' => $_POST['facilities'] ?? []]));
        setFlash('error', 'The bus could not be added. Please check the form.');
        redirect('admin/buses/create.php');
    }

    try {
        q(
            'INSERT INTO buses
               (bus_name, reg_number, model, capacity, bus_type, status,
                driver_name, driver_contact, description, image)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
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
                $imageName,
            ]
        );

        $busId = (int) db()->lastInsertId();
        saveBusFacilities($busId, (array) ($_POST['facilities'] ?? []));

        logActivity('Bus added', 'Added bus "' . $input['bus_name'] . '" (' . $input['reg_number'] . ').');
        setFlash('success', 'Bus "' . $input['bus_name'] . '" was added successfully.');
        redirect('admin/buses/index.php');

    } catch (PDOException $ex) {
        deleteBusImage($imageName);   // do not leave an orphan file behind
        error_log('[BUS CREATE] ' . $ex->getMessage());

        keepErrors([
            $ex->getCode() === '23000'
                ? 'Another bus is already registered with this registration number.'
                : 'The bus could not be saved because of a database error.',
        ]);
        keepOld($_POST);
        setFlash('error', 'The bus could not be added.');
        redirect('admin/buses/create.php');
    }
}

/* ---------------- Render the empty (or re-filled) form ---------------- */
$errors        = takeErrors();
$allFacilities = fetchAll('SELECT id, name FROM facilities ORDER BY name');

$bus = [
    'id'             => 0,
    'bus_name'       => old('bus_name'),
    'reg_number'     => old('reg_number'),
    'model'          => old('model'),
    'capacity'       => old('capacity', '40'),
    'bus_type'       => old('bus_type', 'Student Bus'),
    'status'         => old('status', 'Active'),
    'driver_name'    => old('driver_name'),
    'driver_contact' => old('driver_contact'),
    'description'    => old('description'),
    'image'          => null,
];

$selectedFacilities = array_map('intval', (array) old('facilities', []));
$formAction  = url('admin/buses/create.php');
$submitLabel = 'Save bus';
$isEdit      = false;

require_once __DIR__ . '/../../includes/admin_header.php';
?>

<div class="admin-head">
    <div>
        <h1>Add a new bus</h1>
        <p>Fill in the details below. Fields marked with * are required.</p>
    </div>
    <div class="admin-head__actions">
        <a class="btn btn--outline" href="<?= e(url('admin/buses/index.php')) ?>">Back to buses</a>
    </div>
</div>

<?php
require __DIR__ . '/_form.php';
clearOld();
require_once __DIR__ . '/../../includes/admin_footer.php';
?>
