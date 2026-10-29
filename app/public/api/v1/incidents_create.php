<?php
$user = api_require_login($currentUser);
$input = json_input();

$title = trim($input['title'] ?? '');
$incidentType = trim($input['incident_type'] ?? '');
$location = trim($input['location'] ?? '');
$description = trim($input['description'] ?? '');
$villainId = isset($input['villain_id']) && $input['villain_id'] !== '' ? (int) $input['villain_id'] : null;

if ($title === '' || $incidentType === '' || $location === '' || $description === '') {
    json_error('title, incident_type, location et description sont obligatoires.');
}

$stmt = $pdo->prepare(
    'INSERT INTO incidents (citizen_id, title, incident_type, location, description, villain_id, status, priority)
     VALUES (?,?,?,?,?,?,"signale","normale")'
);
$stmt->execute([$user['id'], $title, $incidentType, $location, $description, $villainId]);
$id = (int) $pdo->lastInsertId();

$recipients = $pdo->query("SELECT email FROM users WHERE is_admin = 1 OR hero_status = 'verified'")->fetchAll();
foreach ($recipients as $r) {
    send_app_mail($r['email'], 'Nouvel incident signalé : ' . $title,
        '<p>Lieu : ' . e($location) . ' — Type : ' . e($incidentType) . '</p>'
    );
}

json_response(['message' => 'Incident signalé.', 'id' => $id], 201);
