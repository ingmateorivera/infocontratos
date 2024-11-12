<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

class SocrataAPI {
    private $domain;
    private $dataset;
    private $appToken;
    private $baseUrl;
    
    public function __construct() {
        $this->domain = getenv('DOMAIN_DATOS');
        $this->dataset = getenv('ENTIDADES_DATASET');
        $this->appToken = getenv('ENTIDADES_APP_TOKEN');
        
        if (!$this->domain || !$this->dataset || !$this->appToken) {
            throw new Exception('Configuración de API incompleta. Verifique las variables de entorno.');
        }
        $this->baseUrl = "https://{$this->domain}/resource/{$this->dataset}.json";
    }
    
    public function searchInstitutions($searchTerm = '', $limit = 20) {
        $params = [
            '$select' => 'ccb_nit_inst, nombre',
            '$where' => 'ccb_nit_inst IS NOT NULL AND ccb_nit_inst != ""',
            '$limit' => $limit,
            '$order' => 'nombre'
        ];
        
        if (!empty($searchTerm)) {
            $searchTerm = str_replace("'", "''", $searchTerm);
            $params['$where'] .= " AND upper(nombre) LIKE upper('%" . $searchTerm . "%')";
        }
        
        $url = $this->baseUrl . '?' . http_build_query($params);
        
        return $this->makeRequest($url);
    }
    
    private function makeRequest($url) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'X-App-Token: ' . $this->appToken
            ],
            CURLOPT_TIMEOUT => 30
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_errno($ch)) {
            throw new Exception('Error cURL: ' . curl_error($ch));
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Error en la API de Socrata: HTTP code $httpCode");
        }
        
        $decodedResponse = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Error al decodificar la respuesta JSON');
        }
        
        return $decodedResponse;
    }
    
    public function formatResults($results) {
        if (empty($results)) {
            return [];
        }
        
        return array_map(function($item) {
            return [
                'id' => $item['ccb_nit_inst'],
                'text' => $item['nombre']
            ];
        }, $results);
    }
}

try {
    $api = new SocrataAPI();
    
    $searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
    $results = $api->searchInstitutions($searchTerm);
    $formattedResults = $api->formatResults($results);
    
    echo json_encode($formattedResults);
    
} catch (Exception $e) {
    error_log('Error en Socrata API: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Error en la búsqueda: ' . $e->getMessage()
    ]);
}
?>