<?php

// Define the path to the JSON file.
$json_file = __DIR__ . '/../data/usuarios.json';

// Check if the file exists.
if (!file_exists($json_file)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found.']);
    exit;
}

// Read the JSON file.
$json_data = file_get_contents($json_file);
$data = json_decode($json_data);

// Check if the JSON data is valid.
if (!$data || !isset($data->usuarios)) {
    http_response_code(500);
    echo json_encode(['error' => 'Invalid JSON data.']);
    exit;
}

// Get the POST input.
$input = json_decode(file_get_contents('php://input'), true);

// Get the filter, limit, and page parameters from the POST request.
$filter = isset($input['filter']) ? strtolower(trim($input['filter'])) : '';
$limit = isset($input['limit']) ? (int) $input['limit'] : 0;
$page = isset($input['page']) ? (int) $input['page'] : 1;

// Filter the users based on the filter value.
$users = $data->usuarios;
if (!empty($filter)) {
    $users = array_filter($users, function ($user) use ($filter) {
        return strpos(strtolower($user->name), $filter) !== false ||
               strpos(strtolower($user->surname1), $filter) !== false ||
               strpos(strtolower($user->surname2), $filter) !== false ||
               strpos(strtolower($user->email), $filter) !== false;
    });
}

// Save the total number of users after filtering.
$total_users_filtered = count($users);

// Calculate the offset for pagination.
$offset = ($page - 1) * $limit;

// Apply the limit and offset if specified.
if ($limit > 0) {
    $users = array_slice($users, $offset, $limit);
}

// Return the filtered users as JSON.
header('Content-Type: application/json');
echo json_encode([
    'usuarios' => array_values($users),
    'total' => count($data->usuarios), // Total number of users (before filtering).
    'total_filtered' => $total_users_filtered, // Total number of users after filtering.
]);