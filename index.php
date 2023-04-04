<?php

// Define file paths for storing data
$dataFile = __DIR__ . '/data.txt';

// Define endpoint paths for API
$imagesEndpoint = '/images';

// Parse HTTP method and path from request
$httpMethod = $_SERVER['REQUEST_METHOD'];
$requestPath = $_SERVER['REQUEST_URI'];

// Handle requests to upload a new image
if ($httpMethod === 'POST' && strpos($requestPath, $imagesEndpoint) === 0) {
    // Parse request body to get image and text data
    $image = $_FILES['image'];
    $text = $_POST['text'];

    // Generate a unique filename for the image
    $ext = pathinfo($image['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '.' . $ext;
    $filepath = __DIR__ . '/uploads/' . $filename;

    // Move the image file to the uploads directory
    move_uploaded_file($image['tmp_name'], $filepath);

    // Write image link and text to data file
    $data = json_decode(file_get_contents($dataFile), true);
    $data[] = ['filename' => $filename, 'text' => $text];
    file_put_contents($dataFile, json_encode($data));

    // Respond with success message
    http_response_code(201);
    echo json_encode(['message' => 'Image uploaded successfully']);
}

// Handle requests to retrieve all images and their text
if ($httpMethod === 'GET' && strpos($requestPath, $imagesEndpoint) === 0) {
    // Read data file and convert to array of image links and texts
    $data = json_decode(file_get_contents($dataFile), true);
    $images = [];
    foreach ($data as $item) {
        $images[] = [
            'url' => '/uploads/' . $item['filename'],
            'text' => $item['text'],
        ];
    }

    // Respond with array of images and texts
    echo json_encode($images);
}

// Handle requests to delete an image
if ($httpMethod === 'DELETE' && preg_match('#^' . $imagesEndpoint . '/([a-f0-9]+\\.[a-z]+)$#i', $requestPath, $matches)) {
    $filename = $matches[1];
    $filepath = __DIR__ . '/uploads/' . $filename;

    // Delete the image file
    unlink($filepath);

    // Remove the image link and text from data file
    $data = json_decode(file_get_contents($dataFile), true);
    $data = array_filter($data, function ($item) use ($filename) {
        return $item['filename'] !== $filename;
    });
    file_put_contents($dataFile, json_encode($data));

    // Respond with success message
    echo json_encode(['message' => 'Image deleted successfully']);
}
