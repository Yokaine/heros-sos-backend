<?php
$villains = $pdo->query('SELECT * FROM villains ORDER BY pseudo')->fetchAll();
json_response(['data' => $villains]);
