<?php require_once __DIR__ . '/includes/header.php';

$buses = [
  1 => [
    'id' => 1,
    'name' => 'Volvo 9700',
    'photo' => '/test/assets/uploads/bus1.jpg',
    'capacity' => 50,
    'info' => 'Comfortable intercity coach with AC, Wi-Fi and reclining seats.',
    'route_name' => 'City Express',
    'origin' => 'Central Station',
    'destination' => 'Airport',
    'stops' => 'Stop A, Stop B, Stop C'
  ],
  2 => [
    'id' => 2,
    'name' => 'Mercedes Tourismo',
    'photo' => '/test/assets/uploads/bus2.jpg',
    'capacity' => 45,
    'info' => 'Luxurious coach with onboard restroom and entertainment system.',
    'route_name' => 'Coastal Line',
    'origin' => 'Harbor',
    'destination' => 'Seaside',
    'stops' => 'Pier 1, Pier 2'
  ]
];

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$id || !isset($buses[$id])) {
    echo '<div class="alert alert-warning">Bus not found.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$bus = $buses[$id];
?>
<div class="row">
  <div class="col-md-6">
    <img src="<?= htmlspecialchars($bus['photo'] ?: 'https://via.placeholder.com/800x500?text=Bus+Photo') ?>" class="img-fluid rounded">
  </div>
  <div class="col-md-6">
    <h2><?=htmlspecialchars($bus['name'])?></h2>
    <p class="text-muted">Route: <?=htmlspecialchars($bus['route_name'] ?: '—')?></p>
    <p class="bus-info"><?=nl2br(htmlspecialchars($bus['info']))?></p>
    <ul class="list-unstyled mt-3">
      <li><strong>Capacity:</strong> <?=htmlspecialchars($bus['capacity'])?></li>
      <li><strong>Route origin:</strong> <?=htmlspecialchars($bus['origin'])?></li>
      <li><strong>Route destination:</strong> <?=htmlspecialchars($bus['destination'])?></li>
      <li><strong>Stops:</strong> <?=htmlspecialchars($bus['stops'])?></li>
    </ul>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php';
