<?php require_once __DIR__ . '/includes/header.php';

$buses = [
  [
    'id' => 1,
    'name' => 'Volvo 9700',
    'photo' => '/test/assets/uploads/bus1.jpg',
    'capacity' => 50,
    'info' => 'Comfortable intercity coach with AC, Wi-Fi and reclining seats.',
    'route_name' => 'City Express',
    'origin' => 'Central Station',
    'destination' => 'Airport'
  ],
  [
    'id' => 2,
    'name' => 'Mercedes Tourismo',
    'photo' => '/test/assets/uploads/bus2.jpg',
    'capacity' => 45,
    'info' => 'Luxurious coach with onboard restroom and entertainment system.',
    'route_name' => 'Coastal Line',
    'origin' => 'Harbor',
    'destination' => 'Seaside'
  ]
];
?>
<section class="hero mb-4">
  <div class="container text-center text-dark">
    <h1 class="display-6">Premium Bus Fleet & Routes</h1>
    <p class="lead">Explore our buses, view routes, and learn about onboard amenities.</p>
  </div>
</section>

<div class="row g-4">
  <?php foreach ($buses as $bus): ?>
    <div class="col-sm-6 col-md-4">
      <div class="card shadow-sm">
        <img src="<?= htmlspecialchars($bus['photo'] ?: 'https://via.placeholder.com/600x400?text=Bus+Photo') ?>" class="card-img-top" alt="<?=htmlspecialchars($bus['name'])?>">
        <div class="card-body">
          <h5 class="card-title"><?=htmlspecialchars($bus['name'])?></h5>
          <p class="card-text small text-muted">Route: <?=htmlspecialchars($bus['route_name'] ?: '—')?></p>
          <p class="card-text bus-info"><?=htmlspecialchars(
            strlen($bus['info'])>120?substr($bus['info'],0,120).'...':$bus['info']
          )?></p>
          <a href="/test/bus.php?id=<?=$bus['id']?>" class="btn btn-primary">View details</a>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php';
