<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;

class CVAnalysisService
{
    private $client;
    private $apiKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoiNWE0MWNmNDEtNmRjMi00NDczLThmZWMtY2JiYWUwNjUyZGE4IiwidHlwZSI6ImFwaV90b2tlbiJ9.NgKc7awrUTNBWfyvRKfe3Piu-ajCiwp5bYWy9dbrDgk';

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    public function analyzeCV($cvFile)
    {
        $url = 'https://api.edenai.run/v2/ocr/resume_parser';

        try {
            // Lecture du fichier
            $fileContents = file_get_contents($cvFile);
            
            // Envoi de la requête avec multipart/form-data
            $response = $this->client->request('POST', $url, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ],
               
                'multipart' => [
                    [
                        'name'     => 'file',
                        'contents' => fopen($cvFile, 'r'),
                        'filename' => basename($cvFile),
                    ],
                    [
                        'name'     => 'language',
                        'contents' => 'fr',
                    ],
                    [
                        'name'     => 'providers',
                        'contents' => 'affinda',
                    ],
                ],
            ]);

            // Vérification de la réponse
            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);

            if ($statusCode !== 200) {
                throw new \Exception('Erreur API: ' . ($data['message'] ?? 'Erreur inconnue'));
            }

            return [
                'success' => true,
                'affinda' => [
                    'data' => [
                        'skills' => $data['affinda']['extracted_data']['skills'] ?? [],
                        'professional_experiences' => $data['affinda']['extracted_data']['work_experience'] ?? [],
                    ]
                ]
            ];

        } catch (ClientExceptionInterface | ServerExceptionInterface $e) {
            $response = $e->getResponse();
            $content = $response ? $response->getContent(false) : null;
            $errorMessage = 'Erreur inconnue';

            if ($content) {
                $data = json_decode($content, true);
                $apiError = $data['error'] ?? ($data['message'] ?? '');

                if (strpos(strtolower($apiError), 'token expired') !== false) {
                    $errorMessage = 'Votre clé API a expiré.';
                } elseif (strpos(strtolower($apiError), 'invalid token') !== false || strpos(strtolower($apiError), 'unauthorized') !== false) {
                    $errorMessage = 'Votre clé API est invalide.';
                } else {
                    $errorMessage = $apiError;
                }
            }

            return [
                'error' => true,
                'message' => 'Erreur API: ' . $errorMessage,
            ];

        } catch (TransportExceptionInterface $e) {
            return [
                'error' => true,
                'message' => 'Erreur réseau: ' . $e->getMessage(),
            ];
        } catch (\Exception $e) {
            return [
                'error' => true,
                'message' => 'Erreur d\'analyse: ' . $e->getMessage(),
            ];
        }
    }
}

