<?php
if (file_exists('../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../digiriskdolibarr.main.inc.php';
} elseif (file_exists('../../digiriskdolibarr.main.inc.php')) {
    require_once __DIR__ . '/../../digiriskdolibarr.main.inc.php';
} else {
    die('Include of digiriskdolibarr main fails');
}

global $conf;
// Récupération des données JSON
$data = json_decode(file_get_contents('php://input'), true);
$description = $data['description'];

// Appel à l'API ChatGPT
$chatGptApiKey = $conf->global->DIGIRISKDOLIBARR_CHATGPT_API_KEY;
$chatGptUrl = 'https://api.openai.com/v1/chat/completions';

$chatGptRequest = [
    'model' => 'gpt-4',
    'messages' => [
        [
            'role' => 'system',
            'content' => 'Tu es un expert en analyse de risques professionnels dans des environnements visuels. En te basant sur l\'ED840 de l\'INRS analyse les risques professionnels potentiels dans cette image. Il n\'y a pas de mauvaise réponse, donne une liste au maximum exhaustive des risques que tu vois.
            Les catégories disponibles sont les suivantes : "{
  name: Risques de chute de plain-pied,
  thumbnail_name: chutePP_PictoCategorie_v2
},
{
  name: Risques de chute de hauteur,
  thumbnail_name: chuteH_PictoCategorie_v2
},
{
  name: Risques liés aux circulations internes de véhicules et d\'engins,
  thumbnail_name: circulation_PictoCategorie_v2
},
{
  name: Risques routiers en mission,
  thumbnail_name: circulation_v2
},
{
  name: Risques liés à la charge physique de travail,
  thumbnail_name: activitePhysique_v2
},
{
  name: Risques liés à la manutention mécanique,
  thumbnail_name: manutentionMe_PictoCategorie_v2
},
{
  name: Risques liés aux produits chimiques, aux émissions et aux déchets,
  thumbnail_name: produitsC_PictoCategorie_v2
},
{
  name: Risques liés aux agents biologiques,
  thumbnail_name: manqueHygiene_PictoCategorie_v2
},
{
  name: Risques liés aux équipements de travail,
  thumbnail_name: machine_PictoCategorie_v2
},
{
  name: Risques liés aux effondrements et aux chutes d\'objets,
  thumbnail_name: effondrement_PictoCategorie_v2
},
{
  name: Risques et nuisances liés au bruit,
  thumbnail_name: nuisances_PictoCategorie_v2
},
{
  name: Risques liés aux ambiances thermiques,
  thumbnail_name: climat_PictoCategorie_v2
},
{
  name: Risques d\'incendie et d\'explosion,
  nameDigiriskWordPress: Risques d\'incendie, d\'explosion,
  thumbnail_name: incendies_PictoCategorie_v2
},
{
  name: Risques liés à lélectricité,
  thumbnail_name: electricite_PictoCategorie_v2
},
{
  name: Risques liés aux ambiances lumineuses,
  thumbnail_name: eclairage_PictoCategorie_v2
},
{
  name: Risques liés aux rayonnements,
  thumbnail_name: rayonnement_v2
},
{
  name: Risques psychosociaux,
  thumbnail_name: rps_v2
},
{
  name: Risques liés aux vibrations,
  thumbnail_name: vibration_PictoCategorie_v2
},
{
  name: Risques de heurt, de cognement,
  thumbnail_name: heurt_PictoCategorie_v2
},
{
  name: Risques liés aux pratiques addictives,
  thumbnail_name: pratiques_addictives_PictoCategorie_v2
},
{
  name: Risques liés à l\'amiante,
  thumbnail_name: amiante_v2
},
{
  name: Risques autres,
  thumbnail_name: autre_PictoCategorie_v2
}
"
             Donne la réponse sous le format suivant (1 objet par risque) :
            {
            "title": "le thumbnail_name correspondant à la catégorie de risque selon l\INRS",
            "description": "Description du risque",
            "cotation": entre 1 et 100,
            "prevention_actions": [
            les mesures de prévention que tu préconises
            ],
           }
           La réponse ne doit contenir qu\'un tableau contenant ces objets risque, pas besoin de texte autour.
            '
            ],
        [
            'role' => 'user',
            'content' => $description
        ]
    ]
];

$ch = curl_init($chatGptUrl);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $chatGptApiKey,
    'Content-Type: application/json'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($chatGptRequest));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$chatGptResponse = curl_exec($ch);
curl_close($ch);

echo $chatGptResponse;
?>
