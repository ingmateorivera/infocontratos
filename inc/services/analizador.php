<?php
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ContractAnalyzer
{
    private $socrataClient;
    private $openAIClient;
    private $configs;
    private $maxRetries = 3;
    private $retryDelay = 1000;

    public function __construct()
    {
        $this->loadConfigs();
        $this->initializeClients();
    }

    private function logError($message, $context = [])
    {
        $logMessage = date('Y-m-d H:i:s') . " - " . $message;
        if (!empty($context)) {
            $logMessage .= " - Context: " . json_encode($context);
        }
        error_log($logMessage);
    }

    private function loadConfigs()
    {
        $requiredEnvVars = [
            'DOMAIN_DATOS',
            'CONTRATOS_DATASET',
            'CONTRATOS_APP_TOKEN',
            'AI_URL_ENDPOINT',
            'AI_NAME_DEPLOY',
            'AI_API_VERSION',
            'AI_API_KEY'
        ];

        foreach ($requiredEnvVars as $var) {
            if (!getenv($var)) {
                throw new Exception("Variable de entorno requerida no encontrada: $var");
            }
        }

        $this->configs = [
            'socrata' => [
                'domain' => getenv('DOMAIN_DATOS'),
                'dataset' => getenv('CONTRATOS_DATASET'),
                'app_token' => getenv('CONTRATOS_APP_TOKEN')
            ],
            'openai' => [
                'endpoint' => getenv('AI_URL_ENDPOINT'),
                'deployment' => getenv('AI_NAME_DEPLOY'),
                'api_version' => getenv('AI_API_VERSION'),
                'api_key' => getenv('AI_API_KEY')
            ]
        ];
    }

    private function initializeClients()
    {
        $this->socrataClient = new Client([
            'base_uri' => "https://{$this->configs['socrata']['domain']}/",
            'headers' => [
                'X-App-Token' => $this->configs['socrata']['app_token']
            ],
            'timeout' => 30,
            'connect_timeout' => 5
        ]);

        $this->openAIClient = new Client([
            'base_uri' => "https://{$this->configs['openai']['endpoint']}/",
            'headers' => [
                'api-key' => $this->configs['openai']['api_key'],
                'Content-Type' => 'application/json'
            ],
            'timeout' => 60,
            'connect_timeout' => 5
        ]);
    }

    public function analyzeContractsByNit($nit, $chunkSize = 1000)
    {
        $this->logError("Iniciando análisis para NIT: {$nit}");

        try {
            $todayDate = date('Y-m-d');

            // Construir la consulta SoQL correctamente
            $query = sprintf(
                '$query=SELECT objeto_del_contrato, valor_del_contrato, fecha_de_firma, modalidad_de_contratacion, proveedor_adjudicado ' .
                'WHERE nit_entidad=\'%s\' AND fecha_de_firma <= \'%s\' ' .
                'ORDER BY fecha_de_firma DESC LIMIT %d',
                $nit,
                $todayDate,
                $chunkSize
            );

            $url = "resource/{$this->configs['socrata']['dataset']}.json";
            //$this->logError("URL de consulta: " . $url . "?" . $query);

            $response = $this->socrataClient->request('GET', $url, [
                'query' => $query,
                'headers' => [
                    'X-App-Token' => $this->configs['socrata']['app_token']
                ]
            ]);

            return $this->processDataStream($nit, $response);

        } catch (Exception $e) {
            $this->logError("Error en análisis de contratos: " . $e->getMessage());
            throw $e;
        }
    }

    private function processDataStream($nit, $response)
    {
        $buffer = '';
        $contracts = [];
        $results = [];
        $totalProcessed = 0;

        $stream = $response->getBody();
        while (!$stream->eof()) {
            $buffer .= $stream->read(8192);

            if (strlen($buffer) >= 1024 * 1024) {
                $newContracts = json_decode($buffer, true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($newContracts)) {
                    $contracts = array_merge($contracts, $newContracts);
                    $buffer = '';

                    if (count($contracts) >= 50) {
                        $results[] = $this->analyzeContractsChunkWithRetry($contracts);
                        $totalProcessed += count($contracts);
                        $this->logError("Procesados {$totalProcessed} contratos para NIT {$nit}");
                        $contracts = [];
                    }
                }
            }
        }

        if (!empty($buffer) || !empty($contracts)) {
            $remainingContracts = json_decode($buffer, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($remainingContracts)) {
                $contracts = array_merge($contracts, $remainingContracts);
            }
            if (!empty($contracts)) {
                $results[] = $this->analyzeContractsChunkWithRetry($contracts);
                $totalProcessed += count($contracts);
            }
        }

        return $this->summarizeResults($results, $totalProcessed);
    }

    private function analyzeContractsChunkWithRetry($contracts)
    {
        $attempts = 0;
        while ($attempts < $this->maxRetries) {
            try {
                return $this->analyzeContractsChunk($contracts);
            } catch (Exception $e) {
                $attempts++;
                if ($attempts === $this->maxRetries) {
                    throw $e;
                }
                usleep($this->retryDelay * 1000);
            }
        }
    }

    private function analyzeContractsChunk($contracts)
    {
        $context = "Eres un auditor experto en contratación pública colombiana. Tu tarea es identificar posibles anomalías, patrones sospechosos y recomendar acciones de seguimiento.";
        
        $prompt = $this->buildAnalysisPrompt($contracts);

        $response = $this->openAIClient->request('POST', "openai/deployments/{$this->configs['openai']['deployment']}/chat/completions", [
            'json' => [
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => $context
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'max_tokens' => 3000,
                'temperature' => 0.7
            ],
            'query' => ['api-version' => $this->configs['openai']['api_version']]
        ]);

        return json_decode($response->getBody(), true);
    }

    private function buildAnalysisPrompt($contracts)
    {
        $prompt = "Analiza los siguientes contratos públicos buscando anomalías como:\n" .
            "- Sobrecostos comparados con contratos similares\n" .
            "- Descripciones vagas o imprecisas\n" .
            "- Patrones inusuales en fechas o montos\n" .
            "- Concentración de contratos en proveedores específicos\n" .
            "- Uso inadecuado de modalidades de contratación\n\n" .
            "Proporciona un análisis detallado y estructurado.\n\n";

        foreach ($contracts as $contract) {
            $prompt .= "---\n";
            $prompt .= "Objeto: {$contract['objeto_del_contrato']}\n";
            $prompt .= "Valor: $" . number_format($contract['valor_del_contrato'], 2, ',', '.') . "\n";
            $prompt .= "Fecha: {$contract['fecha_de_firma']}\n";
            $prompt .= "Modalidad: {$contract['modalidad_de_contratacion']}\n";
            $prompt .= "Proveedor: {$contract['proveedor_adjudicado']}\n\n";
        }

        return $prompt;
    }

    private function summarizeResults($results, $totalProcessed)
    {
        $summary = [
            'total_contratos_analizados' => $totalProcessed,
            'fecha_analisis' => date('Y-m-d H:i:s'),
            'anomalias_detectadas' => [],
            'estadisticas' => [
                'total_valor' => 0,
                'contratos_por_modalidad' => []
            ]
        ];

        foreach ($results as $result) {
            if (isset($result['choices'][0]['message']['content'])) {
                $summary['anomalias_detectadas'][] = $result['choices'][0]['message']['content'];
            }
        }

        return $summary;
    }
}

function sendJsonResponse($data, $success = true)
{
    $response = [
        'success' => $success,
        'data' => $success ? $data : null,
        'error' => !$success ? $data : null
    ];

    if (!headers_sent()) {
        header('Content-Type: application/json');
    }

    echo json_encode($response);
    exit;
}
?>