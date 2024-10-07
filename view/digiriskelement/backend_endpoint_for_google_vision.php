<?php
global $conf;

// Récupération de l'image
if (isset($_FILES['image_file'])) {
    $imagePath = $_FILES['image_file']['tmp_name'];
    $imageData = base64_encode(file_get_contents($imagePath));

    // Appel à l'API Google Vision
    $visionApiKey = $conf->global->DIGIRISKDOLIBARR_GOOGLE_VISION_API_KEY;
    $visionUrl = 'https://vision.googleapis.com/v1/images:annotate?key=' . $visionApiKey;

    $visionRequest = [
        'requests' => [
            [
                'image' => ['content' => $imageData],
                'features' => [
                    ['type' => 'LABEL_DETECTION', 'maxResults' => 10],
                    ['type' => 'OBJECT_LOCALIZATION', 'maxResults' => 10],
                    ['type' => 'TEXT_DETECTION']
                ]
            ]
        ]
    ];

    // Requête cURL
    $ch = curl_init($visionUrl);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($visionRequest));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $visionResponse = curl_exec($ch);
    curl_close($ch);

    echo $visionResponse;
}
?>
