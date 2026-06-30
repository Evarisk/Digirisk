<?php
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

global $conf, $langs;

$chatGptApiKey = $conf->global->DIGIRISKDOLIBARR_CHATGPT_API_KEY;
$chatGptUrl = 'https://api.openai.com/v1/chat/completions';

$action = $_POST['action'] ?? '';


if ($action == 'analyze_image') {
    if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['error' => 'Aucune image reçue ou erreur lors de l\'upload.']);
        exit;
    }

    $imagePath = $_FILES['image_file']['tmp_name'];
    $imageData = base64_encode(file_get_contents($imagePath));
    $imageMimeType = mime_content_type($imagePath);
    if ($imageData) {
        $content = [
            'type' => 'image_url',
            'image_url' => [
                'url' => 'data:' . $imageMimeType . ';base64,' . $imageData,
            ]
        ];
    }
}

if ($action == 'analyze_text') {

    $textAnalysis = $_POST['analysis_text'] ?? '';
    if ($textAnalysis) {
        $content = [
            'type' => 'text',
            'text' => $langs->trans('HereIsTheWorkStationDescription') . $textAnalysis
        ];
    }
}

$chatGptRequest = [
    'model' => 'gpt-4o',
    'messages' => [
        [
            'role' => 'system',
            'content' => 'Tu es un expert en analyse de risques professionnels dans des environnements visuels. En te basant sur l\'ED840 de l\'INRS, identifie les risques professionnels potentiels dans cette image. Donne uniquement un tableau JSON comme spécifié.'
        ],
        [
            'role' => 'user',
            'content' => [
                $content,
                [
                    'type' => 'text',
                    'text' => <<<EOT
Analyse l’image selon ces catégories de risque :

[
  {"name": "Risques de chute de plain-pied", "thumbnail_name": "chutePP_PictoCategorie_v2"},
  {"name": "Risques de chute de hauteur", "thumbnail_name": "chuteH_PictoCategorie_v2"},
  {"name": "Risques liés aux circulations internes de véhicules et d'engins", "thumbnail_name": "circulation_PictoCategorie_v2"},
  {"name": "Risques routiers en mission", "thumbnail_name": "circulation_v2"},
  {"name": "Risques liés à la charge physique de travail", "thumbnail_name": "activitePhysique_v2"},
  {"name": "Risques liés à la manutention mécanique", "thumbnail_name": "manutentionMe_PictoCategorie_v2"},
  {"name": "Risques liés aux produits chimiques, aux émissions et aux déchets", "thumbnail_name": "produitsC_PictoCategorie_v2"},
  {"name": "Risques liés aux agents biologiques", "thumbnail_name": "manqueHygiene_PictoCategorie_v2"},
  {"name": "Risques liés aux équipements de travail", "thumbnail_name": "machine_PictoCategorie_v2"},
  {"name": "Risques liés aux effondrements et aux chutes d'objets", "thumbnail_name": "effondrement_PictoCategorie_v2"},
  {"name": "Risques et nuisances liés au bruit", "thumbnail_name": "nuisances_PictoCategorie_v2"},
  {"name": "Risques liés aux ambiances thermiques", "thumbnail_name": "climat_PictoCategorie_v2"},
  {"name": "Risques d'incendie et d'explosion", "thumbnail_name": "incendies_PictoCategorie_v2"},
  {"name": "Risques liés à l'électricité", "thumbnail_name": "electricite_PictoCategorie_v2"},
  {"name": "Risques liés aux ambiances lumineuses", "thumbnail_name": "eclairage_PictoCategorie_v2"},
  {"name": "Risques liés aux rayonnements", "thumbnail_name": "rayonnement_v2"},
  {"name": "Risques psychosociaux", "thumbnail_name": "rps_v2"},
  {"name": "Risques liés aux vibrations", "thumbnail_name": "vibration_PictoCategorie_v2"},
  {"name": "Risques de heurt, de cognement", "thumbnail_name": "heurt_PictoCategorie_v2"},
  {"name": "Risques liés aux pratiques addictives", "thumbnail_name": "pratiques_addictives_PictoCategorie_v2"},
  {"name": "Risques liés à l'amiante", "thumbnail_name": "amiante_v2"},
  {"name": "Risques autres", "thumbnail_name": "autre_PictoCategorie_v2"}
]

Donne la réponse uniquement sous forme d’un tableau JSON d’objets avec la structure suivante :
[
  {
    "title": "thumbnail_name",
    "description": "texte",
    "cotation": 70,
    "prevention_actions": ["action 1", "action 2"]
  },
  ...
]
EOT
                ]
            ]
        ]
    ],
    'max_tokens' => 1000
];

// Envoi à l'API OpenAI
$ch = curl_init($chatGptUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $chatGptApiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($chatGptRequest));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
curl_close($ch);

// Retourne brut la réponse OpenAI
header('Content-Type: application/json');
echo $response;
